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
            // The catalog card renders star ratings, which are accessors over
            // this relation. Eager load it or every card costs its own query.
            ->with(['category', 'mainImage', 'images', 'approvedReviews'])
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
     * Whether any of the given keys holds a real value.
     *
     * `!empty()` is wrong for these blocks: it treats "0" as missing, so a
     * 0-20-0 fertilizer (nitrogen 0) would never create its detail row, and
     * it ignores a product that only supplies an application rate or only a
     * registration number.
     */
    /**
     * Resolve the label stored in the legacy `products.category` text column.
     *
     * Shared with the spreadsheet importer via the model so every writer agrees
     * on what that column means.
     */
    private function legacyCategoryLabel(Category $category): string
    {
        return Product::legacyCategoryLabel($category);
    }

    private function hasAnyValue(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if ($value !== null && $value !== '' && $value !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the incoming payload actually manages a spec block at all.
     *
     * This separates "the user opened the form and blanked every field" (a
     * deliberate clear) from "this request never carried the block" (leave the
     * existing row untouched).
     */
    private function wasSubmitted(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create/update/clear a one-to-one spec row from a submitted form block.
     *
     * `updateOrCreate` alone silently leaves stale data behind when the user
     * clears every field on an edit, so an all-blank submission deletes the row.
     */
    private function syncSpecDetails(
        Product $product,
        string $relation,
        array $validated,
        array $keys,
        array $attributes
    ): void {
        if (! $this->wasSubmitted($validated, $keys)) {
            return;
        }

        if (! $this->hasAnyValue($validated, $keys)) {
            $product->{$relation}()->delete();

            return;
        }

        $product->{$relation}()->updateOrCreate(
            ['product_id' => $product->id],
            $attributes
        );
    }

    private const FERTILIZER_FIELDS = [
        'nitrogen_percentage',
        'phosphorus_percentage',
        'potassium_percentage',
        'form_type',
        'fertilizer_rate_min',
        'fertilizer_rate_max',
        'fertilizer_rate_unit',
        'fertilizer_method',
        'fertilizer_timing',
    ];

    private const REGISTRATION_FIELDS = [
        'registration_status',
        'registration_authority',
        'registration_number',
        'lot_number',
        'batch_number',
        'manufacturing_date',
        'expiration_date',
    ];

    /**
     * Store a newly created product in the database.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        // `products.category` is a legacy denormalised text column that is NOT
        // NULL, while the forms only submit `category_id`. Keep it in sync so
        // the insert satisfies strict mode and legacy readers still see a label.
        $category = Category::with('parent')->findOrFail($validated['category_id']);

        DB::transaction(function () use ($validated, $request, $category) {
            $product = Product::create([
                'user_id'            => Auth::id(),
                'category_id'        => $validated['category_id'],
                'category'           => $this->legacyCategoryLabel($category),
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

            // Handle image uploads — the first one becomes the main image.
            $this->storeProductImages($product, $request->file('images', []));

            // Fertilizer Details
            if ($this->hasAnyValue($validated, self::FERTILIZER_FIELDS)) {
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
            if ($this->hasAnyValue($validated, self::REGISTRATION_FIELDS)) {
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
        $category = Category::with('parent')->findOrFail($validated['category_id']);

        DB::transaction(function () use ($validated, $request, $product, $category) {
            $product->update([
                'category_id'        => $validated['category_id'],
                'category'           => $this->legacyCategoryLabel($category),
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

            // Append any newly uploaded images; existing ones are left alone
            // and managed individually from the edit form.
            $this->storeProductImages($product, $request->file('images', []));

            // Fertilizer Details: create, update, or clear when blanked.
            $this->syncSpecDetails(
                $product,
                'fertilizerDetail',
                $validated,
                self::FERTILIZER_FIELDS,
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

            // Registration Details: create, update, or clear when blanked.
            $this->syncSpecDetails(
                $product,
                'registration',
                $validated,
                self::REGISTRATION_FIELDS,
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
            ->with(['category', 'storeOwner', 'mainImage', 'images', 'approvedReviews'])
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
     * Persist uploaded product images. Zero uploads is a no-op; the first
     * image is promoted to `main` only when the product has none yet, so
     * appending to an existing gallery never steals the main slot.
     *
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     */
    private function storeProductImages(Product $product, array $files): void
    {
        $promoteFirstToMain = ! $product->mainImage()->exists();
        $isFirst = true;

        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('products/' . $product->id, 'public');

            $product->images()->create([
                'image_type'        => ($isFirst && $promoteFirstToMain) ? 'main' : 'gallery',
                'file_path'         => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type'         => $file->getMimeType(),
                'file_size'         => $file->getSize(),
            ]);

            $isFirst = false;
        }
    }

    /**
     * Promote one of the product's images to be the main/cover image.
     */
    public function updateMainImage(Product $product, ProductImage $image)
    {
        $this->authorizeProductOwner($product);

        abort_unless($image->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $image) {
            $product->images()->where('image_type', 'main')->update(['image_type' => 'gallery']);
            $image->update(['image_type' => 'main']);
        });

        return back()->with('success', 'Main image updated.');
    }

    /**
     * Delete a single product image, promoting another to main if needed.
     */
    public function destroyImage(Product $product, ProductImage $image)
    {
        $this->authorizeProductOwner($product);

        abort_unless($image->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $image) {
            $wasMain = $image->image_type === 'main';

            Storage::disk('public')->delete($image->file_path);
            $image->delete();

            if ($wasMain && ! $product->mainImage()->exists()) {
                $product->images()->orderBy('id')->first()?->update(['image_type' => 'main']);
            }
        });

        return back()->with('success', 'Image removed successfully.');
    }

    private function authorizeProductOwner(Product $product): void
    {
        if ($product->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}