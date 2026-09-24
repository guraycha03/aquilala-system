<?php

namespace App\Http\Controllers;

use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CostQuotationEngine;
use Illuminate\Http\Request;
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
        $computedCount = $allQuotes->filter(fn ($q) => !$q->is_expired && $q->status === 'computed')->count();
        $viewedCount = $allQuotes->filter(fn ($q) => !$q->is_expired && $q->status === 'viewed')->count();
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
        // calculation breakdown used by the itemized subpage.
        $formulas = DecisionFormula::whereCrop($quotation->crop_type)
            ->where('season', $quotation->season)
            ->get()
            ->keyBy('product_id');

        $total = (float) $quotation->estimated_total_cost;

        // What's driving the cost — top items with % share of the total.
        $topItems = $quotation->items
            ->map(function ($item) use ($formulas, $total) {
                $unit = strtolower(trim($formulas->get($item->product_id)?->unit
                    ?? $item->product?->stock_unit
                    ?? 'unit'));

                return [
                    'product_name' => $item->product_name,
                    'store_name'   => $item->product?->storeOwner?->name,
                    'price'        => (float) $item->price_at_generation,
                    'quantity'     => (float) $item->calculated_quantity,
                    'unit'         => $unit,
                    'subtotal'     => (float) $item->subtotal,
                    'share'        => $total > 0
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

        $formulas = DecisionFormula::whereCrop($quotation->crop_type)
            ->where('season', $quotation->season)
            ->get()
            ->keyBy('product_id');

        return view('farmer.quotations.breakdown', compact('quotation', 'formulas'));
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
                'name'     => $item->product_name,
                'store'    => $item->product?->storeOwner?->name ?? null,
                'price'    => (float) $item->price_at_generation,
                'quantity' => (float) $item->calculated_quantity,
                'unit'     => strtolower(trim($item->product?->stock_unit ?? 'unit')),
                'subtotal' => round((float) $item->subtotal, 2),
            ])
            ->map(fn ($i) => $i['unit'] === 'piece' ? array_merge($i, ['unit' => 'unit']) : $i)
            ->values();

        $total     = (float) $quotation->estimated_total_cost;
        $landArea  = (float) $quotation->land_area;
        $perHectare = $landArea > 0 ? round($total / $landArea, 2) : $total;

        $context = [
            'farm_area'    => $landArea,
            'crop'         => $quotation->crop_type,
            'season'       => $quotation->season,
            'total'        => $total,
            'per_hectare'  => $perHectare,
            'item_count'   => $items->count(),
            'generated'    => $quotation->created_at->format('F d, Y'),
            'valid_until'  => $quotation->valid_until?->format('F d, Y'),
            'items'        => $items,
            'top_items'    => $items->sortByDesc('subtotal')->take(5)->values(),
            'stores'       => $quotation->items
                ->pluck('product.storeOwner.name')
                ->filter()
                ->unique()
                ->values(),
        ];

        $fallback = $this->buildFarmerSmartFallback($quotation, $context);

        $apiKey = config('services.groq.key');

        if (!$apiKey) {
            return response()->json([
                'overview' => $fallback,
                'source'   => 'fallback',
                'metrics'  => $context,
            ]);
        }

        $system = <<<'PROMPT'
You are a senior agronomist-farm advisor for the Aquilala Irrigators Association (Bulan, Sorsogon, Philippines). A farmer generated an auto-computed cost quotation for the farm input(s) they chose for their planned crop. Explain the quotation as plain-language farming guidance focused ONLY on the input(s) they selected. Use ONLY the JSON telemetry given. Never invent numbers, prices, or products.

Write in warm, plain English a farmer would trust. Keep it SHORT and centered on what the farmer chose. Do NOT reproduce a price table and do NOT list every line item with its math — that itemized table is shown separately on this page. Structure your response EXACTLY with these markdown headings:

### AI Takeaway
ONE sentence saying what this quotation means for the farmer's plan: the chosen input(s), the total, and the per-hectare cost, plus one brief observation on whether it looks reasonable.

### What It Means For Your Farm
2-3 short plain-language bullets explaining what the selected input(s) will do for their crop and why the quantity and budget make sense for their land area, crop, and season. Reference the chosen input by name. Do not list prices line by line.

### Practical Advice
2-3 specific, actionable bullets grounded in this quotation (e.g. confirm the price with the store, adjust the quantity if the land plan changed, ask about a bulk discount). Never invent external prices.

### Next Move
ONE closing sentence suggesting the immediate next step (e.g. contact the store to confirm availability and lock in the price before the quote expires).

FORMATTING RULES:
1. Use **bold** for money amounts, quantities, and product names.
2. Keep bullets short and scannable - no emojis, no long paragraphs, no markdown tables, no price-per-item listings.
3. Never include a [SUGGEST:...] line.
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->withoutVerifying()->timeout(90)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'            => 'openai/gpt-oss-20b',
                'messages'         => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
                ],
                'temperature'     => 0.4,
                'max_tokens'      => 4096,
                'reasoning_effort'=> 'low',
            ]);

            if ($response->failed()) {
                return response()->json([
                    'overview' => $fallback,
                    'source'   => 'fallback',
                    'metrics'  => $context,
                ]);
            }

            $overview = trim((string) $response->json('choices.0.message.content', ''));

            return response()->json([
                'overview' => $overview !== '' ? $overview : $fallback,
                'source'   => $overview !== '' ? 'ai' : 'fallback',
                'metrics'  => $context,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'overview' => $fallback,
                'source'   => 'fallback',
                'metrics'  => $context,
            ]);
        }
    }

    /**
     * Deterministic "smart response" shown when the AI service is unavailable.
     * Built entirely from the quotation's own data so it is always truthful.
     */
    private function buildFarmerSmartFallback(Quotation $quotation, array $context): string
    {
        $total      = (float) $quotation->estimated_total_cost;
        $landArea   = (float) $quotation->land_area;
        $perHectare = $landArea > 0 ? round($total / $landArea, 2) : $total;

        $items    = $context['items'];
        $count    = $items->count();
        $names    = $items->pluck('name')->filter()->values();
        $subject  = $count === 0
            ? 'this plan'
            : ($count === 1
                ? '**' . $names->first() . '**'
                : '**' . $names->implode('**, **') . '**');

        $lines = '### AI Takeaway' . "\n"
            . 'Your **' . $quotation->crop_type . '** ' . ucfirst($quotation->season) . '-season plan for **'
            . $landArea . ' ha** comes to **₱' . number_format($total, 2)
            . '** — about **₱' . number_format($perHectare, 2) . ' per hectare** — for ' . $subject . '.' . "\n\n"
            . '### What It Means For Your Farm' . "\n";

        if ($count === 0) {
            $lines .= '- No products matched the formulas for this crop and season, so the total may be incomplete.' . "\n";
        } else {
            $lines .= '- You selected ' . $subject . ' to cover your input needs for ' . $landArea . ' ha.' . "\n";
            $lines .= '- The **₱' . number_format($perHectare, 2) . '/ha** budget already includes everything the formulas recommend for this crop and season.' . "\n";
            if ($context['valid_until']) {
                $lines .= '- The quotation is valid until **' . $context['valid_until'] . '** — the prices are only locked until then.' . "\n";
            }
        }

        $storeText = $context['stores']->isNotEmpty()
            ? implode(', ', $context['stores']->all())
            : 'the general catalog';

        $lines .= "\n" . '### Practical Advice' . "\n"
            . '- Confirm the listed prices and stock with **' . $storeText . '** before buying — prices can move after the quote expires.' . "\n"
            . '- Check the quantities against your actual planting plan; whole bags/sacks are rounded up, so you may end up with extra.' . "\n"
            . '- Ask about volume discounts or free delivery if you need more this season.' . "\n\n"
            . '### Next Move' . "\n"
            . 'Reach out to ' . ($context['stores']->isNotEmpty() ? 'the store' : 'the general catalog')
            . ' to lock in the price before the quotation expires.';

        return $lines;
    }

    /**
     * Show the farmer the quotation request form.
     */
    public function farmerCreate()
    {
        $predefinedCrops = [
            'Rice', 'Corn', 'Wheat',
            'Tomato', 'Eggplant', 'Okra', 'Sitaw (String Beans)', 'Patola (Sponge Gourd)',
            'Ampalaya (Bitter Gourd)', 'Squash (Kalabasa)', 'Cabbage', 'Pechay (Bok Choy)',
            'Lettuce', 'Carrots', 'Radish (Labanos)', 'Turnip', 'Beets',
            'Onions', 'Garlic', 'Ginger', 'Chili (Siling Labuyo)', 'Bell Pepper',
            'Cucumber', 'Sweet Potato (Camote)', 'Taro (Gabi)', 'Potato',
            'Watermelon', 'Papaya', 'Mango', 'Banana', 'Pineapple',
            'Avocado', 'Calamansi', 'Guyabano (Soursop)', 'Rambutan',
            'Durian', 'Lanzones', 'Chico', 'Santol', 'Bayabas (Guava)',
            'Mung Beans (Munggo)', 'Peanuts', 'Soybeans', 'Black-eyed Peas',
            'Cassava (Kamoteng Kahoy)', 'Ube (Purple Yam)',
            'Sugarcane', 'Coffee', 'Cacao', 'Coconut', 'Abaca', 'Tobacco',
        ];

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
            'season'    => ['required', 'in:wet,dry'],
        ]);

        $cropType = ucwords(strtolower(trim($validated['crop_type'])));

        $products = Product::select('products.id', 'products.name', 'products.sku', 'products.price', 'products.price_unit', 'products.stock_quantity', 'products.stock_unit', 'products.brand', 'products.status')
            ->join('decision_formulas', 'decision_formulas.product_id', '=', 'products.id')
            ->whereRaw('LOWER(decision_formulas.crop_type) = ?', [$cropType])
            ->where('decision_formulas.season', $validated['season'])
            ->where('products.status', 'active')
            ->with('category:id,name')
            ->distinct()
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'sku'           => $p->sku,
                'price'         => $p->price,
                'price_unit'    => $p->price_unit,
                'stock_quantity'=> $p->stock_quantity,
                'stock_unit'    => $p->stock_unit,
                'brand'         => $p->brand,
                'category'      => $p->category->name ?? null,
                'in_stock'      => $p->stock_quantity > 0,
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
            'crop_type'          => ['required', 'string', 'max:255'],
            'season'             => ['required', 'in:wet,dry'],
            'land_area'          => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'selected_product_ids' => ['nullable', 'string'],
        ]);

        $cropType = ucwords(strtolower(trim($validated['crop_type'])));

        // Parse selected product IDs
        $selectedProductIds = [];
        if (!empty($validated['selected_product_ids'])) {
            $decoded = json_decode($validated['selected_product_ids'], true);
            if (is_array($decoded) && count($decoded) > 0) {
                $selectedProductIds = array_map('intval', $decoded);
                $existingIds = Product::whereIn('id', $selectedProductIds)->pluck('id')->toArray();
                $selectedProductIds = array_values(array_intersect($selectedProductIds, $existingIds));
            }
        }

        // Use CostQuotationEngine to auto-compute the full quotation
        $engine = new CostQuotationEngine();

        try {
            $quotation = $engine->calculateAndSave([
                'crop_type'         => $cropType,
                'season'            => $validated['season'],
                'land_area'         => $validated['land_area'],
                'weather_context'   => 'normal',
                'soil_type'         => 'clay_loam',
                'selected_products' => !empty($selectedProductIds) ? $selectedProductIds : null,
                'notes'             => $validated['notes'] ?? null,
            ], Auth::user());
        } catch (\Exception $e) {
            // Fallback: create a basic quotation without items if engine fails
            $quotation = DB::transaction(function () use ($validated, $cropType, $selectedProductIds) {
                $q = Quotation::create([
                    'user_id'              => Auth::id(),
                    'quotation_number'     => 'QT-' . date('Y') . '-' . strtoupper(Str::random(6)),
                    'crop_type'            => $cropType,
                    'season'               => $validated['season'],
                    'land_area'            => $validated['land_area'],
                    'estimated_total_cost' => 0,
                    'status'               => Quotation::STATUS_COMPUTED,
                    'valid_until'          => now()->addDays(14),
                    'notes'                => $validated['notes'] ?? null,
                ]);

                // Try to add items from DecisionFormula manually
                $formulas = DecisionFormula::with('product')
                    ->whereCrop($cropType)
                    ->where('season', $validated['season'])
                    ->get();

                if ($formulas->isNotEmpty()) {
                    $totalCost = 0;
                    foreach ($formulas as $formula) {
                        if ($formula->product && $formula->product->status === 'active') {
                            $qty = round((float) $formula->rate_per_hectare * (float) $validated['land_area'], 2);
                            $price = (float) $formula->product->price;
                            $subtotal = round($qty * $price, 2);

                            $q->items()->create([
                                'product_id'          => $formula->product_id,
                                'product_name'        => $formula->product->name,
                                'price_at_generation' => $price,
                                'calculated_quantity' => $qty,
                                'subtotal'            => $subtotal,
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
        $from   = request('from');
        $to     = request('to');
        $type   = request('type');

        $userId = Auth::id();
        $today  = now()->endOfDay();

        // ── Date range resolution ──
        // Custom From/To dates take priority over the period preset; otherwise the
        // preset period defines the range. Both are sanitised (no future, no swapped
        // order) so the query can never look into the future or return illogical data.
        $fromDate = null;
        $toDate   = null;

        $parseDate = function ($value) {
            if (!$value || strtotime($value) === false) {
                return null;
            }
            return \Illuminate\Support\Carbon::parse($value);
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
            $to   = $toDate->toDateString();
        }

        // ── Decide the effective start/end for the query ──
        $start = $fromDate;
        $end   = $toDate;

        // Period preset only applies when the user hasn't chosen custom dates.
        if (in_array($period, ['today', '7days', '30days', 'month'], true) && !$from && !$to) {
            [$periodStart, $periodEnd] = match ($period) {
                'today'   => [now()->startOfDay(), now()->endOfDay()],
                '7days'   => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
                '30days'  => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
                'month'   => [now()->startOfMonth(), now()->endOfMonth()],
            };
            $start = $periodStart;
            $end   = $periodEnd;
            $from  = $periodStart->toDateString();
            $to    = $periodEnd->toDateString();
        } elseif ($period !== 'all' && !$from && !$to) {
            // Invalid preset — fall back to no period filter.
            $period = 'all';
        }

        // ── Build the filtered query ──
        $query = \App\Models\ActivityLog::where('user_id', $userId);

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

        $activities = $query->latest()->paginate(10)->withQueryString();

        // ── Aggregate stats from the farmer's FULL (unfiltered) history ──
        // So the summary cards reflect their real totals regardless of active filters.
        $statsBase = \App\Models\ActivityLog::where('user_id', $userId);
        $totalQuotations = (clone $statsBase)->where('type', 'quotation.created')->count();
        $totalCrops = (clone $statsBase)->whereIn('type', ['crop.planted', 'crop.updated', 'crop.harvested', 'crop.deleted'])->count();
        $totalDeleted = (clone $statsBase)->where('type', 'quotation.deleted')->count();
        $totalLogins = (clone $statsBase)->where('type', 'auth.login')->count();

        $uniqueTypes = \App\Models\ActivityLog::where('user_id', $userId)
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
        $totalQuotations  = $allQuotations->count();
        $uniqueFarmers    = $allQuotations->pluck('user_id')->filter()->unique()->count();

        $recentQuotations = $allQuotations->where('created_at', '>=', now()->subDays(7))->count();
        $priorQuotations  = $allQuotations->filter(fn ($q) =>
            $q->created_at->lt(now()->subDays(7)) && $q->created_at->gte(now()->subDays(14))
        )->count();
        $weekTrend = $priorQuotations > 0
            ? (int) round(($recentQuotations - $priorQuotations) / $priorQuotations * 100)
            : ($recentQuotations > 0 ? 100 : 0);

        $ownDemandValue     = $storeItems->sum('subtotal');
        $demandProductCount = $storeItems->pluck('product_id')->unique()->count();

        // Products from this store that appear in quotations
        $productDemand = $storeItems
            ->groupBy('product_name')
            ->map(fn ($items) => [
                'count'       => $items->count(),
                'total_qty'   => $items->sum('calculated_quantity'),
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
        if (!in_array(Auth::id(), $involvedStoreIds)) {
            abort(403, 'You do not have access to this quotation.');
        }

        $quotation->load(['farmer', 'items.product.storeOwner']);

        // Mark as viewed by this store (first view only, records the timestamp)
        if ($quotation->status === Quotation::STATUS_COMPUTED) {
            $quotation->update([
                'status'    => Quotation::STATUS_VIEWED,
                'viewed_at' => $quotation->viewed_at ?? now(),
            ]);
            ActivityLogger::quotationViewed($quotation);
        }

        // Get this store's items specifically
        $storeItems = $quotation->items->filter(fn ($item) =>
            $item->product && $item->product->user_id === Auth::id()
        );

        return view('store.quotations.show', compact('quotation', 'storeItems'));
    }

    /**
     * Generate a complete AI demand intelligence report for this store (Groq API).
     */
    public function storeAiOverview(Request $request)
    {
        abort_unless($request->user()?->role === 'store_owner', 403);

        $apiKey = config('services.groq.key');
        if (!$apiKey) {
            return response()->json(['error' => 'AI service is not configured yet.'], 500);
        }

        $storeUserId = Auth::id();

        $quotations = Quotation::involvingStore($storeUserId)
            ->with(['items.product'])
            ->latest()
            ->get();

        $storeItems = $quotations->pluck('items')
            ->flatten()
            ->filter(fn ($item) => $item->product && $item->product->user_id === $storeUserId);

        $recentQuotations = $quotations->where('created_at', '>=', now()->subDays(7))->count();
        $priorQuotations  = $quotations->filter(fn ($q) =>
            $q->created_at->lt(now()->subDays(7)) && $q->created_at->gte(now()->subDays(14))
        )->count();

        $topProducts = $storeItems
            ->groupBy('product_name')
            ->map(fn ($items) => [
                'name'  => $items->first()->product_name,
                'count' => $items->count(),
                'qty'   => round($items->sum('calculated_quantity'), 1),
                'value' => round($items->sum('subtotal'), 2),
            ])
            ->sortByDesc('count')
            ->take(6)
            ->values()
            ->toArray();

        $topCrops = $quotations
            ->groupBy('crop_type')
            ->map(fn ($qs, $crop) => [
                'crop'  => $crop,
                'count' => $qs->count(),
                'area'  => $qs->sum('land_area'),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->toArray();

        $context = [
            'store'               => Auth::user()->name,
            'total_signals'       => $quotations->count(),
            'unique_farmers'      => $quotations->pluck('user_id')->filter()->unique()->count(),
            'this_week'           => $recentQuotations,
            'last_week'           => $priorQuotations,
            'own_products_value'  => round($storeItems->sum('subtotal'), 2),
            'signals_total_value' => round($quotations->sum('estimated_total_cost'), 2),
            'top_products'        => $topProducts,
            'top_crops'           => $topCrops,
        ];

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
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->withoutVerifying()->timeout(90)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'       => 'openai/gpt-oss-20b',
                'messages'    => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)],
                ],
                'temperature'     => 0.4,
                'max_tokens'      => 8192,
                'reasoning_effort'=> 'low',
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => $response->json('error.message') ?? 'AI service returned an error.',
                ], 502);
            }

            return response()->json([
                'overview' => trim((string) $response->json('choices.0.message.content', '')),
                'metrics' => [
                    'total_signals'       => $quotations->count(),
                    'unique_farmers'      => $quotations->pluck('user_id')->filter()->unique()->count(),
                    'this_week'           => $recentQuotations,
                    'last_week'           => $priorQuotations,
                    'own_products_value'  => round($storeItems->sum('subtotal'), 2),
                    'signals_total_value' => round($quotations->sum('estimated_total_cost'), 2),
                    'top_products'        => $topProducts,
                    'top_crops'           => $topCrops,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to reach the AI service. Please try again.'], 500);
        }
    }

    // ────────────────────────────────────────────────────────────────
    //  ADMIN — Demand Analytics & Association Performance
    // ────────────────────────────────────────────────────────────────

    /**
     * Admin view: all quotations + per-store demand metrics.
     */
    public function adminIndex()
    {
        // Full dataset for metrics, store performance, crop demand
        $allQuotations = Quotation::with(['farmer', 'items.product.storeOwner'])
            ->latest()
            ->get();

        // Global metrics (expired is derived from valid_until, not the stored status)
        $metrics = [
            'total'      => $allQuotations->count(),
            'computed'   => $allQuotations->filter(fn ($q) => !$q->is_expired && $q->status === Quotation::STATUS_COMPUTED)->count(),
            'viewed'     => $allQuotations->filter(fn ($q) => !$q->is_expired && $q->status === Quotation::STATUS_VIEWED)->count(),
            'expired'    => $allQuotations->filter(fn ($q) => $q->is_expired)->count(),
            'totalValue' => $allQuotations->sum('estimated_total_cost'),
        ];

        // Per-store demand performance
        $storePerformance = $allQuotations
            ->pluck('items')
            ->flatten()
            ->filter(fn ($item) => $item->product && $item->product->storeOwner)
            ->groupBy(fn ($item) => $item->product->user_id)
            ->map(function ($items, $storeId) {
                $store = $items->first()->product->storeOwner;
                return [
                    'store_name'      => $store->name,
                    'store_id'        => $storeId,
                    'quotation_count' => $items->pluck('quotation_id')->unique()->count(),
                    'product_count'   => $items->pluck('product_id')->unique()->count(),
                    'total_value'     => $items->sum('subtotal'),
                    'total_qty'       => $items->sum('calculated_quantity'),
                ];
            })
            ->sortByDesc('quotation_count')
            ->values()
            ->toArray();

        // Crop demand breakdown
        $cropDemand = $allQuotations
            ->groupBy('crop_type')
            ->map(fn ($qs, $crop) => [
                'crop' => $crop,
                'count' => $qs->count(),
                'total_area' => $qs->sum('land_area'),
                'total_value' => $qs->sum('estimated_total_cost'),
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

        $quotations = Quotation::with(['farmer', 'items.product.storeOwner'])
            ->latest()
            ->get();

        return view('admin.quotations.index', compact('quotations', 'metrics', 'storePerformance', 'cropDemand'));
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

        $query = \App\Models\ActivityLog::with('user');

        if ($userFilter) {
            $query->where('user_id', $userFilter);
        }
        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }

        $activities = $query->latest()->paginate(10)->withQueryString();

        // For the filter dropdown
        $users = \App\Models\User::orderBy('name')->get();
        $activityTypes = \App\Models\ActivityLog::select('type')->distinct()->pluck('type')->sort()->toArray();

        return view('admin.activity.index', compact('activities', 'users', 'activityTypes', 'userFilter', 'typeFilter'));
    }
}
