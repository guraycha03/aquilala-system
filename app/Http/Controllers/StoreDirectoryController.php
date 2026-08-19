<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;

class StoreDirectoryController extends Controller
{
    /**
     * Display all registered store owners with their store info and product counts.
     */
    public function index()
    {
        $stores = User::where('role', 'store_owner')
            ->whereNotNull('store_name')
            ->with(['products' => function ($q) {
                $q->where('status', 'active');
            }])
            ->withCount('products')
            ->orderBy('store_name')
            ->get();

        // Trim to 4 products per store in the view data (avoids row_number window fn)
        $stores->each(function ($store) {
            $store->setRelation('products', $store->products->take(4));
        });

        $totalStores = $stores->count();
        $totalProducts = Product::where('status', 'active')->count();

        return view('farmer.stores.index', compact('stores', 'totalStores', 'totalProducts'));
    }
}

