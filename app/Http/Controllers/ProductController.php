<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Quotation;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
/**
     * Display a listing of the store owner's products.
     */
    public function index()
    {
        $user = Auth::user();

        // Query products for the logged-in merchant
        $products = Product::where('user_id', $user->id)
            ->with(['category', 'mainImage', 'images'])
            ->latest()
            ->get();

        // ─── Merchant Dashboard Metrics ────────────────────────────
        $productCount = $products->count();
        $lowStockProducts = $products->filter(fn ($p) => $p->is_low_stock)->values();
        $lowStockCount = $lowStockProducts->count();

        // ─── Quotation metrics for this store ──────────────────────
        // Quotations that use one of this store's products = demand signals
        $involvedStoreQuery = fn ($andClause = null) => Quotation::query()
            ->whereHas('items.product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($andClause, fn ($query, $status) => $query->where('status', $status));

        $demandQuotationCount = $involvedStoreQuery()->count();
        $computedQuotationCount = $involvedStoreQuery(Quotation::STATUS_COMPUTED)->count();
        $viewedQuotationCount = $involvedStoreQuery(Quotation::STATUS_VIEWED)->count();

        // ─── Top performers (Most Viewed / Most Quoted / Top Rated) ─────
        $products->load('approvedReviews');

        // ─── Rating & review metrics for this store ───────────────────
        $ratedProducts = $products->filter(fn ($p) => $p->average_rating !== null);
        $averageRating = $ratedProducts->isNotEmpty()
            ? round((float) $ratedProducts->avg('average_rating'), 1)
            : null;
        $totalReviews = $products->sum('review_count');

        $topViewed = $products
            ->where('views_count', '>', 0)
            ->sortByDesc('views_count')
            ->take(5)
            ->values();

        // Distinct quotations that include each of this store's products.
        $quotedCounts = DB::table('quotation_items')
            ->join('products', 'quotation_items.product_id', '=', 'products.id')
            ->where('products.user_id', $user->id)
            ->select('quotation_items.product_id', DB::raw('COUNT(DISTINCT quotation_items.quotation_id) as total'))
            ->groupBy('quotation_items.product_id')
            ->orderByDesc('total')
            ->get()
            ->keyBy('product_id');

        $topQuoted = $products
            ->filter(fn ($p) => isset($quotedCounts[$p->id]))
            ->sortByDesc(fn ($p) => $quotedCounts[$p->id]->total)
            ->take(5)
            ->values()
            ->map(fn ($p) => (object) [
                'product' => $p,
                'count'   => (int) $quotedCounts[$p->id]->total,
            ]);

        $topRated = $products
            ->filter(fn ($p) => $p->review_count > 0)
            ->sortByDesc(fn ($p) => [$p->average_rating, $p->review_count])
            ->take(5)
            ->values();

        // Pass $products to your dashboard blade view
        return view('dashboard.store_owner', compact(
            'products',
            'productCount',
            'lowStockCount',
            'demandQuotationCount',
            'computedQuotationCount',
            'viewedQuotationCount',
            'ratedProducts',
            'averageRating',
            'totalReviews',
            'topViewed',
            'topQuoted',
            'topRated'
        ));
    }

    /**
     * Display the store owner's product catalog (My Store page).
     */
    public function storefront()
    {
        $products = Product::where('user_id', Auth::id())
            ->with(['category', 'mainImage', 'images'])
            ->latest()
            ->get();

        $categories = $products
            ->pluck('category.name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('store.storefront.index', compact('products', 'categories'));
    }

    /**
     * Display the farmer-facing product recommendations page.
     */
    public function recommendations()
    {
        $products = Product::active()
            ->with(['category', 'mainImage', 'images', 'storeOwner'])
            ->latest()
            ->get();

        $storeCount = \App\Models\User::where('role', 'store_owner')
            ->whereNotNull('store_name')
            ->count();

        // Product categories present among the recommended products, with counts.
        $categories = $products
            ->filter(fn ($p) => $p->category_display_name !== 'Uncategorized')
            ->groupBy(fn ($p) => $p->category_display_name)
            ->sortKeys()
            ->map(fn ($group) => (object) [
                'name'  => $group->first()->category_display_name,
                'count' => $group->count(),
            ])
            ->values();

        return view('farmer.recommendations.index', compact('products', 'storeCount', 'categories'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categories = Category::active()->parents()->with('children')->get();
        $isEdit = false;

        return view('store.storefront.create', compact('categories', 'isEdit'));
    }

    /**
     * Store a newly created product in the database.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request) {
            $product = Product::create([
                'user_id'            => Auth::id(),
                'category_id'        => $validated['category_id'],
                'name'               => $validated['name'],
                'brand'              => $validated['brand'] ?? null,
                'sku'                => $validated['sku'],
                'product_type'       => $validated['product_type'] ?? null,
                'description'        => $validated['description'] ?? null,
                'package_size'       => $validated['package_size'] ?? null,
                'package_unit'       => $validated['package_unit'] ?? null,
                'packaging_type'     => $validated['packaging_type'] ?? null,
                'price'              => $validated['price'],
                'price_unit'         => $validated['price_unit'] ?? null,
                'stock_quantity'    => $validated['stock_quantity'],
                'stock_unit'         => $validated['stock_unit'] ?? null,
                'reorder_threshold'  => $validated['reorder_threshold'],
                'recommended_season' => $validated['recommended_season'],
                'status'             => $validated['status'],
            ]);

            // Handle main image upload
            if ($request->hasFile('main_image')) {
                $file = $request->file('main_image');
                $path = $file->store('products/' . $product->id, 'public');

                $product->images()->create([
                    'image_type'        => 'main',
                    'file_path'         => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type'         => $file->getMimeType(),
                    'file_size'         => $file->getSize(),
                ]);
            }

            // Fertilizer Details
            if (!empty($validated['nitrogen_percentage']) || !empty($validated['phosphorus_percentage']) || !empty($validated['potassium_percentage'])) {
                $product->fertilizerDetail()->create([
                    'nitrogen_percentage'   => $validated['nitrogen_percentage'] ?? null,
                    'phosphorus_percentage' => $validated['phosphorus_percentage'] ?? null,
                    'potassium_percentage'  => $validated['potassium_percentage'] ?? null,
                    'form_type'             => $validated['form_type'] ?? null,
                    'application_rate_min'  => $validated['fertilizer_rate_min'] ?? null,
                    'application_rate_max'  => $validated['fertilizer_rate_max'] ?? null,
                    'application_rate_unit' => $validated['fertilizer_rate_unit'] ?? null,
                    'application_method'    => $validated['fertilizer_method'] ?? null,
                    'application_timing'    => $validated['fertilizer_timing'] ?? null,
                ]);
            }

            // Registration Details
            if (!empty($validated['registration_status']) || !empty($validated['lot_number'])) {
                $product->registration()->create([
                    'registration_status'    => $validated['registration_status'] ?? null,
                    'registration_authority' => $validated['registration_authority'] ?? null,
                    'registration_number'    => $validated['registration_number'] ?? null,
                    'lot_number'             => $validated['lot_number'] ?? null,
                    'batch_number'           => $validated['batch_number'] ?? null,
                    'manufacturing_date'     => $validated['manufacturing_date'] ?? null,
                    'expiration_date'        => $validated['expiration_date'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('store.storefront.index')
            ->with('success', 'Product profile created successfully.');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $product->load(['category', 'fertilizerDetail', 'registration', 'images', 'documents', 'mainImage']);
        $categories = Category::active()->parents()->with('children')->get();
        $isEdit = true;

        return view('store.storefront.edit', compact('product', 'categories', 'isEdit'));
    }

    /**
     * Update the specified product in the database.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, $product) {
            $product->update([
                'category_id'        => $validated['category_id'],
                'name'               => $validated['name'],
                'brand'              => $validated['brand'] ?? null,
                'sku'                => $validated['sku'],
                'product_type'       => $validated['product_type'] ?? null,
                'description'        => $validated['description'] ?? null,
                'package_size'       => $validated['package_size'] ?? null,
                'package_unit'       => $validated['package_unit'] ?? null,
                'packaging_type'     => $validated['packaging_type'] ?? null,
                'price'              => $validated['price'],
                'price_unit'         => $validated['price_unit'] ?? null,
                'stock_quantity'    => $validated['stock_quantity'],
                'stock_unit'         => $validated['stock_unit'] ?? null,
                'reorder_threshold'  => $validated['reorder_threshold'],
                'recommended_season' => $validated['recommended_season'],
                'status'             => $validated['status'],
            ]);

            // Handle main image replacement
            if ($request->hasFile('main_image')) {
                $oldMain = $product->mainImage;
                if ($oldMain) {
                    Storage::disk('public')->delete($oldMain->file_path);
                    $oldMain->delete();
                }

                $file = $request->file('main_image');
                $path = $file->store('products/' . $product->id, 'public');

                $product->images()->create([
                    'image_type'        => 'main',
                    'file_path'         => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type'         => $file->getMimeType(),
                    'file_size'         => $file->getSize(),
                ]);
            }

            // Fertilizer Details update/create
            if (!empty($validated['nitrogen_percentage']) || !empty($validated['phosphorus_percentage']) || !empty($validated['potassium_percentage'])) {
                $product->fertilizerDetail()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'nitrogen_percentage'   => $validated['nitrogen_percentage'] ?? null,
                        'phosphorus_percentage' => $validated['phosphorus_percentage'] ?? null,
                        'potassium_percentage'  => $validated['potassium_percentage'] ?? null,
                        'form_type'             => $validated['form_type'] ?? null,
                        'application_rate_min'  => $validated['fertilizer_rate_min'] ?? null,
                        'application_rate_max'  => $validated['fertilizer_rate_max'] ?? null,
                        'application_rate_unit' => $validated['fertilizer_rate_unit'] ?? null,
                        'application_method'    => $validated['fertilizer_method'] ?? null,
                        'application_timing'    => $validated['fertilizer_timing'] ?? null,
                    ]
                );
            }

            // Registration Details update/create
            if (!empty($validated['registration_status']) || !empty($validated['lot_number'])) {
                $product->registration()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'registration_status'    => $validated['registration_status'] ?? null,
                        'registration_authority' => $validated['registration_authority'] ?? null,
                        'registration_number'    => $validated['registration_number'] ?? null,
                        'lot_number'             => $validated['lot_number'] ?? null,
                        'batch_number'           => $validated['batch_number'] ?? null,
                        'manufacturing_date'     => $validated['manufacturing_date'] ?? null,
                        'expiration_date'        => $validated['expiration_date'] ?? null,
                    ]
                );
            }
        });

        return redirect()
            ->route('store.storefront.index')
            ->with('success', 'Product profile updated successfully.');
    }

    /**
     * Remove the specified product from the database.
     */
    public function destroy(Product $product)
    {
        if ($product->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->file_path);
            }
            $product->images()->delete();
            $product->fertilizerDetail()->delete();
            $product->registration()->delete();
            $product->documents()->delete();
            $product->delete();
        });

        return redirect()
            ->route('store.storefront.index')
            ->with('success', 'Product profile deleted successfully.');
    }

    /**
     * Display all active products for farmers to browse.
     */
    public function browse()
    {
        $products = Product::active()
            ->with(['category', 'storeOwner', 'mainImage', 'approvedReviews'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('farmer.products.browse', compact('products'));
    }

    /**
     * Display the specified product (farmer-facing detail page).
     */
    public function show(Product $product)
    {
        if ($product->status !== 'active' && $product->user_id !== Auth::id()) {
            abort(404);
        }

        // Track farmer product-page views for the store dashboard "Most Viewed" ranking.
        // Store owners/admin viewing their own products are not counted as farmer visits.
        if (Auth::check() && Auth::user()->role === 'farmer') {
            Product::whereKey($product->id)->increment('views_count');
        }

        $product->load([
            'category',
            'storeOwner',
            'fertilizerDetail',
            'registration',
            'applications.crop',
            'images',
            'documents',
            'mainImage',
            'approvedReviews.farmer',
        ]);

        $relatedProducts = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('approvedReviews')
            ->take(4)
            ->get();

        // The farmer's own review (any status) so they can see moderation state.
        $myReview = $product->reviews()->where('user_id', Auth::id())->first();

        return view('farmer.products.show', compact('product', 'relatedProducts', 'myReview'));
    }

    /**
     * Delete a single product image.
     */
    public function deleteImage($imageId)
    {
        $image = ProductImage::findOrFail($imageId);
        $product = $image->product;

        if ($product->user_id !== Auth::id()) {
            abort(403);
        }

        Storage::disk('public')->delete($image->file_path);
        $image->delete();

        return back()->with('success', 'Image removed successfully.');
    }
}