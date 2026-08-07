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
            ->withCount('products')
            ->with(['products' => function ($q) {
                $q->active()->take(4);
            }])
            ->orderBy('store_name')
            ->get();

        $totalStores = $stores->count();
        $totalProducts = Product::active()->count();

        return view('farmer.stores.index', compact('stores', 'totalStores', 'totalProducts'));
    }
}

