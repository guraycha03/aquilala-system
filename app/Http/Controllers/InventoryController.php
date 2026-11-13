<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display the inventory management dashboard with live data.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $cat = trim((string) $request->query('cat', ''));
        $filterActive = $q !== '' || in_array($status, ['healthy', 'low', 'out'], true) || $cat !== '';

        // Paginated products for the main table (server-side smart filtering)
        $query = Product::with('category')
            ->where('user_id', $userId)
            ->orderBy('name');

        $tokens = preg_split('/\s+/', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($tokens as $token) {
            $like = "%{$token}%";
            $query->where(function ($sub) use ($like) {
                $sub->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(sku) LIKE ?', [$like])
                    ->orWhereHas('category', function ($c) use ($like) {
                        $c->whereRaw('LOWER(name) LIKE ?', [$like]);
                    });
            });
        }

        if ($status === 'healthy') {
            $query->where(function ($sub) {
                $sub->whereRaw('COALESCE(stock_quantity,0) > COALESCE(reorder_threshold,1)')
                    ->where('status', '!=', 'discontinued');
            });
        } elseif ($status === 'low') {
            $query->where(function ($sub) {
                $sub->whereRaw('COALESCE(stock_quantity,0) BETWEEN 1 AND COALESCE(reorder_threshold,1)')
                    ->where('status', '!=', 'discontinued');
            });
        } elseif ($status === 'out') {
            $query->where(function ($sub) {
                $sub->where('stock_quantity', '<=', 0)
                    ->orWhere('status', 'out_of_stock')
                    ->orWhere('status', 'discontinued');
            });
        }

        if ($cat !== '') {
            $query->whereHas('category', fn ($c) => $c->where('name', $cat));
        }

        $products = $query->get();

        // Full dataset for metrics, chart, and modal
        $allProducts = Product::with('category')->where('user_id', $userId)->get();

        // Distinct category names for the filter dropdown. Must use
        // category_display_name: the legacy `category` column shadows the
        // relation, so pluck('category') yields plain strings and the
        // following ->pluck('name') reduced every entry to null.
        $categories = $allProducts
            ->map(fn ($p) => $p->category_display_name)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Metrics
        $totalItems = $allProducts->count();

        // Stock-health buckets, decided in this order so a product can only
        // ever land in ONE of them. The bar chart beside the donut already
        // worked this way (it returns early on quantity <= 0), and `stats()`
        // plus the status filters above already exclude zero from low stock —
        // but this block did not.
        //
        // An empty shelf satisfies `quantity <= reorder_threshold`, so a single
        // zero-quantity product was counted as BOTH out of stock and low stock,
        // then subtracted twice from healthyCount. Store 86's 18 products
        // rendered as "1 out, 1 low, 16 healthy" when the truth is
        // "1 out, 0 low, 17 healthy" — which is why the filters correctly
        // returned nothing for low stock while the donut claimed one.
        $isOutOfStock = fn ($p) => ($p->stock_quantity ?? 0) <= 0
            || $p->status === 'out_of_stock';

        $outOfStockItems = $allProducts->filter($isOutOfStock);
        $lowStockItems = $allProducts->filter(
            fn ($p) => ! $isOutOfStock($p) && ($p->stock_quantity ?? 0) <= $p->reorder_threshold
        );
        $outOfStockCount = $outOfStockItems->count();
        $lowStockCount = $lowStockItems->count();

        $totalStockValue = $allProducts->sum(function ($p) {
            return ($p->stock_quantity ?? 0) * $p->price;
        });
        $totalUnits = $allProducts->sum(fn ($p) => (int) ($p->stock_quantity ?? 0));

        // Stock health breakdown — exact now that the buckets are disjoint.
        $healthyCount = $totalItems - $lowStockCount - $outOfStockCount;

        // Archived (trashed) products this store can restore — powers the Archive button badge.
        $archivedCount = Product::onlyTrashed()->where('user_id', $userId)->count();

        return view('store.inventory.index', compact(
            'products', 'allProducts',
            'totalItems', 'lowStockCount', 'outOfStockCount',
            'totalStockValue', 'totalUnits', 'healthyCount', 'lowStockItems',
            'status', 'q', 'cat', 'filterActive', 'categories', 'archivedCount'
        ));
    }

    /**
     * Update stock quantity for a product (quick edit inline).
     */
    public function updateStock(Request $request, Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'reorder_threshold' => ['required', 'integer', 'min:0'],
        ]);

        $before = $product->only(['stock_quantity', 'reorder_threshold']);

        $product->update($validated);

        $changes = [];
        foreach ($validated as $key => $new) {
            if ((int) $before[$key] !== (int) $new) {
                $label = $key === 'stock_quantity' ? 'stock' : 'reorder level';
                $changes[] = "{$label}: {$before[$key]} → {$new}";
            }
        }

        if ($changes !== []) {
            ActivityLogger::productUpdated($product, implode(', ', $changes));
        }

        return redirect()
            ->route('store.inventory.index')
            ->with('success', 'Stock levels updated successfully for ' . $product->name . '.');
    }

    /**
     * API endpoint to get inventory stats for charting.
     */
    public function stats()
    {
        $userId = Auth::id();
        $products = Product::where('user_id', $userId)->get();

        // Same exclusive buckets as index(): out-of-stock decided first, so a
        // zero-quantity product can never be counted here as low stock too and
        // push healthy negative.
        $isOutOfStock = fn ($p) => ($p->stock_quantity ?? 0) <= 0
            || $p->status === 'out_of_stock';

        $outOfStock = $products->filter($isOutOfStock)->count();
        $lowStock = $products->filter(
            fn ($p) => ! $isOutOfStock($p) && ($p->stock_quantity ?? 0) <= $p->reorder_threshold
        )->count();
        $healthy = $products->count() - $lowStock - $outOfStock;

        return response()->json([
            'labels' => ['Healthy Stock', 'Low Stock', 'Out of Stock'],
            'data' => [$healthy, $lowStock, $outOfStock],
            'colors' => ['#16a34a', '#f59e0b', '#dc2626'],
        ]);
    }
}
