<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display the inventory management dashboard with live data.
     */
    public function index()
    {
        $userId = Auth::id();

        // Fetch all products for this store owner with related category
        $products = Product::with('category')
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();

        // Metrics
        $totalItems = $products->count();
        $lowStockItems = $products->filter(function ($p) {
            return ($p->stock_quantity ?? 0) <= $p->reorder_threshold;
        });
        $lowStockCount = $lowStockItems->count();
        $outOfStockCount = $products->filter(function ($p) {
            return ($p->stock_quantity ?? 0) <= 0 || $p->status === 'out_of_stock';
        })->count();
        $totalStockValue = $products->sum(function ($p) {
            return ($p->stock_quantity ?? 0) * $p->price;
        });
        $totalUnits = $products->sum(fn ($p) => (int) ($p->stock_quantity ?? 0));

        // Stock health breakdown
        $healthyCount = $totalItems - $lowStockCount - $outOfStockCount;

        return view('store.inventory.index', compact(
            'products',
            'totalItems',
            'lowStockCount',
            'outOfStockCount',
            'totalStockValue',
            'totalUnits',
            'healthyCount',
            'lowStockItems'
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

        $product->update($validated);

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

        $lowStock = $products->filter(fn($p) => ($p->stock_quantity ?? 0) <= $p->reorder_threshold && ($p->stock_quantity ?? 0) > 0)->count();
        $outOfStock = $products->filter(fn($p) => ($p->stock_quantity ?? 0) <= 0 || $p->status === 'out_of_stock')->count();
        $healthy = $products->count() - $lowStock - $outOfStock;

        return response()->json([
            'labels' => ['Healthy Stock', 'Low Stock', 'Out of Stock'],
            'data' => [$healthy, $lowStock, $outOfStock],
            'colors' => ['#16a34a', '#f59e0b', '#dc2626'],
        ]);
    }
}
