<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DecisionFormula;
use App\Models\FarmerCrop;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CostQuotationEngine;
use App\Services\CropLibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class QuotationController extends Controller
{
    // ────────────────────────────────────────────────────────────────
    //  FARMER — Generate Cost Quotation (auto-computed instantly)
    // ────────────────────────────────────────────────────────────────

    /**
     * List all quotations for the authenticated farmer.
     */
    public function farmerIndex()
    {
        $quotations = Quotation::with('items.product.storeOwner')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        $allQuotes = Quotation::where('user_id', Auth::id())->get();

        $totalQuotes = $allQuotes->count();
        $computedCount = $allQuotes->filter(fn ($q) => ! $q->is_expired && $q->status === 'computed')->count();
        $viewedCount = $allQuotes->filter(fn ($q) => ! $q->is_expired && $q->status === 'viewed')->count();
        $totalValue = $allQuotes->sum('estimated_total_cost');

        return view('farmer.quotations.index', compact(
            'quotations', 'totalQuotes', 'computedCount', 'viewedCount', 'totalValue'
        ));
    }

    /**
     * Show a quotation to the farmer as a plain-language overview page.
     *
     * Only the essential summary is presented here (total, plan details,
     * top cost drivers, stores). The full itemized table lives on the
     * dedicated "breakdown" subpage so farmers are never dumped into a
     * wall of raw numbers right after generating a quotation.
     */
    public function farmerShow(Quotation $quotation)
    {
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        $quotation->load(['items.product.storeOwner', 'farmer']);

        // Decision formulas for this crop + season drive the per-line
        // calculation breakdown used by the itemized subpage. Stage rows are
        // merged per product so the unit and total season rate stay readable.
        $formulas = $this->formulaTotalsFor($quotation);

        $total = (float) $quotation->estimated_total_cost;

        // What's driving the cost — top items with % share of the total.
        $topItems = $quotation->items
            ->map(function ($item) use ($formulas, $total) {
                $unit = strtolower(trim($formulas->get($item->product_id)?->unit
                    ?? $item->product?->stock_unit
                    ?? 'unit'));

                return [
                    'product_name' => $item->product_name,
                    'store_name' => $item->product?->storeOwner?->name,
                    'price' => (float) $item->price_at_generation,
                    'quantity' => (float) $item->calculated_quantity,
                    'unit' => $unit,
                    'subtotal' => (float) $item->subtotal,
                    'share' => $total > 0
                        ? round(((float) $item->subtotal / $total) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('subtotal')
            ->values()
            ->take(5);

        return view('farmer.quotations.show', compact('quotation', 'formulas', 'total', 'topItems'));
    }

    /**
     * Show the full itemized breakdown for a quotation (dedicated subpage).
     *
     * Kept separate from the overview page on purpose: the main page
     * should be readable at a glance, while this page is where the
     * line-by-line "rate/ha × area → price → subtotal" math lives.
     */
    public function farmerBreakdown(Quotation $quotation)
    {
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        $quotation->load(['items.product.storeOwner', 'farmer']);

        $formulas = $this->formulaTotalsFor($quotation);

        return view('farmer.quotations.breakdown', compact('quotation', 'formulas'));
    }

    /**
     * This quotation's formulas merged per product: one entry per product
     * carrying the unit plus the season rate summed across application stages
     * (clamped to each stage's min/max bounds). The views read `unit` and
     * `rate_per_hectare`, so stage rows never leak through as duplicates.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function formulaTotalsFor(Quotation $quotation)
    {
        return DecisionFormula::whereCrop($quotation->crop_type)
            ->where('season', $quotation->season)
            ->get()
            ->groupBy('product_id')
            ->map(fn ($stages) => (object) [
                'product_id'       => $stages->first()->product_id,
                'unit'             => $stages->first()->unit,
                'rate_per_hectare' => round((float) $stages->sum(fn ($f) => $f->effectiveRate()), 2),
                'stages'           => $stages->values(),
            ]);
    }

    /**
     * Delete a quotation (farmer only, own quotations).
     */
    public function farmerDestroy(Quotation $quotation)
    {
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        ActivityLogger::quotationDeleted($quotation);

        $quotation->items()->delete();
        $quotation->delete();

        return redirect()
            ->route('farmer.quotations.index')
            ->with('success', 'Cost estimate has been deleted.');
    }

    /**
     * Generate an AI readability overview of ONE quotation for the farmer.
     *
     * Uses Groq when configured; otherwise (or on failure) returns a
     * deterministic "smart response" computed from the quotation itself so
     * the page always has something useful to show — never a dead end.
     */
    public function farmerAiOverview(Request $request, Quotation $quotation)
    {
        abort_unless($request->user()?->role === 'farmer', 403);

        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        $quotation->load(['items.product.storeOwner']);

        $items = $quotation->items
            ->map(fn ($item) => [
                'name' => $item->product_name,
                'store' => $item->product?->storeOwner?->name ?? null,
                'price' => (float) $item->price_at_generation,
                'quantity' => (float) $item->calculated_quantity,
                'unit' => strtolower(trim($item->product?->stock_unit ?? 'unit')),
                'subtotal' => round((float) $item->subtotal, 2),
                // Deferred lines are NOT part of the total — the advisor
                // must know, or its explanation will not add up.
                'deferred' => (bool) $item->is_deferred,
            ])
            ->map(fn ($i) => $i['unit'] === 'piece' ? array_merge($i, ['unit' => 'unit']) : $i)
            ->values();

        $total = (float) $quotation->estimated_total_cost;
        $landArea = (float) $quotation->land_area;
        $perHectare = $landArea > 0 ? round($total / $landArea, 2) : $total;

        $deferredItems = $quotation->items->where('is_deferred', true);
        $budgetCap = $quotation->budget_cap !== null ? (float) $quotation->budget_cap : null;

        $context = [
            'farm_area' => $landArea,
            'crop' => $quotation->crop_type,
            'season' => $quotation->season,
            'total' => $total,
            'per_hectare' => $perHectare,
            'item_count' => $items->count(),
            'soil' => $quotation->soil_type
                ? CostQuotationEngine::getSoilTypeLabel($quotation->soil_type)
                : null,
            'weather_risk' => $quotation->weather_context
                ? CostQuotationEngine::getWeatherContextLabel($quotation->weather_context)
                : null,
            'budget' => $budgetCap === null ? null : [
                'cap' => $budgetCap,
                'fitted_total' => $total,
                'deferred_count' => $deferredItems->count(),
                'deferred_total' => round((float) $deferredItems->sum('subtotal'), 2),
                'deferred_names' => $deferredItems->pluck('product_name')->filter()->take(8)->values()->all(),
            ],
            'generated' => $quotation->created_at->format('F d, Y'),
            'valid_until' => $quotation->valid_until?->format('F d, Y'),
            'items' => $items,
            'top_items' => $items->sortByDesc('subtotal')->take(5)->values(),
            'stores' => $quotation->items
                ->pluck('product.storeOwner.name')
                ->filter()
                ->unique()
                ->values(),
        ];

        $fallback = $this->buildFarmerSmartFallback($quotation, $context);

        $apiKey = config('services.groq.key');

        if (! $apiKey) {
            return response()->json([
                'overview' => $fallback,
                'source' => 'fallback',
                'metrics' => $context,
            ]);
        }

        $system = <<<'PROMPT'
You are a trusted farm adviser for the Aquilala Irrigators Association (Bulan, Sorsogon, Philippines). A farmer just generated a cost quotation for their planned crop. Explain what it means for THEIR farm, using ONLY the JSON telemetry given. Never invent numbers, prices, or products.

HOW TO SOUND:
- Write like a knowledgeable neighbor who also farms: plain words, short sentences, honest observations. Explain the reason behind a point, not just the point.
- Vary how sentences start. No filler ("In conclusion", "It is important to note"), no corporate hedging, no emojis, and never the same sentence shape twice.
- Talk to this farmer's situation, not to a generic audience.

WHAT MATTERS HERE (use whatever applies, skip the rest):
- Field conditions: the soil type and weather risk in the telemetry change quantities and prices — say plainly what they changed and why it matters for this crop.
- Budget: when a budget is present, explain what fits, what was left out, and why that trade-off is sensible for this plan.
- Items marked "deferred" are NOT part of the total — they are inputs the farmer can add when the budget allows. Make that clear.
- The total is what the plan costs now; per-hectare cost puts it in scale for the land area.

STRUCTURE — use these markdown headings exactly:

### AI Takeaway
1-2 sentences: what this quotation means for this farmer's plan — the inputs, the total, the per-hectare cost, and one honest read on whether it looks reasonable.

### What It Means For Your Farm
2-4 short bullets on what the chosen inputs will do for the crop and why the quantities, field conditions, and budget fit this land, crop, and season. Name the products.

### Practical Advice
2-4 specific, doable actions grounded in this quotation (confirm price and stock with the store, adjust quantities if the land plan changed, ask about a bulk discount, get ready for the stated weather risk). Never invent external prices.

### Next Move
One closing sentence with the single best next step — usually contacting the store to confirm and lock in the price before the quote expires.

FORMATTING RULES:
1. Use **bold** for money amounts, quantities, and product names.
2. Short, scannable bullets — no markdown tables, no price-per-item listings.
3. Never include a [SUGGEST:...] line.
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->connectTimeout(5)->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'openai/gpt-oss-20b',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
                ],
                'temperature' => 0.4,
                'max_tokens' => 4096,
                'reasoning_effort' => 'low',
            ]);

            if ($response->failed()) {
                return response()->json([
                    'overview' => $fallback,
                    'source' => 'fallback',
                    'metrics' => $context,
                ]);
            }

            $overview = trim((string) $response->json('choices.0.message.content', ''));

            return response()->json([
                'overview' => $overview !== '' ? $overview : $fallback,
                'source' => $overview !== '' ? 'ai' : 'fallback',
                'metrics' => $context,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'overview' => $fallback,
                'source' => 'fallback',
                'metrics' => $context,
            ]);
        }
    }

    /**
     * Deterministic "smart response" shown when the AI service is unavailable.
     * Built entirely from the quotation's own data so it is always truthful.
     *
     * This text is what farmers read on a slow night with no model to reach,
     * so it carries the same job as the AI version — plain language, the
     * field conditions and budget story included — not a stub.
     */
    private function buildFarmerSmartFallback(Quotation $quotation, array $context): string
    {
        $total = (float) $quotation->estimated_total_cost;
        $landArea = (float) $quotation->land_area;
        $perHectare = $landArea > 0 ? round($total / $landArea, 2) : $total;

        $items = $context['items'];
        $count = $items->count();
        $names = $items->pluck('name')->filter()->values();
        $subject = $count === 0
            ? 'this plan'
            : ($count === 1
                ? '**'.$names->first().'**'
                : '**'.$names->implode('**, **').'**');

        $soilLabel = $quotation->soil_type
            ? CostQuotationEngine::getSoilTypeLabel($quotation->soil_type)
            : null;
        $riskLabel = $quotation->weather_context
            ? CostQuotationEngine::getWeatherContextLabel($quotation->weather_context)
            : null;

        $deferred = $quotation->items->where('is_deferred', true);
        $budgetCap = $quotation->budget_cap !== null ? (float) $quotation->budget_cap : null;

        $lines = '### AI Takeaway'."\n"
            .'Your **'.$quotation->crop_type.'** '.ucfirst($quotation->season).'-season plan for **'
            .$landArea.' ha** comes to **₱'.number_format($total, 2)
            .'** — about **₱'.number_format($perHectare, 2).' per hectare** — for '.$subject.'.'."\n\n"
            .'### What It Means For Your Farm'."\n";

        if ($count === 0) {
            $lines .= '- No inputs matched this crop and season in the catalog, so the total is not a full plan yet.'."\n";
        } else {
            $lines .= '- You chose '.$subject.' to cover your input needs for '.$landArea.' ha, priced at the rates set for this crop and season.'."\n";
            $lines .= '- At **₱'.number_format($perHectare, 2).' per hectare**, the plan scales with your land — twice the area, twice these inputs.'."\n";
            if ($soilLabel) {
                $lines .= '- Your **'.$soilLabel.'** soil setting is already factored into the quantities above.'."\n";
            }
            if ($riskLabel) {
                $lines .= '- The plan is sized for a **'.$riskLabel.'** season.'."\n";
            }
            if ($budgetCap !== null) {
                if ($total == 0 && $deferred->isNotEmpty()) {
                    $lines .= '- Your **₱'.number_format($budgetCap, 2).'** budget is below what this plan needs, so nothing was included — a slightly larger budget, or a smaller area, would make the plan work.'."\n";
                } elseif ($deferred->isNotEmpty()) {
                    $lines .= '- Your **₱'.number_format($budgetCap, 2).'** budget kept what fits, and **'.$deferred->count().'** input(s) worth **₱'.number_format((float) $deferred->sum('subtotal'), 2).'** are set aside on the quotation to add when the budget allows.'."\n";
                } elseif ($total <= $budgetCap) {
                    $lines .= '- You set a **₱'.number_format($budgetCap, 2).'** budget and the whole plan fits inside it, with **₱'.number_format($budgetCap - $total, 2).'** left over.'."\n";
                } else {
                    $lines .= '- Your **₱'.number_format($budgetCap, 2).'** budget is below what the plan comes to, so the total runs **₱'.number_format($total - $budgetCap, 2).'** past it.'."\n";
                }
            }
            if ($context['valid_until']) {
                $lines .= '- The prices hold only until **'.$context['valid_until'].'** — confirm before then.'."\n";
            }
        }

        $storeText = $context['stores']->isNotEmpty()
            ? implode(', ', $context['stores']->all())
            : 'the general catalog';

        $lines .= "\n".'### Practical Advice'."\n"
            .'- Run the numbers past **'.$storeText.'** — confirm the listed price and stock, since prices can move once the quote expires.'."\n"
            .'- Match the quantities to your real planting plan; whole bags and bottles are rounded up, so you may end up with a little extra.'."\n"
            .'- It never hurts to ask about a volume discount or free delivery if you are buying for the whole season.'."\n\n"
            .'### Next Move'."\n"
            .'Message '.($context['stores']->isNotEmpty() ? 'the store' : 'the catalog')
            .' to confirm availability and lock in the price'
            .($context['valid_until'] ? ' before **'.$context['valid_until'].'** passes' : ' while it holds')
            .'.';

        return $lines;
    }

    /**
     * Show the farmer the quotation request form.
     */
    public function farmerCreate()
    {
        // Crop choices come from the Sorsogon crop library; formula crop types
        // are merged so legacy/custom crops stay selectable.
        $predefinedCrops = app(CropLibraryService::class)->names();

        $dbCrops = DecisionFormula::select('crop_type')
            ->distinct()
            ->pluck('crop_type')
            ->toArray();

        $allCrops = collect($predefinedCrops)
            ->merge($dbCrops)
            ->unique(fn ($item) => strtolower(trim($item)))
            ->values();

        return view('farmer.quotations.request', ['cropTypes' => $allCrops]);
    }

    /**
     * AJAX endpoint: return products linked to a crop type + season.
     */
    public function farmerProducts(Request $request)
    {
        $validated = $request->validate([
            'crop_type' => ['required', 'string', 'max:255'],
            'season' => ['required', 'in:wet,dry'],
        ]);

        $cropType = ucwords(strtolower(trim($validated['crop_type'])));

        $products = Product::select('products.id', 'products.name', 'products.sku', 'products.price', 'products.price_unit', 'products.stock_quantity', 'products.stock_unit', 'products.brand', 'products.status', 'products.category_id', 'products.package_size', 'products.package_unit')
            ->join('decision_formulas', 'decision_formulas.product_id', '=', 'products.id')
            // Both sides must be lowercased: relying on MySQL's case-insensitive
            // collation makes the match silently fail on any case-sensitive
            // engine (sqlite in tests, Postgres in production-like setups).
            ->whereRaw('LOWER(decision_formulas.crop_type) = ?', [strtolower($cropType)])
            ->where('decision_formulas.season', $validated['season'])
            ->where('products.status', 'active')
            ->with('category:id,name')
            ->distinct()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->display_name,
                'sku' => $p->sku,
                'price' => $p->price,
                'price_unit' => $p->price_unit,
                'stock_quantity' => $p->stock_quantity,
                'stock_unit' => $p->stock_unit,
                'brand' => $p->brand,
                'category' => $p->category_display_name,
                'in_stock' => $p->stock_quantity > 0,
            ]);

        return response()->json($products);
    }

    /**
     * Farmer submits → auto-computed instantly via CostQuotationEngine.
     * No store approval needed. The estimate is immediately available.
     */
    public function farmerStore(Request $request)
    {
        $validated = $request->validate([
            'crop_type' => ['required', 'string', 'max:255'],
            'season' => ['required', 'in:wet,dry'],
            'land_area' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'soil_type' => ['nullable', 'in:clay_loam,sandy,peat'],
            'weather_context' => ['nullable', 'in:normal,low_rain,typhoon,el_nino'],
            'budget_cap' => ['nullable', 'numeric', 'min:1', 'max:99999999.99'],
            'budget_strict' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'selected_product_ids' => ['nullable', 'string'],
        ]);

        $cropType = ucwords(strtolower(trim($validated['crop_type'])));

        // Parse selected product IDs
        $selectedProductIds = [];
        if (! empty($validated['selected_product_ids'])) {
            $decoded = json_decode($validated['selected_product_ids'], true);
            if (is_array($decoded) && count($decoded) > 0) {
                $selectedProductIds = array_map('intval', $decoded);
                $existingIds = Product::whereIn('id', $selectedProductIds)->pluck('id')->toArray();
                $selectedProductIds = array_values(array_intersect($selectedProductIds, $existingIds));
            }
        }

        // Use CostQuotationEngine to auto-compute the full quotation
        $engine = new CostQuotationEngine;

        try {
            $quotation = $engine->calculateAndSave([
                'crop_type' => $cropType,
                'season' => $validated['season'],
                'land_area' => $validated['land_area'],
                'weather_context' => $validated['weather_context'] ?? 'normal',
                'soil_type' => $validated['soil_type'] ?? 'clay_loam',
                'budget_cap' => isset($validated['budget_cap']) ? (float) $validated['budget_cap'] : null,
                'budget_strict' => ! empty($validated['budget_strict']),
                'selected_products' => ! empty($selectedProductIds) ? $selectedProductIds : null,
                'notes' => $validated['notes'] ?? null,
            ], Auth::user());
        } catch (\Exception $e) {
            // Fallback: create a basic quotation without items if engine fails
            $quotation = DB::transaction(function () use ($validated, $cropType) {
                $q = Quotation::create([
                    'user_id' => Auth::id(),
                    'quotation_number' => 'QT-'.date('Y').'-'.strtoupper(Str::random(6)),
                    'crop_type' => $cropType,
                    'season' => $validated['season'],
                    'soil_type' => $validated['soil_type'] ?? 'clay_loam',
                    'weather_context' => $validated['weather_context'] ?? 'normal',
                    'land_area' => $validated['land_area'],
                    'budget_cap' => isset($validated['budget_cap']) ? (float) $validated['budget_cap'] : null,
                    'estimated_total_cost' => 0,
                    'status' => Quotation::STATUS_COMPUTED,
                    'valid_until' => now()->addDays(14),
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Try to add items from DecisionFormula manually. Grouped by
                // product so a multi-stage rule lands as one item with the
                // stages summed, not one item per stage.
                $formulas = DecisionFormula::with('product')
                    ->whereCrop($cropType)
                    ->where('season', $validated['season'])
                    ->get()
                    ->groupBy('product_id');

                if ($formulas->isNotEmpty()) {
                    $totalCost = 0;
                    foreach ($formulas as $stages) {
                        $product = $stages->first()->product;
                        if ($product && $product->status === 'active') {
                            $rate = (float) $stages->sum(fn ($f) => $f->effectiveRate());
                            $qty = round($rate * (float) $validated['land_area'], 2);
                            $price = (float) $product->price;
                            $subtotal = round($qty * $price, 2);

                            $q->items()->create([
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'price_at_generation' => $price,
                                'calculated_quantity' => $qty,
                                'subtotal' => $subtotal,
                            ]);
                            $totalCost += $subtotal;
                        }
                    }
                    $q->update(['estimated_total_cost' => $totalCost]);
                }

                return $q;
            });
        }

        ActivityLogger::quotationCreated($quotation);

        return redirect()
            ->route('farmer.quotations.show', $quotation)
            ->with('success', 'Your cost estimate is ready! Here is a plain-language summary - you can open the full itemized breakdown anytime. Store owners will also see this as demand intel for their products.');
    }

    // ────────────────────────────────────────────────────────────────
    //  FARMER — Activity Log
    // ────────────────────────────────────────────────────────────────

    /**
     * Show the farmer's activity history with smart filters
     * (search, date period, and activity type).
     */
    public function farmerActivity()
    {
        $search = request('q');
        $period = request('period', 'all');
        $from = request('from');
        $to = request('to');
        $type = request('type');

        $userId = Auth::id();
        $today = now()->endOfDay();

        // ── Date range resolution ──
        // Custom From/To dates take priority over the period preset; otherwise the
        // preset period defines the range. Both are sanitised (no future, no swapped
        // order) so the query can never look into the future or return illogical data.
        $fromDate = null;
        $toDate = null;

        $parseDate = function ($value) {
            if (! $value || strtotime($value) === false) {
                return null;
            }

            return Carbon::parse($value);
        };

        if ($from && ($parsedFrom = $parseDate($from)) && $parsedFrom->startOfDay()->lte($today)) {
            $from = $parsedFrom->toDateString();
            $fromDate = $parsedFrom->startOfDay();
        } else {
            $from = null;
        }

        if ($to && ($parsedTo = $parseDate($to))) {
            // Never search beyond today (future activity can't exist).
            $toDate = $parsedTo->startOfDay()->gt($today) ? $today : $parsedTo->endOfDay();
            $to = $toDate->toDateString();
        } else {
            $to = null;
        }

        // If a custom range is inverted (from > to), swap so from <= to.
        if ($fromDate && $toDate && $fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
            $from = $fromDate->toDateString();
            $to = $toDate->toDateString();
        }

        // ── Decide the effective start/end for the query ──
        $start = $fromDate;
        $end = $toDate;

        // Period preset only applies when the user hasn't chosen custom dates.
        if (in_array($period, ['today', '7days', '30days', 'month'], true) && ! $from && ! $to) {
            [$periodStart, $periodEnd] = match ($period) {
                'today' => [now()->startOfDay(), now()->endOfDay()],
                '7days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
                '30days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
                'month' => [now()->startOfMonth(), now()->endOfMonth()],
            };
            $start = $periodStart;
            $end = $periodEnd;
            $from = $periodStart->toDateString();
            $to = $periodEnd->toDateString();
        } elseif ($period !== 'all' && ! $from && ! $to) {
            // Invalid preset — fall back to no period filter.
            $period = 'all';
        }

        // ── Build the filtered query ──
        $query = ActivityLog::where('user_id', $userId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($start) {
            $query->where('created_at', '>=', $start);
        }
        if ($end) {
            $query->where('created_at', '<=', $end);
        }

        if ($type) {
            $query->where('type', $type);
        }

        // subject: the event-status accessors resolve each row's subject
        // (and rebuild state for deleted events), so load them in one query.
        $activities = $query->with('subject')->latest()->paginate(10)->withQueryString();

        // ── Aggregate stats from the farmer's FULL (unfiltered) history ──
        // So the summary cards reflect their real totals regardless of active filters.
        $statsBase = ActivityLog::where('user_id', $userId);
        $totalQuotations = (clone $statsBase)->where('type', ActivityLog::TYPE_QUOTATION_CREATED)->count();
        $totalCrops = (clone $statsBase)->whereIn('type', [
            ActivityLog::TYPE_CROP_PLANTED,
            ActivityLog::TYPE_CROP_UPDATED,
            ActivityLog::TYPE_CROP_HARVESTED,
            ActivityLog::TYPE_CROP_DELETED,
        ])->count();
        $totalDeleted = (clone $statsBase)->where('type', ActivityLog::TYPE_QUOTATION_DELETED)->count();
        $totalLogins = (clone $statsBase)->where('type', ActivityLog::TYPE_LOGIN)->count();

        $uniqueTypes = ActivityLog::where('user_id', $userId)
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->sort()
            ->values()
            ->toArray();

        return view('farmer.activity.index', compact(
            'activities',
            'search',
            'period',
            'from',
            'to',
            'type',
            'uniqueTypes',
            'totalQuotations',
            'totalCrops',
            'totalDeleted',
            'totalLogins'
        ));
    }

    // ────────────────────────────────────────────────────────────────
    //  STORE OWNER — Demand Intelligence Dashboard
    // ────────────────────────────────────────────────────────────────

    /**
     * Show quotations that involve this store's products (demand visibility).
     */
    public function storeIndex()
    {
        $storeUserId = Auth::id();

        // Full dataset for metrics and recent activity
        $allQuotations = Quotation::involvingStore($storeUserId)
            ->with(['farmer', 'items.product'])
            ->orderByRaw("FIELD(status, 'computed', 'viewed', 'expired')")
            ->latest()
            ->get();

        // Only the items that belong to this store (demand value attributable to this store)
        $storeItems = $allQuotations->pluck('items')
            ->flatten()
            ->filter(fn ($item) => $item->product && $item->product->user_id === $storeUserId);

        // Demand metrics
        $totalQuotations = $allQuotations->count();
        $uniqueFarmers = $allQuotations->pluck('user_id')->filter()->unique()->count();

        $recentQuotations = $allQuotations->where('created_at', '>=', now()->subDays(7))->count();
        $priorQuotations = $allQuotations->filter(fn ($q) => $q->created_at->lt(now()->subDays(7)) && $q->created_at->gte(now()->subDays(14))
        )->count();
        $weekTrend = $priorQuotations > 0
            ? (int) round(($recentQuotations - $priorQuotations) / $priorQuotations * 100)
            : ($recentQuotations > 0 ? 100 : 0);

        $ownDemandValue = $storeItems->sum('subtotal');
        $demandProductCount = $storeItems->pluck('product_id')->unique()->count();

        // Products from this store that appear in quotations
        $productDemand = $storeItems
            ->groupBy('product_name')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'total_qty' => $items->sum('calculated_quantity'),
                'total_value' => $items->sum('subtotal'),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->toArray();

        // Recent farmer activity (top 8)
        $recentActivity = $allQuotations->take(8);

        // Full dataset for the main table
        $quotations = Quotation::involvingStore($storeUserId)
            ->with(['farmer', 'items.product'])
            ->orderByRaw("FIELD(status, 'computed', 'viewed', 'expired')")
            ->latest()
            ->get();

        return view('store.quotations.index', compact(
            'quotations',
            'recentActivity',
            'totalQuotations',
            'uniqueFarmers',
            'recentQuotations',
            'priorQuotations',
            'weekTrend',
            'ownDemandValue',
            'demandProductCount',
            'productDemand'
        ));
    }

    /**
     * Read-only view of a quotation for the store owner (demand intel).
     */
    public function storeShow(Quotation $quotation)
    {
        // Verify this store's products are in the quotation
        $involvedStoreIds = $quotation->getInvolvedStoreIds();
        if (! in_array(Auth::id(), $involvedStoreIds)) {
            abort(403, 'You do not have access to this quotation.');
        }

        $quotation->load(['farmer', 'items.product.storeOwner']);

        // Mark as viewed by this store (first view only, records the timestamp)
        if ($quotation->status === Quotation::STATUS_COMPUTED) {
            $quotation->update([
                'status' => Quotation::STATUS_VIEWED,
                'viewed_at' => $quotation->viewed_at ?? now(),
            ]);
            ActivityLogger::quotationViewed($quotation);
        }

        // Get this store's items specifically
        $storeItems = $quotation->items->filter(fn ($item) => $item->product && $item->product->user_id === Auth::id()
        );

        return view('store.quotations.show', compact('quotation', 'storeItems'));
    }

    /**
     * Generate a complete AI demand intelligence report for this store (Groq API).
     */
    public function storeAiOverview(Request $request)
    {
        abort_unless($request->user()?->role === 'store_owner', 403);

        $storeUserId = Auth::id();

        $quotations = Quotation::involvingStore($storeUserId)
            ->with(['items.product'])
            ->latest()
            ->get();

        $storeItems = $quotations->pluck('items')
            ->flatten()
            ->filter(fn ($item) => $item->product && $item->product->user_id === $storeUserId);

        $recentQuotations = $quotations->where('created_at', '>=', now()->subDays(7))->count();
        $priorQuotations = $quotations->filter(fn ($q) => $q->created_at->lt(now()->subDays(7)) && $q->created_at->gte(now()->subDays(14))
        )->count();

        $topProducts = $storeItems
            ->groupBy('product_name')
            ->map(fn ($items) => [
                'name' => $items->first()->product_name,
                'count' => $items->count(),
                'qty' => round($items->sum('calculated_quantity'), 1),
                'value' => round($items->sum('subtotal'), 2),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values()
            ->toArray();

        $topCrops = $quotations
            ->groupBy('crop_type')
            ->map(fn ($qs, $crop) => [
                'crop' => $crop,
                'count' => $qs->count(),
                'area' => $qs->sum('land_area'),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->toArray();

        $context = [
            'store' => Auth::user()->name,
            'total_signals' => $quotations->count(),
            'unique_farmers' => $quotations->pluck('user_id')->filter()->unique()->count(),
            'this_week' => $recentQuotations,
            'last_week' => $priorQuotations,
            'own_products_value' => round($storeItems->sum('subtotal'), 2),
            'signals_total_value' => round($quotations->sum('estimated_total_cost'), 2),
            'top_products' => $topProducts,
            'top_crops' => $topCrops,
        ];

        // Deterministic report first: the store always gets a real report.
        // No key, dead network, or rejected call all land here instead of
        // an error the page would have to explain away.
        $fallback = $this->buildStoreSmartFallback($context);

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json([
                'overview' => $fallback,
                'source' => 'fallback',
                'metrics' => $context,
            ]);
        }

        $system = 'You are a senior business analyst for store owners in the Aquilala Irrigators Association (Bulan, Sorsogon, Philippines). Farmers generate cost quotations and stores see them as "demand signals" - demand visibility, no action required. Write a COMPLETE demand intelligence report using ONLY the JSON telemetry provided. Report total_signals, this_week and last_week EXACTLY as given - this_week/last_week are the last 7 and previous 7 days respectively, distinct from total_signals. Never invent numbers.

STRUCTURE YOUR RESPONSE WITH THESE SECTIONS (use markdown headings exactly as shown):
### AI Analyst Takeaway
ONE punchy sentence that sums up the demand picture.

### Demand Snapshot
4-6 short bullet points (- ) covering: total demand signals and farmer interest, week-over-week change, total quoted value, and one practical read on momentum.

### Top Products & Crop Trends
Short bullets on your top 3-6 products (quote quantities and PHP peso values) and top crops (quote land area), plus 1 sentence of trend context.

### Product Recommendations
Recommend 2-4 specific products this store should stock or restock based on the demand data. Use a small markdown table: Product | Est. Weekly Demand | Suggested Action. Add 2 short bullet takeaways if useful.

### Next Step
ONE closing sentence with a practical next action (e.g. restock, price check, or watch a crop).

FORMATTING RULES:
1. Use **bold** for key numbers, product names, and crops.
2. Short, scannable content - no emojis, no overly long paragraphs.
3. Keep table cells brief and single-line.
4. At the very end of your reply, on its own line, add product keywords for the recommendations EXACTLY in this format (2-4 short generic keywords, comma-separated):
[SUGGEST:urea, complete fertilizer, 14-14-14]';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->connectTimeout(5)->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'openai/gpt-oss-20b',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
                ],
                'temperature' => 0.4,
                'max_tokens' => 8192,
                'reasoning_effort' => 'low',
            ]);

            $overview = $response->failed()
                ? ''
                : trim((string) $response->json('choices.0.message.content', ''));

            if ($overview === '') {
                return response()->json([
                    'overview' => $fallback,
                    'source' => 'fallback',
                    'metrics' => $context,
                ]);
            }

            return response()->json([
                'overview' => $overview,
                'source' => 'ai',
                'metrics' => $context,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'overview' => $fallback,
                'source' => 'fallback',
                'metrics' => $context,
            ]);
        }
    }

    /**
     * Deterministic demand report shown when the model provider is
     * unavailable. Built only from metrics computed above, in the same
     * section layout as the AI report so the page renders identically —
     * and only claiming what the numbers actually say.
     */
    private function buildStoreSmartFallback(array $context): string
    {
        $signals = (int) $context['total_signals'];
        $farmers = (int) $context['unique_farmers'];
        $thisWeek = (int) $context['this_week'];
        $lastWeek = (int) $context['last_week'];
        $quotedValue = (float) $context['signals_total_value'];
        $ownValue = (float) $context['own_products_value'];
        $topProducts = collect($context['top_products']);
        $topCrops = collect($context['top_crops']);

        if ($signals === 0) {
            return '### AI Analyst Takeaway'."\n"
                .'No farmer demand signals have reached your store yet — this report fills in as farmers generate quotations.'."\n\n"
                .'### Demand Snapshot'."\n"
                .'- **0** demand signals from **0** farmers so far, with nothing to compare week-over-week yet.'."\n\n"
                .'### Top Products & Crop Trends'."\n"
                .'- Nothing quoted yet — product and crop trends appear here once signals arrive.'."\n\n"
                .'### Product Recommendations'."\n"
                .'- Share your store with association farmers; their quotations become your demand forecast.'."\n\n"
                .'### Next Step'."\n"
                .'Let farmers know your store is listed so their quotation requests start landing here.';
        }

        if ($lastWeek > 0) {
            $delta = $thisWeek - $lastWeek;
            $pct = (int) round(abs($delta) / $lastWeek * 100);
            $trend = $delta > 0
                ? 'up **'.$pct.'%** week-over-week'
                : ($delta < 0 ? 'down **'.$pct.'%** week-over-week' : 'level with last week');
        } elseif ($thisWeek > 0) {
            $trend = 'with no prior week on record to compare against';
        } else {
            $trend = 'quiet this week';
        }

        $lines = '### AI Analyst Takeaway'."\n"
            .'**'.$signals.'** demand signals from **'.$farmers.'** farmer(s), worth **₱'.number_format($quotedValue, 2).'** in quoted value.'."\n\n"
            .'### Demand Snapshot'."\n"
            .'- This week brought **'.$thisWeek.'** new signals vs **'.$lastWeek.'** last week — '.$trend.'.'."\n"
            .'- Farmers quoted **₱'.number_format($quotedValue, 2).'** overall; **₱'.number_format($ownValue, 2).'** of that sits on your own products.'."\n";

        if ($topProducts->isNotEmpty()) {
            $lines .= '- Most quoted right now: **'.$topProducts->first()['name'].'** ('.$topProducts->first()['count'].' quotation(s)).'."\n";
        }

        $lines .= "\n".'### Top Products & Crop Trends'."\n";
        if ($topProducts->isEmpty() && $topCrops->isEmpty()) {
            $lines .= '- No product or crop activity in the data yet.'."\n";
        } else {
            foreach ($topProducts->take(5) as $product) {
                $lines .= '- **'.$product['name'].'** — '.$product['count'].' quote(s), ₱'.number_format((float) $product['value'], 2).' quoted.'."\n";
            }
            foreach ($topCrops->take(3) as $crop) {
                $lines .= '- **'.$crop['crop'].'** — '.$crop['count'].' quotation(s) covering '.round((float) $crop['area'], 2).' ha.'."\n";
            }
        }

        $lines .= "\n".'### Product Recommendations'."\n";
        if ($topProducts->isEmpty()) {
            $lines .= '- No product recommendations yet — they appear once farmers quote your inputs.'."\n";
        } else {
            foreach ($topProducts->take(3) as $product) {
                $lines .= '- Keep **'.$product['name'].'** well stocked — '.$product['count'].' farmer quote(s) already lean on it.'."\n";
            }
        }

        $lines .= "\n".'### Next Step'."\n"
            .($topProducts->isNotEmpty()
                ? 'Check stock on **'.$topProducts->first()['name'].'** first — it is what farmers ask for most.'
                : 'Share your store with association farmers so demand signals start arriving.');

        $keywords = $topProducts->take(3)
            ->map(fn ($product) => trim(str_replace(',', '', strtolower((string) $product['name']))))
            ->filter()
            ->implode(', ');

        if ($keywords !== '') {
            $lines .= "\n".'[SUGGEST:'.$keywords.']';
        }

        return $lines;
    }

    // ────────────────────────────────────────────────────────────────
    //  ADMIN — Demand Analytics & Association Performance
    // ────────────────────────────────────────────────────────────────

    /**
     * Aggregate product & crop demand across every estimate.
     * De-identified: products/crops only, no store or farmer identity.
     *
     * @return array{quotations: Collection, product: Collection, crop: Collection}
     */
    private function aggregateDemand(): array
    {
        $quotations = Quotation::with(['items.product.category'])
            ->latest()
            ->get();

        $productDemand = [];
        $cropDemand = [];

        foreach ($quotations as $q) {
            $crop = $q->crop_type ?: 'Unspecified';
            if (! isset($cropDemand[$crop])) {
                $cropDemand[$crop] = [
                    'crop' => $crop,
                    'count' => 0,
                    'total_area' => 0.0,
                    'total_value' => 0.0,
                ];
            }
            $cropDemand[$crop]['count'] += 1;
            $cropDemand[$crop]['total_area'] += (float) $q->land_area;
            $cropDemand[$crop]['total_value'] += (float) $q->estimated_total_cost;

            foreach ($q->items as $item) {
                if (! $item->product) {
                    continue;
                }

                $pid = $item->product_id;
                if (! isset($productDemand[$pid])) {
                    $productDemand[$pid] = [
                        'name' => $item->product->name,
                        'display_name' => $item->product->display_name,
                        'category' => $item->product->category_display_name,
                        'unit' => $item->product->stock_unit ?? $item->product->package_unit ?? 'units',
                        'quotation_ids' => [],
                        'total_qty' => 0.0,
                        'total_value' => 0.0,
                        'crops' => [],
                    ];
                }

                $productDemand[$pid]['quotation_ids'][$q->id] = true;
                $productDemand[$pid]['total_qty'] += (float) $item->calculated_quantity;
                $productDemand[$pid]['total_value'] += (float) $item->subtotal;
                if ($crop && ! in_array($crop, $productDemand[$pid]['crops'], true)) {
                    $productDemand[$pid]['crops'][] = $crop;
                }
            }
        }

        // Ranked most-quoted first. The summary page slices the top 5 for its
        // chart; this full list backs the dedicated "All Products" page.
        $product = collect($productDemand)
            ->map(function ($p) {
                $p['quotation_count'] = count($p['quotation_ids']);
                unset($p['quotation_ids']);
                $p['crops'] = collect($p['crops'])->filter()->join(', ');

                return $p;
            })
            ->sortByDesc('quotation_count')
            ->values();

        $crop = collect($cropDemand)
            ->sortByDesc('count')
            ->values();

        return compact('quotations', 'product', 'crop');
    }

    /**
     * Crops farmers have actually planned or planted, rolled up from their own
     * crop batches. This is the supply side to crop *demand*, which is measured
     * from the estimates stores requested. De-identified to the crop level —
     * only a distinct farmer count is kept, never an identity.
     */
    private function aggregatePlanting(): Collection
    {
        return FarmerCrop::with('crop')
            ->get()
            ->groupBy(fn ($batch) => $batch->crop?->name ?: 'Unspecified')
            ->map(function ($batches, $crop) {
                return [
                    'crop' => $crop,
                    'batches' => $batches->count(),
                    // Still in the ground: prepared, sown, growing or ripening.
                    'active' => $batches->whereNotIn('growth_stage', ['harvested', 'failed'])->count(),
                    'harvested' => $batches->where('growth_stage', 'harvested')->count(),
                    'farmers' => $batches->pluck('user_id')->unique()->count(),
                    'total_area' => (float) $batches->sum('land_area'),
                ];
            })
            ->sortByDesc('total_area')
            ->values();
    }

    /**
     * Join demand and planting into one ranked crop list so the table can show
     * every crop once, with both sides of the story side by side.
     */
    private function mergeCropViews(Collection $crop, Collection $planting): Collection
    {
        return $crop->pluck('crop')
            ->union($planting->pluck('crop'))
            ->filter()
            ->unique()
            ->map(function ($name) use ($crop, $planting) {
                $q = $crop->firstWhere('crop', $name);
                $p = $planting->firstWhere('crop', $name);

                return [
                    'crop' => $name,
                    'estimates' => (int) ($q['count'] ?? 0),
                    'quoted_area' => (float) ($q['total_area'] ?? 0),
                    'quoted_value' => (float) ($q['total_value'] ?? 0),
                    'batches' => (int) ($p['batches'] ?? 0),
                    'active' => (int) ($p['active'] ?? 0),
                    'farmers' => (int) ($p['farmers'] ?? 0),
                    'planted_area' => (float) ($p['total_area'] ?? 0),
                ];
            })
            ->sortByDesc('estimates')
            ->sortByDesc('planted_area')
            ->values();
    }

    /**
     * Admin view: product & crop demand analytics, de-identified.
     */
    public function adminIndex()
    {
        ['quotations' => $allQuotations, 'product' => $allProducts, 'crop' => $cropDemand] = $this->aggregateDemand();

        // Global metrics (expired is derived from valid_until, not the stored status)
        $metrics = [
            'total' => $allQuotations->count(),
            'computed' => $allQuotations->filter(fn ($q) => ! $q->is_expired && $q->status === Quotation::STATUS_COMPUTED)->count(),
            'viewed' => $allQuotations->filter(fn ($q) => ! $q->is_expired && $q->status === Quotation::STATUS_VIEWED)->count(),
            'expired' => $allQuotations->filter(fn ($q) => $q->is_expired)->count(),
            'totalValue' => $allQuotations->sum('estimated_total_cost'),
        ];

        // Both charts plot only the top 5; the table underneath carries the rest.
        $productTotal = $allProducts->count();
        $productDemand = $allProducts->take(5)->toArray();

        $planting = $this->aggregatePlanting();
        $cropRows = $this->mergeCropViews($cropDemand, $planting);

        return view('admin.quotations.index', [
            'metrics' => $metrics,
            'productDemand' => $productDemand,
            'productTotal' => $productTotal,
            'cropRows' => $cropRows->toArray(),
            'cropTotal' => $cropRows->count(),
            'plantingTotal' => $planting->sum('batches'),
        ]);
    }

    /**
     * Admin view: the complete ranked product demand list behind the
     * "Show all products" link on the Demand Trends page. The graph still only
     * plots the top 5 — everything below it is the table.
     */
    public function adminProducts()
    {
        ['product' => $allProducts] = $this->aggregateDemand();

        $products = $allProducts->toArray();
        $topProducts = $allProducts->take(5)->toArray();

        $summary = [
            'total' => count($products),
            'estimates' => array_sum(array_column($products, 'quotation_count')),
            'totalQty' => array_sum(array_column($products, 'total_qty')),
            'totalValue' => array_sum(array_column($products, 'total_value')),
        ];

        return view('admin.quotations.products', compact('products', 'topProducts', 'summary'));
    }

    // ────────────────────────────────────────────────────────────────
    //  ADMIN — Activity Audit Log (all users)
    // ────────────────────────────────────────────────────────────────

    /**
     * Admin view: all user activities across the system.
     */
    public function adminActivity()
    {
        // Filter options
        $userFilter = request('user_id');
        $typeFilter = request('type');

        $query = ActivityLog::with(['user', 'actor']);

        if ($userFilter) {
            // Match either side of the row: the actor, or the recipient a
            // notification-style row was filed under.
            $query->where(function ($q) use ($userFilter) {
                $q->where('user_id', $userFilter)
                    ->orWhere('actor_id', $userFilter);
            });
        }
        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }

        $activities = $query->latest()->paginate(10)->withQueryString();

        // For the filter dropdown
        $users = User::orderBy('name')->get();
        $activityTypes = ActivityLog::select('type')->distinct()->pluck('type')->sort()->toArray();

        return view('admin.activity.index', compact('activities', 'users', 'activityTypes', 'userFilter', 'typeFilter'));
    }
}
