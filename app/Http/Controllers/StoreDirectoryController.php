<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;

class StoreDirectoryController extends Controller
{
    /**
     * Display all registered store owners with their store info and product counts.
     *
     * Each store card shows a six-product carousel; the rest are reached
     * through that store's "Browse Products" link. products_count is counted
     * with the same `active` constraint the carousel is loaded under, so the
     * chip can never claim more products than the store actually lists.
     */
    public function index()
    {
        $stores = User::where('role', 'store_owner')
            ->whereNotNull('store_name')
            ->with(['products' => function ($q) {
                $q->where('status', 'active');
            }])
            ->withCount(['products as products_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderBy('store_name')
            ->get();

        // Trim to 6 products per store in the view data (avoids row_number window fn)
        $stores->each(function ($store) {
            $store->setRelation('products', $store->products->take(6));
        });

        $totalStores = $stores->count();
        $totalProducts = Product::where('status', 'active')->count();

        return view('farmer.stores.index', compact('stores', 'totalStores', 'totalProducts'));
    }

    /**
     * Display one store's own page: its details and every product it lists.
     *
     * The directory card's Browse Products button and its "See all" tile both
     * land here, so a farmer after the rest of a store's listing reaches that
     * store alone instead of a catalog mixed with every other store.
     */
    public function show(User $store)
    {
        // Only a registered store owner with a store name gets a page. Any
        // other account is a 404, so this URL can't be used to fish for
        // farmers or staff accounts.
        abort_unless(
            $store->role === 'store_owner' && $store->store_name,
            404
        );

        $products = $store->products()
            ->where('status', 'active')
            ->with(['category.parent', 'mainImage', 'images'])
            ->orderBy('created_at', 'desc')
            ->get();

        Product::markSavedState($products, auth()->user());

        $productsCount = $products->count();

        return view('farmer.stores.show', compact('store', 'products', 'productsCount'));
    }
}

