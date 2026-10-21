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
use App\Services\StoreDemandService;
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
    /**
     * Placeholder that marks where the page should render the product
     * cards. The report arrives as markdown; the products are drawn from
     * the metrics instead, so each one gets its own card.
     */
    private const PRODUCT_CARDS_TOKEN = '[[PRODUCT_CARDS]]';

    public function __construct(private readonly StoreDemandService $storeDemand)
    {
    }

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

        // Active and expired are two different lists. The page the farmer
        // works from is the live one; the estimates whose window has closed
        // wait on the archive page.
        $active = $quotations->filter(fn ($q) => ! $q->is_expired)->values();
        $archivedCount = $quotations->count() - $active->count();

        $totalQuotes = $active->count();
        $computedCount = $active->filter(fn ($q) => $q->status === 'computed')->count();
        $totalValue = $active->sum('estimated_total_cost');

        // The crop filter offers the crops the system knows - the Sorsogon
        // library - not just the ones that happen to be in this farmer's
        // rows, so the picker is the same everywhere.
        $cropOptions = collect(app(CropLibraryService::class)->offeredNames())
            ->map(fn ($crop) => [mb_strtolower($crop), $crop])
            ->all();

        // No "seen by stores" figure: a farmer should not be measured by
        // how often a store looked at their prices.
        return view('farmer.quotations.index', [
            'quotations'     => $active,
            'totalQuotes'    => $totalQuotes,
            'computedCount'  => $computedCount,
            'totalValue'     => $totalValue,
            'cropOptions'    => $cropOptions,
            'archivedCount'  => $archivedCount,
        ]);
    }

    /**
     * The farmer's archived estimates - the ones whose window has closed.
     *
     * They are kept, because the record of what a farmer costed out is
     * theirs. They are not on the working page, because a closed estimate
     * is not something to plan a season with.
     */
    public function farmerArchive()
    {
        $archived = Quotation::with('items.product.storeOwner')
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->filter(fn ($q) => $q->is_expired)
            ->values();

        return view('farmer.quotations.archive', [
            'archived' => $archived,
            'archivedValue' => $archived->sum('estimated_total_cost'),
        ]);
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
     *
     * A withdrawn estimate leaves no trace anywhere. Its rows go with it,
     * so the store's notification list, the admin's copy and the farmer's
     * own history all stop mentioning it: what the store can see is what
     * exists.
     */
    public function farmerDestroy(Quotation $quotation)
    {
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        // Every notice and log line that points at this estimate - the
        // store's anonymous copy, the admin's copy, the farmer's own
        // entries - goes with the estimate itself.
        ActivityLog::where('subject_type', Quotation::class)
            ->where('subject_id', $quotation->id)
            ->delete();

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
            // The window may already have closed. The advisor must know, or
            // it will tell a farmer to go and act on a stale price.
            'is_expired' => (bool) $quotation->is_expired,
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
- If "is_expired" is true, the window has already closed. Say so plainly, in its own short section, and point at generating a fresh estimate for current prices. Do not tell the farmer to go and act on these prices as if they still hold.

STRUCTURE — use these markdown headings exactly:

### AI Takeaway
1-2 sentences: what this quotation means for this farmer's plan — the inputs, the total, the per-hectare cost, and one honest read on whether it looks reasonable.

### What It Means For Your Farm
2-4 short bullets on what the chosen inputs will do for the crop and why the quantities, field conditions, and budget fit this land, crop, and season. Name the products.

### Practical Advice
2-4 specific, doable actions grounded in this quotation (confirm price and stock with the store, adjust quantities if the land plan changed, ask about a bulk discount, get ready for the stated weather risk). Never invent external prices.

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

        // An expired estimate is history, not a plan. Say so plainly, and
        // point at the thing that fixes it, instead of advising the farmer
        // to go and act on a price that has since moved.
        if ($quotation->is_expired) {
            $lines .= "\n".'### This Estimate Has Expired'."\n"
                .'- It closed on **'.$context['valid_until'].'**. The figures above are the ones it was made with — they are kept as they were quoted, and the prices may have moved since.'."\n"
                .'- Generate a new estimate for the same crop and area: it picks up the current prices and stock, and that one is the plan to work from.'."\n";

            return $lines;
        }

        $lines .= "\n".'### Practical Advice'."\n"
            .'- Run the numbers past **'.$storeText.'** — confirm the listed price and stock'
            .($context['valid_until'] ? ' before **'.$context['valid_until'].'** passes' : '')
            .', since prices move once the quote expires.'."\n"
            .'- Match the quantities to your real planting plan; whole bags and bottles are rounded up, so you may end up with a little extra.'."\n"
            .'- It never hurts to ask about a volume discount or free delivery if you are buying for the whole season.';

        return $lines;
    }

    /**
     * Show the farmer the quotation request form.
     */
    public function farmerCreate()
    {
        // One list of the crops the system offers, written once in the
        // Sorsogon crop library.
        $allCrops = app(CropLibraryService::class)->offeredNames();

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
            'valid_days' => ['nullable', 'integer', 'in:3,5,7'],
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
                'valid_days' => (int) ($validated['valid_days'] ?? CostQuotationEngine::DEFAULT_VALID_DAYS),
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
                    'valid_until' => now()->addDays((int) ($validated['valid_days'] ?? CostQuotationEngine::DEFAULT_VALID_DAYS)),
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
            ->orderedByStatusPriority()
            ->latest()
            ->get();

        // Only the items that belong to this store (demand value attributable to this store)
        $storeItems = $allQuotations->pluck('items')
            ->flatten()
            ->filter(fn ($item) => $item->product && $item->product->user_id === $storeUserId);

        // Live demand and history are two different things. An estimate that
        // expired a month ago is still part of the store's record, but it is
        // not what is being asked for today - and a price that has since
        // moved is not a current price. The figures below are the live ones;
        // the expired ones stay in the table and in the history.
        $liveQuotations = $allQuotations->filter(fn ($q) => ! $q->is_expired);
        $liveItems = $liveQuotations->pluck('items')
            ->flatten()
            ->filter(fn ($item) => $item->product && $item->product->user_id === $storeUserId);

        // Demand metrics
        $totalQuotations = $liveQuotations->count();
        $expiredQuotations = $allQuotations->count() - $totalQuotations;
        $uniqueFarmers = $liveQuotations->pluck('user_id')->filter()->unique()->count();

        $recentQuotations = $liveQuotations->where('created_at', '>=', now()->subDays(7))->count();
        $priorQuotations = $liveQuotations->filter(fn ($q) => $q->created_at->lt(now()->subDays(7)) && $q->created_at->gte(now()->subDays(14))
        )->count();

        $ownDemandValue = $liveItems->sum('subtotal');
        $demandProductCount = $liveItems->pluck('product_id')->unique()->count();

        // Products from this store that appear in live quotations
        $productDemand = $liveItems
            ->groupBy('product_name')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'total_qty' => $items->sum('calculated_quantity'),
                'total_value' => $items->sum('subtotal'),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->toArray();

        return view('store.quotations.index', [
            // The quotations tab reuses the dataset the metrics were read
            // from — the same rows, no second query.
            'quotations' => $allQuotations,
            'totalQuotations' => $totalQuotations,
            'expiredQuotations' => $expiredQuotations,
            'uniqueFarmers' => $uniqueFarmers,
            'recentQuotations' => $recentQuotations,
            'priorQuotations' => $priorQuotations,
            'demandProductCount' => $demandProductCount,
            'productDemand' => $productDemand,
        ]);
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

        // Mark as viewed by this store (first view only, records the timestamp).
        // Looking at a price is not an activity worth recording: a farmer
        // must never feel watched for checking what an input costs, and a
        // store looking at an estimate a hundred times should not fill the
        // history with a hundred rows. The estimate's own state still
        // changes; nobody is told about it.
        if ($quotation->status === Quotation::STATUS_COMPUTED) {
            $quotation->update([
                'status' => Quotation::STATUS_VIEWED,
                'viewed_at' => $quotation->viewed_at ?? now(),
            ]);
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

        // One service works the demand figures out, so the report on the page
        // and the answer in the store owner's chat are the same numbers.
        $demand = $this->storeDemand->summary($storeUserId);

        $context = [
            'store' => Auth::user()->name,
            'total_signals' => $demand['total_quotations'],
            'unique_farmers' => $demand['unique_farmers'],
            'this_week' => $demand['this_week'],
            'last_week' => $demand['last_week'],
            'own_products_value' => $demand['own_products_value'],
            'signals_total_value' => $demand['total_value'],
            'top_products' => $demand['top_products'],
            'top_crops' => $demand['top_crops'],
        ];

        // Deterministic report first: the store always gets a real report.
        // No key, dead network, or rejected call all land here instead of
        // an error the page would have to explain away.
        $fallback = $this->shapeStoreOverview($this->buildStoreSmartFallback($context));

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json([
                'overview' => $fallback,
                'source' => 'fallback',
                'metrics' => $context,
            ]);
        }

        $system = 'You are a friendly business adviser for store owners in the Aquilala Irrigators Association (Bulan, Sorsogon, Philippines). Farmers create their own cost quotations; stores simply see the ones that include their products - for information only, no action required and no approval involved. Write a COMPLETE report for the store owner using ONLY the JSON telemetry provided. Report total_signals, this_week and last_week EXACTLY as given - this_week/last_week are the last 7 and previous 7 days respectively, distinct from total_signals. Never invent numbers.

PLAIN LANGUAGE: the reader is an ordinary store owner, not a business analyst. Use short, everyday words. Never use jargon such as "demand signals", "telemetry", "intelligence", "momentum" or "week-over-week" - say "farmer quotations", "this week", "compared with last week" instead. Farmers CREATE quotations on their own; never imply the farmer asked the store for anything or that the store must respond.

STRUCTURE YOUR RESPONSE WITH THESE SECTIONS (use markdown headings exactly as shown):
### What Farmers Quote
4-6 short bullet points (- ) covering: how many quotations were created and by how many farmers, how this week compares with last week, the total peso value of your products in these quotations, and one practical read.

### Your Most Quoted Products
ONE short sentence of context about your most quoted products. Do NOT list the products or repeat their numbers - the page shows every product as its own card, so a list would say twice what the reader can already see.

### Worth Stocking Up
Recommend 2-4 specific products this store should stock or restock based on the quotations. Use a small markdown table: Product | How Often Quoted | Suggested Action. Add 2 short bullet takeaways if useful.

### Next Step
ONE closing sentence with a practical next action (e.g. restock, check prices, or watch a crop).

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
                'overview' => $this->shapeStoreOverview($overview),
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
     * Shape the report for the page it lands on.
     *
     * Two rules, both about how the report reads rather than what it says:
     *   1. The "In One Sentence" opener is dropped — the modal header
     *      already says what the card is, so the line only repeated it.
     *   2. The products section keeps its heading and a line of context,
     *      but the model's product list is replaced by a placeholder:
     *      the page renders every product as its own card, straight from
     *      the metrics, so a list would only say it twice — and less
     *      accurately.
     */
    private function shapeStoreOverview(string $overview): string
    {
        $sections = preg_split('/(?m)^(?=#{1,4}\s)/', $overview);
        $shaped = [];

        foreach ($sections as $section) {
            $section = trim($section);
            if ($section === '') {
                continue;
            }

            if (preg_match('/^#{1,4}\s*In One Sentence\b/i', $section)) {
                continue;
            }

            if (preg_match('/^#{1,4}\s*Your Most Quoted Products\b/i', $section)) {
                $lines = preg_split('/\R/', $section);
                // The heading goes too: the page draws its own above the
                // cards, and two of them would read as a duplicate.
                array_shift($lines);
                $context = [];

                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed === '' || $trimmed === self::PRODUCT_CARDS_TOKEN) {
                        continue;
                    }
                    // A bullet or a table row is the model listing products
                    // in sentences — the cards do that better.
                    if (preg_match('/^([-*+]|\d+[.)])\s+/', $trimmed) || str_starts_with($trimmed, '|')) {
                        continue;
                    }
                    $context[] = $trimmed;
                }

                $piece = $context ? implode("\n", $context) : '';
                $piece = trim($piece . "\n" . self::PRODUCT_CARDS_TOKEN);
                $shaped[] = $piece;

                continue;
            }

            $shaped[] = $section;
        }

        return implode("\n\n", $shaped);
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

        if ($signals === 0) {
            return '### What Farmers Quote'."\n"
                .'- **0** quotations from **0** farmers so far, with nothing to compare against last week yet.'."\n\n"
                .self::PRODUCT_CARDS_TOKEN."\n\n"
                .'### Worth Stocking Up'."\n"
                .'- Share your store with association farmers; their quotations become your guide to what to keep in stock.'."\n\n"
                .'### Next Step'."\n"
                .'Let farmers know your store is listed so their quotations start landing here.';
        }

        if ($lastWeek > 0) {
            $delta = $thisWeek - $lastWeek;
            $pct = (int) round(abs($delta) / $lastWeek * 100);
            $trend = $delta > 0
                ? 'up **'.$pct.'%** compared with last week'
                : ($delta < 0 ? 'down **'.$pct.'%** compared with last week' : 'the same as last week');
        } elseif ($thisWeek > 0) {
            $trend = 'with nothing last week to compare against';
        } else {
            $trend = 'quiet this week';
        }

        $lines = '### What Farmers Quote'."\n"
            .'- This week brought **'.$thisWeek.'** new quotations vs **'.$lastWeek.'** last week — '.$trend.'.'."\n"
            .'- Farmers quoted **₱'.number_format($quotedValue, 2).'** overall; **₱'.number_format($ownValue, 2).'** of that is your own products.'."\n\n";

        // The products are cards on the page, not bullets in a sentence.
        $lines .= '### Your Most Quoted Products'."\n"
            .self::PRODUCT_CARDS_TOKEN."\n\n";

        $lines .= "\n".'### Worth Stocking Up'."\n";
        if ($topProducts->isEmpty()) {
            $lines .= '- Nothing to suggest yet — tips appear once farmers quote your products.'."\n";
        } else {
            foreach ($topProducts->take(3) as $product) {
                $lines .= '- Keep **'.$product['name'].'** well stocked — '.$product['count'].' farmer quotation(s) already lean on it.'."\n";
            }
        }

        $lines .= "\n".'### Next Step'."\n"
            .($topProducts->isNotEmpty()
                ? 'Check stock on **'.$topProducts->first()['name'].'** first — it is what farmers quote most.'
                : 'Share your store with association farmers so their quotations start arriving.');

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
