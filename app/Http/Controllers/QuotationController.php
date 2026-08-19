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

        return view('farmer.quotations.index', compact('quotations'));
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
     * Show the farmer's activity history.
     */
    public function farmerActivity()
    {
        $activities = \App\Models\ActivityLog::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('farmer.activity.index', compact('activities'));
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

        // Quotations where this store's products were used
        $quotations = Quotation::involvingStore($storeUserId)
            ->with(['farmer', 'items.product'])
            ->orderByRaw("FIELD(status, 'computed', 'viewed', 'expired')")
            ->latest()
            ->get();

        // Demand metrics
        $totalQuotations = $quotations->count();
        $recentQuotations = $quotations->where('created_at', '>=', now()->subDays(7))->count();
        $totalDemandValue = $quotations->sum('estimated_total_cost');

        // Products from this store that appear in quotations
        $productDemand = $quotations->pluck('items')
            ->flatten()
            ->filter(fn ($item) => $item->product && $item->product->user_id === $storeUserId)
            ->groupBy('product_name')
            ->map(fn ($items) => [
                'count' => $items->count(),
                'total_qty' => $items->sum('calculated_quantity'),
                'total_value' => $items->sum('subtotal'),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->toArray();

        return view('store.quotations.index', compact(
            'quotations',
            'totalQuotations',
            'recentQuotations',
            'totalDemandValue',
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

        // Mark as viewed by this store
        if ($quotation->status === Quotation::STATUS_COMPUTED) {
            $quotation->update(['status' => Quotation::STATUS_VIEWED]);
            ActivityLogger::quotationViewed($quotation);
        }

        // Get this store's items specifically
        $storeItems = $quotation->items->filter(fn ($item) =>
            $item->product && $item->product->user_id === Auth::id()
        );

        return view('store.quotations.show', compact('quotation', 'storeItems'));
    }

    // ────────────────────────────────────────────────────────────────
    //  ADMIN — Demand Analytics & Association Performance
    // ────────────────────────────────────────────────────────────────

    /**
     * Admin view: all quotations + per-store demand metrics.
     */
    public function adminIndex()
    {
        $quotations = Quotation::with(['farmer', 'items.product.storeOwner'])
            ->latest()
            ->get();

        // Global metrics
        $metrics = [
            'total'      => $quotations->count(),
            'computed'   => $quotations->where('status', Quotation::STATUS_COMPUTED)->count(),
            'viewed'     => $quotations->where('status', Quotation::STATUS_VIEWED)->count(),
            'expired'    => $quotations->where('status', Quotation::STATUS_EXPIRED)->count(),
            'totalValue' => $quotations->sum('estimated_total_cost'),
        ];

        // Per-store demand performance
        $storePerformance = $quotations
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
        $cropDemand = $quotations
            ->groupBy('crop_type')
            ->map(fn ($qs) => [
                'count' => $qs->count(),
                'total_area' => $qs->sum('land_area'),
                'total_value' => $qs->sum('estimated_total_cost'),
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

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
        $activities = \App\Models\ActivityLog::with('user')
            ->latest()
            ->paginate(20);

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

        $activities = $query->latest()->paginate(20);

        // For the filter dropdown
        $users = \App\Models\User::orderBy('name')->get();
        $activityTypes = \App\Models\ActivityLog::select('type')->distinct()->pluck('type')->sort()->toArray();

        return view('admin.activity.index', compact('activities', 'users', 'activityTypes', 'userFilter', 'typeFilter'));
    }

    // ────────────────────────────────────────────────────────────────
    //  LEGACY STUBS — Backward compatibility for old routes
    // ────────────────────────────────────────────────────────────────

    public function acceptQuotation(Quotation $quotation)
    {
        return back()->with('success', 'This quotation is an informational estimate. Contact the store directly to proceed with a purchase.');
    }

    public function rejectQuotation(Request $request, Quotation $quotation)
    {
        return back()->with('success', 'This quotation is an informational estimate. No action needed.');
    }

    public function claimQuotation(Quotation $quotation)
    {
        return back()->with('success', 'Quotations are now auto-computed. No claiming needed — you can view demand intel directly.');
    }

    public function computePrices(Request $request, Quotation $quotation)
    {
        return back()->with('success', 'Quotations are now auto-computed when farmers submit them.');
    }

    public function dispatchQuotation(Quotation $quotation)
    {
        return back()->with('success', 'Quotations are now instantly available to farmers. No dispatch step needed.');
    }

    public function updateItem(Request $request, QuotationItem $item)
    {
        return back()->with('success', 'Quotation items are now auto-computed. Overrides are no longer needed.');
    }
}
