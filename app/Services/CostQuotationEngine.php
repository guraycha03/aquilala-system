<?php

namespace App\Services;

use App\AI\AiSuggestionPicker;
use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CostQuotationEngine
 * 
 * Core rule-based calculation engine for the Aquilala Irrigators Association.
 * Computes highly localized, realistic farming cost quotations based on:
 * - Land area, soil type, weather/season context
 * - Crop-specific standard multipliers (DecisionFormula records)
 * - Current active product prices from the store owner's inventory
 * - Conditional adjustments for soil (Sandy, Peat) and weather (Typhoon, El Niño)
 * 
 * @package App\Services
 */
class CostQuotationEngine
{
    /**
     * Standard crop multipliers (bags/kg per hectare) — used as fallback
     * when no DecisionFormula records exist for a given crop + season.
     * These represent typical Philippine rice farming standards.
     */
    const CROP_STANDARD_MULTIPLIERS = [
        'rice' => [
            'seeds_inbred'  => 40,    // kg per hectare
            'seeds_hybrid'  => 20,    // kg per hectare
            'urea'          => 3,     // bags (50kg) per hectare
            'complete_14_14_14' => 4, // bags per hectare
            'mop_0_0_60'    => 1,     // bag per hectare
            'ammonium_sulfate_21_0_0' => 2, // bags per hectare
        ],
    ];

    /**
     * Product-type mapping: keys used in logic to identify what a product is.
     */
    const PRODUCT_TYPE_SEEDS_SECTION    = 'seeds';
    const PRODUCT_TYPE_FERTILIZER_BASAL = 'fertilizer_basal';
    const PRODUCT_TYPE_FERTILIZER_TOP   = 'fertilizer_top';
    const PRODUCT_TYPE_FERTILIZER_POTASH = 'fertilizer_potash';
    const PRODUCT_TYPE_PESTICIDE        = 'pesticide';
    const PRODUCT_TYPE_FUNGICIDE        = 'fungicide';
    const PRODUCT_TYPE_LIME             = 'agricultural_lime';
    const PRODUCT_TYPE_IRRIGATION       = 'irrigation';

    /**
     * Contingency rate for risk buffer (percentage of subtotal).
     */
    const CONTINGENCY_RATE = 0.05; // 5%

    /**
     * How long a quotation stays live.
     *
     * The capstone specification puts a quotation's "Valid Until" at 3 to 7
     * days: long enough to plan a season's inputs, short enough that a
     * price that moves is not quoted as if it were today's. The farmer
     * picks the window; 7 days is the default.
     */
    const VALID_DAY_OPTIONS = [3, 5, 7];
    const DEFAULT_VALID_DAYS = 7;
    /**
     * Budget-fitting priority tiers (transparent category rule):
     * - situational: lime/irrigation lines the soil/weather rules added —
     *   the chosen field conditions justify them, so a budget never cuts them;
     * - essential: seeds and fertilizer — no crop without them;
     * - optional: crop protection — protects yield but can be deferred.
     */
    const PRIORITY_ESSENTIAL   = 'essential';
    const PRIORITY_OPTIONAL    = 'optional';
    const PRIORITY_SITUATIONAL = 'situational';

    /**
     * "Fit suggestions to my budget" lets the plan reach TWICE the stated
     * budget (confirmed with the farmer: a ₱50 budget may suggest up to
     * ₱100) so a must-have that crosses the line can still be considered —
     * never past this ceiling, and never applied when the toggle is off
     * (then the budget stays a guide, not a rule).
     */
    const BUDGET_FLEX_MULTIPLIER = 2.0;

    /**
     * Main entry point: compute a complete cost quotation.
     *
     * @param array $input {
     *     @type string  $crop_type        e.g. 'Rice', 'Corn'
     *     @type string  $season           'wet' | 'dry'
     *     @type string  $weather_context  'low_rain' | 'typhoon' | 'normal' | 'el_nino'
     *     @type string  $soil_type        'clay_loam' | 'sandy' | 'peat'
     *     @type float   $land_area        in hectares
     *     @type array   $selected_products Array of product_ids the farmer chose (optional)
     *     @type string  $notes            Farmer's notes (optional)
     * }
     * @param User|null $storeOwner The store owner performing the computation (defaults to current user)
     * @return array{
     *     quotation: Quotation,
     *     items: array,
     *     summary: array
     * }
     * @throws ValidationException
     */
    public function calculate(array $input, ?User $storeOwner = null): array
    {
        $storeOwner = $storeOwner ?? Auth::user();

        // ─── 1. Validate Inputs ────────────────────────────────────
        $this->validateInputs($input);

        $cropType       = strtolower(trim($input['crop_type']));
        $season         = $input['season'];
        $weatherContext  = $input['weather_context'] ?? 'normal';
        $soilType       = $input['soil_type'] ?? 'clay_loam';
        $landArea       = (float) $input['land_area'];
        $selectedIds    = $input['selected_products'] ?? [];
        $notes          = $input['notes'] ?? null;

        // How long the estimate holds. The capstone spec puts a quotation's
        // "Valid Until" at 3 to 7 days, to protect the farmer and the store
        // from a price that moves; the farmer picks the window.
        $validDays      = (int) ($input['valid_days'] ?? self::DEFAULT_VALID_DAYS);
        if (! in_array($validDays, self::VALID_DAY_OPTIONS, true)) {
            $validDays = self::DEFAULT_VALID_DAYS;
        }

        // ─── 2. Determine what products to include ─────────────────
        // If specific products selected, use those; otherwise fetch all
        // matching products available in the store owner's catalog.
        $products = $this->resolveProducts($cropType, $season, $storeOwner?->id, $selectedIds);

        // ─── 3. Look up DecisionFormula records for base rates ─────
        // Grouped by product: one product can carry several stage rows
        // (basal + topdress + …) whose rates add up to the season total.
        $formulas = DecisionFormula::whereCrop($cropType)
            ->where('season', $season)
            ->get()
            ->groupBy('product_id');

        // ─── 4. Iterate and compute each line item ─────────────────
        $items         = [];
        $subtotal      = 0.0;
        $soilSurcharge = 0.0;
        $weatherSurcharge = 0.0;
        $contingencyFee   = 0.0;

        foreach ($products as $product) {
            $stages = $formulas->get($product->id);

            // Base quantity = sum of every stage's effective rate (clamped to
            // its min/max guardrails) × land_area, or the fallback multiplier.
            $baseRate = $stages
                ? (float) $stages->sum(fn (DecisionFormula $f) => $f->effectiveRate())
                : $this->getFallbackRate($cropType, $product);
            $baseQuantity = round($baseRate * $landArea, 2);

            // The stage schedule, quoted on the line so a farmer can see the
            // rate is split across applications rather than one dumping.
            $stageNote = null;
            if ($stages && $stages->count() > 1) {
                $parts = $stages
                    ->map(fn (DecisionFormula $f) => rtrim(rtrim(number_format($f->effectiveRate(), 2), '0'), '.') . ' at ' . $f->stageLabel())
                    ->implode(', ');
                $stageNote = 'Split across stages: ' . $parts . ' per ha';
            }

            // A rate outside its guardrails still quotes the clamped value —
            // say so, so the entry can be corrected at the source.
            $clampNote = null;
            if ($stages && $stages->contains(fn (DecisionFormula $f) => $f->rateNeedsClamp())) {
                $clampNote = 'Rate adjusted to the configured safety bounds';
            }

            // ── Soil Adjustments ──
            $adjustedQuantity = $baseQuantity;
            $soilAdjustmentNote = null;

            if ($soilType === 'sandy' && $this->isFertilizer($product)) {
                // Sandy soil: +15% fertilizer due to leaching
                $adjustedQuantity = round($baseQuantity * 1.15, 2);
                $soilSurcharge += ($adjustedQuantity - $baseQuantity) * (float) $product->price;
                $soilAdjustmentNote = 'Sandy soil adjustment: +15% (nutrient leaching)';
            }

            if ($soilType === 'peat' && $this->isAgriculturalLime($product)) {
                // Peat soil: add agricultural lime to balance pH
                $adjustedQuantity = max($adjustedQuantity, round(0.5 * $landArea, 2)); // 0.5 bags lime per ha
                $soilAdjustmentNote = 'Peat soil: added agricultural lime for pH balance';
            }

            // ── Weather/Season Adjustments ──
            $weatherNote = null;

            if ($weatherContext === 'typhoon' && $this->isFungicide($product)) {
                // Typhoon: +20% fungicide/pesticide
                $adjustedQuantity = round($adjustedQuantity * 1.20, 2);
                $weatherSurcharge += ($adjustedQuantity - $baseQuantity) * (float) $product->price;
                $weatherNote = 'Typhoon season: +20% fungicide allocation (fungal risk)';
            }

            if ($weatherContext === 'el_nino' && $this->isIrrigation($product)) {
                // El Niño: add irrigation fuel/water fee
                $adjustedQuantity = max($adjustedQuantity, round(1.0 * $landArea, 2)); // 1 unit irrigation per ha
                $weatherNote = 'El Niño: irrigation fuel/water fees applied';
            }

            // ── Round up to whole units for discrete products ──
            $unit = strtolower(trim($stages?->first()->unit ?? $product->stock_unit ?? 'piece'));
            $discreteUnits = ['sack', 'sacks', 'bag', 'bags', 'piece', 'pieces', 'unit', 'units', 'carton', 'cartons', 'box', 'boxes', 'pack', 'packs', 'bottle', 'bottles', 'sachet', 'sachets', 'kit', 'kits', 'length', 'lengths', 'set', 'sets'];

            if (in_array($unit, $discreteUnits)) {
                $adjustedQuantity = (int) ceil($adjustedQuantity);
            }

            // ── Compute Subtotal ──
            $priceAtGeneration = (float) $product->price;
            $lineSubtotal      = round($adjustedQuantity * $priceAtGeneration, 2);

            $items[] = [
                'product_id'          => $product->id,
                'product_name'        => $product->name,
                'category'            => $product->category_display_name,
                'price_at_generation' => $priceAtGeneration,
                'base_quantity'       => $baseQuantity,
                'adjusted_quantity'   => $adjustedQuantity,
                'unit'                => $stages?->first()->unit ?? $product->stock_unit ?? 'piece',
                'subtotal'            => $lineSubtotal,
                'priority'            => $this->priorityFor($product),
                'adjustments'         => array_filter([
                    $soilAdjustmentNote,
                    $weatherNote,
                    $stageNote,
                    $clampNote,
                ]),
            ];

            $subtotal += $lineSubtotal;
        }

        // ─── 4.5 Budget Fitting ──────────────────────────────────
        // Two modes, both stated on the quotation — never silently:
        //
        // Informational (default): essentials and condition-justified
        // lines always stay — no crop without them — and optional crop
        // protection is added in recommended order until the budget is
        // reached; the rest are deferred: still recorded on the
        // quotation, but excluded from the fitted total.
        //
        // Strict ("fit suggestions to my budget"): every line competes
        // for the budget in priority order, so the included set is built
        // inside the cap from the start — if not even the essentials
        // fit, nothing is priced and the shortfall says what the plan
        // needs instead.
        //
        // Contingencies below then apply to what the farmer will
        // actually spend.
        $fullPlanSubtotal = $subtotal;
        $budgetCap = isset($input['budget_cap']) && $input['budget_cap'] !== null
            ? round((float) $input['budget_cap'], 2)
            : null;
        $budgetStrict = filter_var($input['budget_strict'] ?? false, FILTER_VALIDATE_BOOL);
        $deferredItems = [];

        // The hard ceiling fitting works against: the stated budget, or
        // twice it while "fit suggestions to my budget" is on.
        $budgetCeiling = $budgetCap !== null
            ? round($budgetCap * ($budgetStrict ? self::BUDGET_FLEX_MULTIPLIER : 1), 2)
            : null;

        // The essential floor of the full plan (seeds, fertilizer, and any
        // condition-justified lines) — used to explain an unreachable budget.
        $essentialMinimum = round(array_sum(array_column(
            array_filter($items, fn (array $i) => $i['priority'] !== self::PRIORITY_OPTIONAL),
            'subtotal'
        )), 2);

        if ($budgetCap !== null) {
            $runningSubtotal = 0.0;
            $kept           = [];

            if ($budgetStrict) {
                // Strict fit: every line competes for the budget in a
                // transparent order — essentials first, then the lines the
                // field conditions justify, then crop protection; cheapest
                // first within a tier so the budget buys as much as it can.
                // Nothing is added once the flexible ceiling (twice the
                // stated budget) is reached.
                $rank = [
                    self::PRIORITY_ESSENTIAL   => 0,
                    self::PRIORITY_SITUATIONAL => 1,
                    self::PRIORITY_OPTIONAL    => 2,
                ];
                $candidates = $items;
                usort($candidates, fn (array $a, array $b) =>
                    [$rank[$a['priority']], $a['price_at_generation']]
                    <=> [$rank[$b['priority']], $b['price_at_generation']]
                );

                foreach ($candidates as $item) {
                    if ($runningSubtotal + $item['subtotal'] <= $budgetCeiling) {
                        $kept[] = $item;
                        $runningSubtotal += $item['subtotal'];
                    } else {
                        $deferredItems[] = $item;
                    }
                }
            } else {
                // Informational fit: essentials and condition-justified lines
                // always stay — no crop without them — and optional crop
                // protection is added in recommended order while it fits.
                $optionalPool = [];

                foreach ($items as $item) {
                    if ($item['priority'] === self::PRIORITY_OPTIONAL) {
                        $optionalPool[] = $item;
                        continue;
                    }
                    $kept[] = $item;
                    $runningSubtotal += $item['subtotal'];
                }

                foreach ($optionalPool as $item) {
                    if ($runningSubtotal + $item['subtotal'] <= $budgetCap) {
                        $kept[] = $item;
                        $runningSubtotal += $item['subtotal'];
                    } else {
                        $deferredItems[] = $item;
                    }
                }
            }

            // Keep the engine's original line order for display, and say so
            // on every deferred line so the deferral is never silent.
            $order = array_flip(array_column($items, 'product_id'));
            usort($kept,         fn ($a, $b) => $order[$a['product_id']] <=> $order[$b['product_id']]);
            usort($deferredItems, fn ($a, $b) => $order[$a['product_id']] <=> $order[$b['product_id']]);

            foreach ($deferredItems as &$item) {
                $item['adjustments'][] = 'Deferred to fit your budget — add it when your budget allows';
            }
            unset($item);

            $items    = $kept;
            $subtotal = round($runningSubtotal, 2);
        }

        // ─── 5. Weather-Specific Contingency Fees ──────────────────
        $weatherContingency = 0.0;
        if ($weatherContext === 'typhoon') {
            $weatherContingency = round($subtotal * 0.08, 2); // 8% risk contingency for typhoon
        } elseif ($weatherContext === 'el_nino') {
            $weatherContingency = round($subtotal * 0.10, 2); // 10% for drought
        }

        // ─── 6. Peat Soil: Additional Contingency for Lime ─────────
        $soilContingency = 0.0;
        if ($soilType === 'peat') {
            // Check if lime was already added as a product item
            $hasLimeItem = collect($items)->contains(fn($i) => $this->isLimeProduct($i['product_name']));
            if (!$hasLimeItem) {
                $soilContingency = round($landArea * 150, 2); // ₱150/ha lime cost estimate
            }
        }

        // ─── 7. Total ──────────────────────────────────────────────
        $grandTotal = round($subtotal + $weatherContingency + $soilContingency, 2);

        // ─── 8. Build Summary ──────────────────────────────────────
        // Budget outcome, in plain terms for the farmer-facing views:
        // fitted = everything fits (with room to spare), trimmed = some
        // optional lines were deferred, in the flexible band = the plan
        // sits between the stated budget and twice it (fit mode's
        // allowance), over_budget = past the ceiling, unreachable = not
        // even twice the budget can start the plan.
        $budgetStatus = 'none';
        $shortfall    = null;
        $budgetLeft   = null;
        $inFlexBand   = false;

        if ($budgetCap !== null) {
            if ($budgetStrict && $items === [] && $fullPlanSubtotal > 0) {
                // Strict mode priced nothing inside the ceiling: the plan's
                // essentials already start above twice the budget.
                $budgetStatus = 'unreachable';
                $shortfall    = round($essentialMinimum - $budgetCeiling, 2);
            } elseif ($grandTotal > $budgetCeiling) {
                // Contingencies can push a strict plan past the ceiling;
                // with the toggle off, the stated cap is the rule.
                $budgetStatus = 'over_budget';
                $shortfall    = round($grandTotal - $budgetCeiling, 2);
            } elseif ($budgetStrict && $grandTotal > $budgetCap) {
                // The flexible band: over the stated budget, never past
                // twice it — counted as fitted, with no "left" to claim.
                $budgetStatus = $deferredItems !== [] ? 'trimmed' : 'fitted';
                $budgetLeft   = null;
                $inFlexBand   = true;
            } elseif ($deferredItems !== []) {
                $budgetStatus = 'trimmed';
                $budgetLeft   = round($budgetCap - $grandTotal, 2);
            } else {
                $budgetStatus = 'fitted';
                $budgetLeft   = round($budgetCap - $grandTotal, 2);
            }
        }

        $summary = [
            'land_area'           => $landArea,
            'soil_type'           => $soilType,
            'weather_context'     => $weatherContext,
            'season'              => $season,
            'subtotal'            => $subtotal,
            'weather_contingency' => $weatherContingency,
            'soil_contingency'    => $soilContingency,
            'grand_total'         => $grandTotal,
            'budget_cap'          => $budgetCap,
            'budget_strict'       => $budgetStrict,
            'budget_flex_ceiling' => $budgetCeiling,
            'budget_in_flex_band' => $inFlexBand,
            'budget_status'       => $budgetStatus,
            'full_plan_subtotal'  => round($fullPlanSubtotal, 2),
            'essential_minimum'   => $essentialMinimum,
            'deferred_count'      => count($deferredItems),
            'deferred_total'      => round(array_sum(array_column($deferredItems, 'subtotal')), 2),
            'shortfall'           => $shortfall,
            'budget_left'         => $budgetLeft,
            'valid_until'         => now()->addDays($validDays)->format('Y-m-d H:i:s'),
            'quotation_number'    => 'QT-' . date('Y') . '-' . strtoupper(Str::random(6)),
        ];

        return [
            'items'          => $items,
            'deferred_items' => $deferredItems,
            'summary'        => $summary,
        ];
    }

    /**
     * Calculate and persist a quotation to the database.
     *
     * Quotations are generated by farmers instantly and shared with all
     * involved stores as demand intel — there is no single assigned store,
     * so store_user_id is left null.
     *
     * @param array $input Same input as calculate()
     * @param User|null $farmer The requesting farmer
     * @param User|null $storeOwner Kept for API compatibility (unused)
     * @return Quotation
     */
    public function calculateAndSave(array $input, ?User $farmer = null, ?User $storeOwner = null): Quotation
    {
        $farmer      = $farmer ?? Auth::user();
        $subTotal    = $this->calculate($input, $farmer);
        $summary     = $subTotal['summary'];

        return DB::transaction(function () use ($farmer, $input, $subTotal, $summary) {
            $quotation = Quotation::create([
                'user_id'              => $farmer->id,
                'quotation_number'     => $summary['quotation_number'],
                'crop_type'            => $input['crop_type'],
                'season'               => $input['season'],
                'soil_type'            => $input['soil_type'] ?? 'clay_loam',
                'weather_context'      => $input['weather_context'] ?? 'normal',
                'land_area'            => $summary['land_area'],
                'budget_cap'           => $summary['budget_cap'],
                'budget_strict'        => $summary['budget_strict'],
                'estimated_total_cost' => $summary['grand_total'],
                'status'               => Quotation::STATUS_COMPUTED,
                'valid_until'          => $summary['valid_until'],
                'notes'                => $input['notes'] ?? null,
            ]);

            // Both fitted and deferred lines are recorded: the farmer and
            // the store owner see the full plan, with the deferral flag
            // marking what the stated budget trimmed off.
            $allItems = array_merge(
                array_map(fn (array $i) => $i + ['is_deferred' => false], $subTotal['items']),
                array_map(fn (array $i) => $i + ['is_deferred' => true], $subTotal['deferred_items'] ?? []),
            );

            foreach ($allItems as $itemData) {
                QuotationItem::create([
                    'quotation_id'        => $quotation->id,
                    'product_id'          => $itemData['product_id'],
                    'product_name'        => $itemData['product_name'],
                    'price_at_generation' => $itemData['price_at_generation'],
                    'calculated_quantity' => $itemData['adjusted_quantity'],
                    'subtotal'            => $itemData['subtotal'],
                    'is_deferred'         => $itemData['is_deferred'],
                    'priority'            => $itemData['priority'] ?? self::PRIORITY_ESSENTIAL,
                ]);
            }

            return $quotation->load(['items.product', 'farmer']);
        });
    }

    // ────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ────────────────────────────────────────────────────────────────

    /**
     * Validate all incoming parameters strictly.
     */
    private function validateInputs(array $input): void
    {
        $validator = validator($input, [
            'crop_type'         => ['required', 'string', 'max:255', 'regex:/^[\pL\s\-]+$/u'],
            'season'            => ['required', 'in:wet,dry'],
            'weather_context'   => ['required', 'in:normal,low_rain,typhoon,el_nino'],
            'soil_type'         => ['required', 'in:clay_loam,sandy,peat'],
            'land_area'         => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'budget_cap'        => ['nullable', 'numeric', 'min:1', 'max:99999999.99'],
            'budget_strict'     => ['nullable', 'boolean'],
            'selected_products' => ['nullable', 'array'],
            'selected_products.*' => ['integer', 'exists:products,id'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ], [
            'crop_type.required'       => 'Please specify the crop type.',
            'crop_type.regex'          => 'Crop type may only contain letters, spaces, and hyphens.',
            'season.in'                => 'Season must be Wet or Dry.',
            'weather_context.in'       => 'Invalid weather context selected.',
            'soil_type.in'             => 'Invalid soil type selected.',
            'land_area.required'       => 'Please enter your land area in hectares.',
            'land_area.max'            => 'Land area cannot exceed 999.99 hectares.',
            'selected_products.*.exists' => 'One or more selected products do not exist.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Resolve the list of products to include in the quotation.
     * If specific product IDs given, fetch only those (must be active).
     * Otherwise, fetch all active products that have DecisionFormula records
     * matching the crop_type + season, or fallback to category-based matching.
     */
    /**
     * Products to price for this crop and season.
     *
     * The catalog is searched across every store on purpose: a farmer asking
     * for an estimate is quoted the products Aquilala actually carries, and
     * $storeId is not needed to find them. It stays in the signature for the
     * store-owner path, which is why it is nullable - a farmer-initiated
     * request has no store owner, and reading an id off a null user used to
     * throw before a single product was looked at.
     */
    private function resolveProducts(string $cropType, string $season, ?int $storeId, array $selectedIds): \Illuminate\Support\Collection
    {
        $query = Product::active()->with('category');

        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        } else {
            // Auto-detect: find products that have DecisionFormula records for this crop+season
            $formulaProductIds = DecisionFormula::whereCrop($cropType)
                ->where('season', $season)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->toArray();

            $query->where(function ($products) use ($formulaProductIds, $cropType, $season) {
                if (! empty($formulaProductIds)) {
                    $products->whereIn('products.id', $formulaProductIds);
                }

                $products->orWhereHas('cropTypes', function ($cropTypes) use ($cropType) {
                    $cropTypes->whereRaw('LOWER(crop_type) = ?', [strtolower($cropType)]);
                })->where(function ($seasonQuery) use ($season) {
                    $seasonQuery->whereIn('recommended_season', [$season, 'both'])
                        ->orWhereNull('recommended_season');
                });
            });

            if (empty($formulaProductIds) && ! $query->exists()) {
                // Fallback: match by category name patterns
                $categoryPatterns = $this->getCategoryPatternsForCrop($cropType);
                $query = Product::active()->with('category')->whereHas('category', function ($q) use ($categoryPatterns) {
                    $q->where(function ($sub) use ($categoryPatterns) {
                        foreach ($categoryPatterns as $pattern) {
                            $sub->orWhere('name', 'like', $pattern);
                        }
                    });
                });
            }
        }

        // Include any product from this store if computing as store owner
        // Otherwise get from any store (for farmer-initiated requests)
        $products = $query->get();

        // Ensure at least some products are returned
        if ($products->isEmpty()) {
            // Last resort: get any active fertilizers/seeds
            $products = Product::active()
                ->whereHas('category', fn($q) => $q->whereIn('name', [
                    'Fertilizers', 'Seeds', 'Pesticides', 'Crop Protection'
                ]))
                ->take(5)
                ->get();
        }

        // The farmer picked nothing: suggest the best product for each job
        // instead of every product configured for the crop. Products the
        // farmer chose by hand are never touched — they asked for those.
        if (empty($selectedIds)) {
            $products = $this->curateAutoSuggestions($products, $cropType, $season);
        }

        return $products;
    }

    /**
     * Narrow an auto-included candidate list to the best product per job.
     *
     * A quotation used to quote EVERY product with a formula for the
     * crop — 20+ lines, several of them competing for the same job
     * (five fertilizer types, three insecticides…). A suggestion means
     * the best seed, the best fertilizer, one weed control, and so on:
     * one winner per role, ordered crop-basics-first.
     *
     * The model provider is asked first when it is reachable — it can
     * weigh the candidates with more nuance than a fixed ranking. Any
     * problem at all (no key, slow network, garbage reply, a bug in this
     * very method) falls through to the deterministic ranking below, and
     * the whole method is wrapped so a quotation is NEVER worth an error
     * page: worst case, the full candidate list is quoted as before.
     */
    private function curateAutoSuggestions(\Illuminate\Support\Collection $products, string $cropType, string $season): \Illuminate\Support\Collection
    {
        if ($products->count() < 2) {
            return $products;
        }

        try {
            $candidates = $this->describeCandidates($products);

            $pickIds = app(AiSuggestionPicker::class)->pick($candidates, $cropType, $season);

            if ($pickIds === null) {
                $pickIds = $this->deterministicPicks($candidates);
            }

            $byId = [];
            foreach ($products as $product) {
                $byId[(int) $product->id] = $product;
            }

            $picked = [];
            foreach ($pickIds as $id) {
                if (isset($byId[$id])) {
                    $picked[] = $byId[$id];
                }
            }

            usort($picked, fn (Product $a, Product $b) => $this->autoRoleRank($a) <=> $this->autoRoleRank($b));

            return \Illuminate\Support\Collection::make($picked);
        } catch (\Throwable $e) {
            Log::warning('Auto-include curation failed; quoting the full candidate list.', [
                'error' => $e->getMessage(),
            ]);

            return $products;
        }
    }

    /**
     * Flat descriptors both pickers reason over: what each candidate is,
     * which job it does, and how it has been doing (stock, farmer demand).
     *
     * @return array<int, array{id: int, name: string, category: string, role: string, price: float, stock: int, times_quoted: int}>
     */
    private function describeCandidates(\Illuminate\Support\Collection $products): array
    {
        $timesQuoted = DB::table('quotation_items')
            ->whereIn('product_id', $products->pluck('id')->all())
            ->groupBy('product_id')
            ->selectRaw('product_id, COUNT(*) as times')
            ->pluck('times', 'product_id');

        return $products
            ->map(function (Product $product) use ($timesQuoted) {
                $category = (string) $product->category_display_name;

                return [
                    'id'            => (int) $product->id,
                    'name'          => (string) $product->name,
                    'category'      => $category,
                    'role'          => $this->roleForCategory($category),
                    'price'         => round((float) $product->price, 2),
                    'stock'         => (int) $product->stock_quantity,
                    'times_quoted'  => (int) ($timesQuoted[$product->id] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * The AI-free ranking: for each job the single strongest candidate.
     *
     * In stock beats out of stock — an unbuyable line helps nobody. Then
     * the product other farmers quoted most (proven demand), then the
     * cheaper one (fits more budgets), then the older listing. A total
     * order every step of the way, so the same inputs always suggest the
     * same products.
     *
     * @param  array<int, array{id: int, role: string, price: float, stock: int, times_quoted: int}>  $candidates
     * @return array<int, int>
     */
    private function deterministicPicks(array $candidates): array
    {
        $best = [];

        foreach ($candidates as $candidate) {
            $role = $candidate['role'];

            if (! isset($best[$role]) || $this->candidateBeats($candidate, $best[$role])) {
                $best[$role] = $candidate;
            }
        }

        return array_map(fn (array $candidate) => $candidate['id'], array_values($best));
    }

    private function candidateBeats(array $candidate, array $current): bool
    {
        if (($candidate['stock'] > 0) !== ($current['stock'] > 0)) {
            return $candidate['stock'] > 0;
        }
        if ($candidate['times_quoted'] !== $current['times_quoted']) {
            return $candidate['times_quoted'] > $current['times_quoted'];
        }
        if ($candidate['price'] !== $current['price']) {
            return $candidate['price'] < $current['price'];
        }

        return $candidate['id'] < $current['id'];
    }

    /**
     * Which job a product does, derived from its category name. Grouping
     * is deliberately coarse per job — every fertilizer family is one
     * fertilizer slot, every protection family keeps its own slot
     * (a weed killer does not replace a fungicide).
     */
    private function roleForCategory(string $category): string
    {
        $name = strtolower($category);

        if (str_contains($name, 'seed') || str_contains($name, 'seedling') || str_contains($name, 'planting material')) {
            return 'seed';
        }
        if (str_contains($name, 'fertilizer') || str_contains($name, 'fertiliser') || str_contains($name, 'micronutrient') || str_contains($name, 'nutrient')) {
            return 'fertilizer';
        }
        if (str_contains($name, 'herbicide') || str_contains($name, 'weed')) {
            return 'herbicide';
        }
        if (str_contains($name, 'fungicide') || str_contains($name, 'fungi') || str_contains($name, 'disease')) {
            return 'fungicide';
        }
        if (str_contains($name, 'insecticide') || str_contains($name, 'pesticide') || str_contains($name, 'insect') || str_contains($name, 'miticide')) {
            return 'insecticide';
        }
        if (str_contains($name, 'crop protection') || str_contains($name, 'pest control')) {
            return 'crop-protection';
        }
        if (str_contains($name, 'irrigation')) {
            return 'irrigation';
        }
        if (str_contains($name, 'tool') || str_contains($name, 'machinery') || str_contains($name, 'equipment')) {
            return 'tool';
        }

        // Soil amendments and everything else keep their own slot: a lime
        // or organic input is its own job, and an unknown category should
        // never be silently folded into the wrong one.
        if (str_contains($name, 'soil') || str_contains($name, 'amendment') || str_contains($name, 'lime')) {
            return 'soil';
        }

        return 'other';
    }

    /**
     * Display order for auto-suggested lines: crop basics first, then
     * field-condition inputs, then protections, then the rest.
     */
    private function autoRoleRank(Product $product): int
    {
        $rank = [
            'seed'           => 0,
            'fertilizer'     => 1,
            'soil'           => 2,
            'herbicide'      => 3,
            'fungicide'      => 4,
            'insecticide'    => 5,
            'crop-protection'=> 6,
            'irrigation'     => 7,
            'tool'           => 8,
            'other'          => 9,
        ];

        return $rank[$this->roleForCategory((string) $product->category_display_name)] ?? 9;
    }

    /**
     * Get the fallback rate per hectare for a product when no DecisionFormula exists.
     */
    private function getFallbackRate(string $cropType, Product $product): float
    {
        $categoryName = strtolower($product->category_display_name);
        $productName  = strtolower($product->name);

        // Rice-specific fallbacks
        if ($cropType === 'rice') {
            if (str_contains($productName, 'inbred')) return 40;   // kg seeds/ha
            if (str_contains($productName, 'hybrid')) return 20;    // kg seeds/ha
            if (str_contains($productName, 'urea')) return 3;       // bags/ha
            if (str_contains($productName, 'complete') || str_contains($categoryName, 'fertilizer')) return 4;
            if (str_contains($productName, 'mop') || str_contains($productName, 'potash')) return 1;
            if (str_contains($productName, 'fungicide') || str_contains($productName, 'pesticide')) return 2;
        }

        // Default fallback: 1 unit per hectare
        return 1.0;
    }

    /**
     * Get SQL LIKE patterns for category matching based on crop type.
     */
    private function getCategoryPatternsForCrop(string $cropType): array
    {
        // Common agricultural input categories
        return [
            '%Seed%',
            '%Fertilizer%',
            '%Pesticide%',
            '%Fungicide%',
            '%Crop Protection%',
            '%Agricultural Input%',
            '%Soil Amendment%',
        ];
    }

    /**
     * Check if a product is a fertilizer type (for soil adjustments).
     */
    private function isFertilizer(Product $product): bool
    {
        $name = strtolower($product->name);
        $cat  = strtolower($product->category_display_name);

        return str_contains($name, 'urea')
            || str_contains($name, 'complete')
            || str_contains($name, 'ammonium')
            || str_contains($name, 'fertilizer')
            || str_contains($name, 'mop')
            || str_contains($name, 'potash')
            || str_contains($name, 'npk')
            || str_contains($cat, 'fertilizer');
    }

    /**
     * Check if a product is a fungicide or pesticide (for weather adjustments).
     */
    private function isFungicide(Product $product): bool
    {
        $name = strtolower($product->name);
        $cat  = strtolower($product->category_display_name);

        return str_contains($name, 'fungicide')
            || str_contains($name, 'pesticide')
            || str_contains($name, 'insecticide')
            || str_contains($name, 'herbicide')
            || str_contains($cat, 'crop protection')
            || str_contains($cat, 'pesticide');
    }

    /**
     * Check if a product is an irrigation fee/fuel line item.
     */
    private function isIrrigation(Product $product): bool
    {
        $name = strtolower($product->name);
        $cat  = strtolower($product->category_display_name);

        return str_contains($name, 'irrigation')
            || str_contains($name, 'water fee')
            || str_contains($name, 'fuel')
            || str_contains($cat, 'irrigation')
            || str_contains($cat, 'utility');
    }

    /**
     * Check if a product is agricultural lime (for peat soil).
     */
    private function isAgriculturalLime(Product $product): bool
    {
        $name = strtolower($product->name);
        return str_contains($name, 'lime')
            || str_contains($name, 'agricultural lime')
            || str_contains($name, 'calcium');
    }

    /**
     * Check by product name if it's a lime product (for post-processing check).
     */
    private function isLimeProduct(string $productName): bool
    {
        $name = strtolower($productName);
        return str_contains($name, 'lime')
            || str_contains($name, 'agricultural lime')
            || str_contains($name, 'calcium');
    }

    /**
     * Budget-fitting priority for a line item.
     *
     * Situational lines (peat lime, El Niño irrigation) are additions the
     * chosen field conditions justify, so a budget never cuts them. Seeds
     * and fertilizer are essential — no crop without them. Crop protection
     * is optional: it protects yield but can be deferred when money is
     * tight. Anything the engine cannot classify defaults to essential so
     * a budget never silently removes an unknown product.
     */
    private function priorityFor(Product $product): string
    {
        if ($this->isAgriculturalLime($product) || $this->isIrrigation($product)) {
            return self::PRIORITY_SITUATIONAL;
        }

        if ($this->isFungicide($product)) {
            return self::PRIORITY_OPTIONAL;
        }

        return self::PRIORITY_ESSENTIAL;
    }

    /**
     * Get the label for a weather context code.
     */
    public static function getWeatherContextLabel(string $code): string
    {
        return match ($code) {
            'normal'   => 'Dry Season - Normal',
            'low_rain' => 'Wet Season - Low Rain',
            'typhoon'  => 'Wet Season - Typhoons/Heavy Rain',
            'el_nino'  => 'Dry Season - El Niño/Drought',
            default    => ucfirst(str_replace('_', ' ', $code)),
        };
    }

    /**
     * Get the label for a soil type code.
     */
    public static function getSoilTypeLabel(string $code): string
    {
        return match ($code) {
            'clay_loam' => 'Clay Loam',
            'sandy'     => 'Sandy',
            'peat'      => 'Peat / Organic',
            default     => ucfirst(str_replace('_', ' ', $code)),
        };
    }

    /**
     * Get all available weather context options for frontend forms.
     */
    public static function getWeatherContextOptions(): array
    {
        return [
            'normal'   => 'Dry Season - Normal',
            'low_rain' => 'Wet Season - Low Rain',
            'typhoon'  => 'Wet Season - Typhoons/Heavy Rain',
            'el_nino'  => 'Dry Season - El Niño/Drought',
        ];
    }

    /**
     * Get all available soil type options for frontend forms.
     */
    public static function getSoilTypeOptions(): array
    {
        return [
            'clay_loam' => 'Clay Loam — Standard water retention',
            'sandy'     => 'Sandy — High drainage, requires more fertilizer',
            'peat'      => 'Peat / Organic — Nutrient-rich but acidic',
        ];
    }
}
