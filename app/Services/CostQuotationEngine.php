<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CostQuotationEngine
 * 
 * Core rule-based calculation engine for the Aquilala Irrigators Association.
 * Computes highly localized, realistic farming cost estimates based on:
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

        // ─── 2. Determine what products to include ─────────────────
        // If specific products selected, use those; otherwise fetch all
        // matching products available in the store owner's catalog.
        $products = $this->resolveProducts($cropType, $season, $storeOwner->id, $selectedIds);

        // ─── 3. Look up DecisionFormula records for base rates ─────
        $formulas = DecisionFormula::whereCrop($cropType)
            ->where('season', $season)
            ->get()
            ->keyBy('product_id');

        // ─── 4. Iterate and compute each line item ─────────────────
        $items         = [];
        $subtotal      = 0.0;
        $soilSurcharge = 0.0;
        $weatherSurcharge = 0.0;
        $contingencyFee   = 0.0;

        foreach ($products as $product) {
            $formula = $formulas->get($product->id);

            // Base quantity = formula rate_per_hectare * land_area, or fallback multiplier
            $baseRate = $formula ? (float) $formula->rate_per_hectare : $this->getFallbackRate($cropType, $product);
            $baseQuantity = round($baseRate * $landArea, 2);

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

            // ── Compute Subtotal ──
            $priceAtGeneration = (float) $product->price;
            $lineSubtotal      = round($adjustedQuantity * $priceAtGeneration, 2);

            $items[] = [
                'product_id'          => $product->id,
                'product_name'        => $product->name,
                'category'            => $product->category?->name ?? 'General',
                'price_at_generation' => $priceAtGeneration,
                'base_quantity'       => $baseQuantity,
                'adjusted_quantity'   => $adjustedQuantity,
                'unit'                => $formula?->unit ?? $product->stock_unit ?? 'piece',
                'subtotal'            => $lineSubtotal,
                'adjustments'         => array_filter([
                    $soilAdjustmentNote,
                    $weatherNote,
                ]),
            ];

            $subtotal += $lineSubtotal;
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
        $summary = [
            'land_area'           => $landArea,
            'soil_type'           => $soilType,
            'weather_context'     => $weatherContext,
            'season'              => $season,
            'subtotal'            => $subtotal,
            'weather_contingency' => $weatherContingency,
            'soil_contingency'    => $soilContingency,
            'grand_total'         => $grandTotal,
            'valid_until'         => now()->addDays(7)->format('Y-m-d H:i:s'),
            'quotation_number'    => 'QT-' . date('Y') . '-' . strtoupper(Str::random(6)),
        ];

        return [
            'items'   => $items,
            'summary' => $summary,
        ];
    }

    /**
     * Calculate and persist a quotation to the database.
     *
     * @param array $input Same input as calculate()
     * @param User|null $farmer The requesting farmer
     * @param User|null $storeOwner The computing store owner
     * @return Quotation
     */
    public function calculateAndSave(array $input, ?User $farmer = null, ?User $storeOwner = null): Quotation
    {
        $farmer      = $farmer ?? Auth::user();
        $storeOwner  = $storeOwner ?? Auth::user();
        $result      = $this->calculate($input, $storeOwner);
        $summary     = $result['summary'];

        return DB::transaction(function () use ($farmer, $storeOwner, $input, $result, $summary) {
            $quotation = Quotation::create([
                'user_id'              => $farmer->id,
                'store_user_id'        => $storeOwner->id,
                'quotation_number'     => $summary['quotation_number'],
                'crop_type'            => $input['crop_type'],
                'season'               => $input['season'],
                'soil_type'            => $input['soil_type'] ?? 'clay_loam',
                'weather_context'      => $input['weather_context'] ?? 'normal',
                'land_area'            => $summary['land_area'],
                'estimated_total_cost' => $summary['grand_total'],
                'status'               => Quotation::STATUS_COMPUTED,
                'valid_until'          => $summary['valid_until'],
                'notes'                => $input['notes'] ?? null,
            ]);

            foreach ($result['items'] as $itemData) {
                QuotationItem::create([
                    'quotation_id'        => $quotation->id,
                    'product_id'          => $itemData['product_id'],
                    'product_name'        => $itemData['product_name'],
                    'price_at_generation' => $itemData['price_at_generation'],
                    'calculated_quantity' => $itemData['adjusted_quantity'],
                    'subtotal'            => $itemData['subtotal'],
                ]);
            }

            return $quotation->load(['items.product', 'farmer', 'storeOwner']);
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
    private function resolveProducts(string $cropType, string $season, int $storeId, array $selectedIds): \Illuminate\Support\Collection
    {
        $query = Product::active()->with('category');

        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        } else {
            // Auto-detect: find products that have DecisionFormula records for this crop+season
            $formulaProductIds = DecisionFormula::whereCrop($cropType)
                ->where('season', $season)
                ->pluck('product_id')
                ->toArray();

            if (!empty($formulaProductIds)) {
                $query->whereIn('id', $formulaProductIds);
            } else {
                // Fallback: match by category name patterns
                $categoryPatterns = $this->getCategoryPatternsForCrop($cropType);
                $query->whereHas('category', function ($q) use ($categoryPatterns) {
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

        return $products;
    }

    /**
     * Get the fallback rate per hectare for a product when no DecisionFormula exists.
     */
    private function getFallbackRate(string $cropType, Product $product): float
    {
        $categoryName = strtolower($product->category?->name ?? '');
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
        $cat  = strtolower($product->category?->name ?? '');

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
        $cat  = strtolower($product->category?->name ?? '');

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
        $cat  = strtolower($product->category?->name ?? '');

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

