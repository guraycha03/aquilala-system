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
     * Show a quotation detail to the farmer (read-only cost breakdown).
     */
    public function farmerShow(Quotation $quotation)
    {
        if ($quotation->user_id !== Auth::id()) {
            abort(403);
        }

        $quotation->load(['items.product.storeOwner', 'farmer']);

        return view('farmer.quotations.show', compact('quotation'));
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
            ->with('success', 'Your cost estimate has been generated instantly! Review the breakdown below. Store owners will also see this as demand intel for their products.');
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
