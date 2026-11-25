<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Quotation;
use App\Models\User;
use App\Helpers\SeasonHelper;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ActivityLogger;
use App\Services\CropLibraryService;
use App\Services\ProductRecommendationService;
use App\Support\ProductImageResizer;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $outOfStockProducts = $products->filter(fn ($p) => $p->stock_quantity <= 0 || $p->status === 'out_of_stock')->values();
        $outOfStockCount = $outOfStockProducts->count();

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

        // Saved-for-later state for the dashboard "My Products" carousel.
        Product::markSavedState($products, $user);

        // Pass $products to your dashboard blade view
        return view('dashboard.store_owner', compact(
            'products',
            'productCount',
            'lowStockCount',
            'outOfStockCount',
            'demandQuotationCount',
            'computedQuotationCount',
            'viewedQuotationCount'
        ));
    }

    /**
     * Store dashboard sub-page: the "Top Products" rankings.
     */
    public function performance()
    {
        $user = Auth::user();

        $products = Product::where('user_id', $user->id)
            ->with(['category', 'mainImage', 'images'])
            ->latest()
            ->get();

        ['topViewed' => $topViewed, 'topQuoted' => $topQuoted] = $this->topPerformers($products);

        return view('dashboard.store_performance', compact(
            'topViewed',
            'topQuoted'
        ));
    }

    /**
     * Most viewed and most quoted products for the signed-in store owner
     * (5 each) — shared by both dashboard pages.
     *
     * @return array{topViewed: \Illuminate\Support\Collection, topQuoted: \Illuminate\Support\Collection}
     */
    private function topPerformers($products)
    {
        $user = Auth::user();

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

        return compact('topViewed', 'topQuoted');
    }

    /**
     * Display the store owner's product catalog (My Store page).
     */
    public function storefront()
    {
        $products = Product::where('user_id', Auth::id())
            ->with(['category.parent', 'mainImage', 'images'])
            ->latest()
            ->get();

        Product::markSavedState($products, Auth::user());

        $facets = $this->filterFacets($products);

        // Drives the "Archive (n)" badge in the header — a soft-deleted
        // product is invisible to the query above but still recoverable.
        $archivedCount = Product::onlyTrashed()
            ->where('user_id', Auth::id())
            ->count();

        return view('store.storefront.index', compact('products', 'archivedCount') + $facets);
    }

    /**
     * Display the farmer-facing product recommendations page.
     */
    public function recommendations(ProductRecommendationService $recommendationService)
    {
        $recommendations = $recommendationService->forFarmer(Auth::user());

        // The shared card/filter components expect an Eloquent collection of
        // plain Product models, not the recommendation wrapper rows.
        $products = new EloquentCollection(
            $recommendations->pluck('product')->all()
        );

        Product::markSavedState($products, Auth::user());

        $currentSeason = Str::lower(SeasonHelper::getCurrentSeason());

        $hasCropReason = fn (array $item): bool => (bool) array_intersect(
            $item['reason_codes'],
            ['crop_formula_match', 'planted_crop_match', 'harvest_history_match'],
        );

        $forYourCrops = $recommendations->filter($hasCropReason)->count();
        $seasonalPicks = $recommendations
            ->filter(fn (array $item): bool => in_array('season_fit', $item['reason_codes'], true))
            ->count();
        $topQuoted = $recommendations
            ->filter(fn (array $item): bool => in_array('top_quoted', $item['reason_codes'], true))
            ->count();

        $storeCount = User::where('role', 'store_owner')
            ->whereNotNull('store_name')
            ->count();

        $facets = $this->filterFacets($products);

        return view('farmer.recommendations.index', compact(
            'recommendations',
            'products',
            'storeCount',
            'currentSeason',
            'forYourCrops',
            'seasonalPicks',
            'topQuoted',
        ) + $facets);
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
     *
     * Returns true when the submission actually wrote or cleared a row, so the
     * caller can record the change in the activity log.
     */
    private function syncSpecDetails(
        Product $product,
        string $relation,
        array $validated,
        array $keys,
        array $attributes
    ): bool {
        if (! $this->wasSubmitted($validated, $keys)) {
            return false;
        }

        if (! $this->hasAnyValue($validated, $keys)) {
            $product->{$relation}()->delete();

            return true;
        }

        $product->{$relation}()->updateOrCreate(
            ['product_id' => $product->id],
            $attributes
        );

        return true;
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

        $product = DB::transaction(function () use ($validated, $request, $category) {
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

            return $product;
        });

        // Written after the transaction so a rolled-back insert never leaves a
        // phantom "product created" entry in the store owner's activity history.
        ActivityLogger::productCreated($product);

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

        $product->load(['category', 'fertilizerDetail', 'registration', 'images', 'documents', 'mainImage', 'cropTypes']);
        $categories = Category::active()->parents()->with('children')->get();
        $cropTypes = app(CropLibraryService::class)->offeredNames();
        $formulas = $product->decisionFormulas()->orderBy('crop_type')->orderBy('season')->orderBy('stage')->get();
        $tab = request()->query('tab', 'details') === 'rules' ? 'rules' : 'details';
        $isEdit = true;

        return view('store.storefront.edit', compact('product', 'categories', 'cropTypes', 'formulas', 'tab', 'isEdit'));
    }

    /**
     * Update the specified product in the database.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorizeProductOwner($product);

        $validated = $request->validated();
        $category = Category::with('parent')->findOrFail($validated['category_id']);

        $payload = [
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
            'stock_quantity'     => $validated['stock_quantity'],
            'stock_unit'         => $validated['stock_unit'] ?? null,
            'reorder_threshold'  => $validated['reorder_threshold'],
            'recommended_season' => $validated['recommended_season'],
            'status'             => $validated['status'],
        ];

        // Snapshot what this request is about to touch so the activity log can
        // report *what* changed ("price: ₱100.00 → ₱120.00") rather than a
        // bare "something was updated".
        $before = $product->only(array_keys($payload));
        $notes = [];

        DB::transaction(function () use ($validated, $request, $product, $payload, &$notes) {
            $product->update($payload);

            $cropLibrary = app(CropLibraryService::class);
            $selectedCropTypes = collect($validated['crop_types'] ?? [])
                ->map(fn (string $cropType) => $cropLibrary->forName($cropType)['name'] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $previousCropTypes = $product->cropTypes->pluck('crop_type')->values()->all();
            $product->cropTypes()->delete();
            $product->cropTypes()->createMany(array_map(
                fn (string $cropType) => ['crop_type' => $cropType],
                $selectedCropTypes
            ));
            if ($previousCropTypes !== $selectedCropTypes) {
                $notes[] = 'crop types updated';
            }

            // Append any newly uploaded images; existing ones are left alone
            // and managed individually from the edit form.
            $this->storeProductImages($product, $request->file('images', []));

            $imagesAdded = count(array_filter($request->file('images', [])));
            if ($imagesAdded > 0) {
                $notes[] = $imagesAdded . ' image' . ($imagesAdded > 1 ? 's' : '') . ' added';
            }

            // Fertilizer Details: create, update, or clear when blanked.
            if ($this->syncSpecDetails(
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
            )) {
                $notes[] = 'fertilizer details updated';
            }

            // Registration Details: create, update, or clear when blanked.
            if ($this->syncSpecDetails(
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
            )) {
                $notes[] = 'registration details updated';
            }
        });

        $change = array_filter(array_merge(
            [$this->describeChanges($before, $product->only(array_keys($payload)))],
            $notes
        ));

        // A no-op resubmit writes nothing — the log stays a record of real changes.
        if ($change !== []) {
            ActivityLogger::productUpdated($product, implode('; ', $change));
        }

        return redirect()
            ->route('store.storefront.index')
            ->with('success', 'Product profile updated successfully.');
    }

    /**
     * Fields whose before/after values are summarised in the activity log.
     */
    private const CHANGE_LABELS = [
        'name'               => 'name',
        'brand'              => 'brand',
        'sku'                => 'SKU',
        'product_type'       => 'type',
        'description'        => 'description',
        'package_size'       => 'package size',
        'package_unit'       => 'package unit',
        'packaging_type'     => 'packaging',
        'price'              => 'price',
        'price_unit'         => 'price unit',
        'stock_quantity'     => 'stock',
        'stock_unit'         => 'stock unit',
        'reorder_threshold'  => 'reorder level',
        'recommended_season' => 'season',
        'status'             => 'status',
        'category'           => 'category',
    ];

    /**
     * Attributes skipped in the change summary (the legacy category label
     * already tells the reader what changed).
     */
    private const CHANGE_SKIP = ['category_id'];

    /**
     * Human-readable "field: old → new" summary of the touched attributes.
     */
    private function describeChanges(array $before, array $after): string
    {
        $parts = [];

        foreach ($before as $key => $old) {
            if (in_array($key, self::CHANGE_SKIP, true) || ! array_key_exists($key, $after)) {
                continue;
            }

            $new = $after[$key];

            // Loose comparison so "10.00" and 10.0 (decimal cast) count as equal.
            if ($old == $new) {
                continue;
            }

            $label = self::CHANGE_LABELS[$key] ?? str_replace('_', ' ', (string) $key);
            $parts[] = "{$label}: " . $this->formatValue($key, $old) . ' → ' . $this->formatValue($key, $new);

            if (count($parts) >= 6) {
                $parts[] = '…';
                break;
            }
        }

        return implode(', ', $parts);
    }

    private function formatValue(string $key, $value): string
    {
        if ($value === null || $value === '') {
            return '(empty)';
        }

        return match ($key) {
            'price'  => '₱' . number_format((float) $value, 2),
            'status', 'recommended_season' => ucfirst(str_replace('_', ' ', (string) $value)),
            'description' => mb_strimwidth((string) $value, 0, 40, '…'),
            default => (string) $value,
        };
    }

    /**
     * Archive the product: a soft delete that hides it everywhere while the
     * row, its images and its specs stay in the database for recovery.
     */
    public function destroy(Product $product)
    {
        $this->authorizeProductOwner($product);

        $product->delete();

        ActivityLogger::productArchived($product);

        return redirect()
            ->route('store.storefront.index')
            ->with('success', 'Product moved to the Archive. Restore it anytime from the Archive screen.');
    }

    /**
     * The store owner's archive (trashed products), newest removals first.
     * The header's "Add Product" modal picks from the active catalog, so the
     * live products travel with the page too.
     */
    public function trash()
    {
        $products = Product::onlyTrashed()
            ->where('user_id', Auth::id())
            ->with(['category', 'mainImage', 'images'])
            ->latest('deleted_at')
            ->get();

        $archivedCount = $products->count();

        $activeProducts = Product::where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->with('mainImage')
            ->orderBy('name')
            ->get();

        return view('store.storefront.trash', compact('products', 'archivedCount', 'activeProducts'));
    }

    /**
     * Move the products checked in the archive's "Add Product" modal into the
     * archive in one request. Anything the store owner does not own (or that
     * is already archived) is silently skipped.
     */
    public function archiveBulk(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->whereIn('id', $validated['product_ids'])
            ->get();

        foreach ($products as $product) {
            $product->delete();

            ActivityLogger::productArchived($product);
        }

        $count = $products->count();

        return redirect()
            ->route('store.products.trash')
            ->with('success', ($count === 1 ? '1 product' : $count . ' products') . ' moved to the Archive.');
    }

    /**
     * Bring an archived product back into the storefront.
     */
    public function restore(Product $product)
    {
        $this->authorizeProductOwner($product);

        abort_unless($product->trashed(), 404);

        $product->restore();

        ActivityLogger::productRestored($product);

        return redirect()
            ->route('store.products.trash')
            ->with('success', "“{$product->name}” is live in your storefront again.");
    }

    /**
     * Permanently remove an archived product: files and spec rows are cleaned
     * up here (the archive screen is the only place this is reachable).
     */
    public function forceDestroy(Product $product)
    {
        $this->authorizeProductOwner($product);

        abort_unless($product->trashed(), 404);

        $name = $product->name;
        $sku = $product->sku;

        DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->file_path);
                Storage::disk('public')->delete($image->thumb_path);
            }

            $product->images()->delete();
            $product->fertilizerDetail()->delete();
            $product->registration()->delete();
            $product->documents()->delete();
            $product->forceDelete();
        });

        ActivityLogger::productDeleted($name, $sku);

        return redirect()
            ->route('store.products.trash')
            ->with('success', 'Product permanently deleted.');
    }

    /**
     * Build the facet data the shared <x-catalog-filters> bar needs from an
     * already-loaded product collection: the category options to offer, and
     * the price bounds to seed the range inputs.
     *
     * The taxonomy is two levels deep, so both the leaf category and its
     * top-level group are offered. A card is tagged with both (see
     * resources/views/components/product-card.blade.php), so picking a parent
     * group returns everything filed underneath it.
     *
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return array{categories: \Illuminate\Support\Collection, priceMin: float, priceMax: float}
     */
    protected function filterFacets($products)
    {
        $categories = $products
            ->flatMap(function (Product $product) {
                $leaf = $product->relationLoaded('category') ? $product->getRelation('category') : null;
                $names = [$product->category_display_name];

                if ($leaf instanceof Category && $leaf->parent_id && $leaf->relationLoaded('parent') && $leaf->parent) {
                    $names[] = (string) $leaf->parent->name;
                }

                return $names;
            })
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->reject(fn ($name) => $name === 'Uncategorized')
            ->unique()
            ->sort()
            ->values();

        return [
            'categories' => $categories,
            'priceMin' => round((float) ($products->min('price') ?? 0), 2),
            'priceMax' => round((float) ($products->max('price') ?? 0), 2),
        ];
    }

    /**
     * Display all active products for farmers to browse.
     *
     * `?store=` narrows the catalog to one store owner's products. The Stores
     * Directory links here from each store card, so a farmer who wants "the
     * rest" of a store's listing lands on that store rather than on every
     * store at once. An unknown or non-store id is ignored rather than
     * erroring, so a stale link still yields a usable catalog.
     */
    public function browse()
    {
        $products = Product::active()
            // category.parent is what lets a card report its top-level group,
            // which the shared filter bar matches on.
            ->with(['category.parent', 'storeOwner', 'mainImage', 'images']);

        if ($storeId = (int) request('store')) {
            $isStore = User::whereKey($storeId)
                ->where('role', 'store_owner')
                ->whereNotNull('store_name')
                ->exists();

            if ($isStore) {
                $products->where('user_id', $storeId);
            }
        }

        $products = $products->orderBy('created_at', 'desc')->get();

        Product::markSavedState($products, Auth::user());

        return view('farmer.products.browse', compact('products') + $this->filterFacets($products));
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
        ]);

        $relatedProducts = Product::active()
            ->with(['mainImage', 'images', 'category'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        // Application rules — the SAME decision_formulas rows the quotation
        // engine reads. CostQuotationEngine groups them by product_id and sums
        // effectiveRate(); QuotationController::formulaTotalsFor() does the
        // same for the result page; the ?tab=rules editor writes them. Reading
        // them here keeps all three on one source of truth instead of this
        // page showing nothing while the quotation quotes from them.
        $formulas = $product->decisionFormulas()
            ->orderBy('crop_type')
            ->orderBy('season')
            ->orderBy('stage')
            ->get();

        return view('farmer.products.show', compact('product', 'relatedProducts', 'formulas'));
    }

    /**
     * Farmer-facing Top Quoted Products page: active products ranked by how
     * many distinct cost quotations include them. Quotations are real
     * purchase-intent requests, so this is a demand signal rather than
     * unverifiable ratings.
     */
    public function topQuoted()
    {
        $products = Product::active()
            ->with(['category.parent', 'storeOwner', 'mainImage'])
            ->topQuoted()
            ->get();

        Product::markSavedState($products, Auth::user());

        $totalQuoted = $products->count();

        // Facet data for the shared <x-catalog-filters> bar, built the same way
        // as the other catalog pages (see ProductController::filterFacets).
        $facets = [
            'categories' => $products
                ->flatMap(function (Product $product) {
                    $cat = $product->getRelation('category');
                    $names = [$product->category_display_name];
                    if ($cat instanceof Category && $cat->parent_id && $cat->relationLoaded('parent') && $cat->parent) {
                        $names[] = (string) $cat->parent->name;
                    }
                    return $names;
                })
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->reject(fn ($name) => $name === 'Uncategorized')
                ->unique()
                ->sort()
                ->values(),
            'priceMin' => round((float) ($products->min('price') ?? 0), 2),
            'priceMax' => round((float) ($products->max('price') ?? 0), 2),
        ];

        return view('farmer.products.top-quoted', compact('products', 'totalQuoted') + $facets);
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

            // Down-scaled on the way in (see ProductImageResizer) so a camera
            // photo never lands in storage at full resolution, and paired with
            // a thumbnail the card/list views serve instead.
            $stored = ProductImageResizer::store($file, 'products/' . $product->id);
            $storedMime = $this->storedMime($stored['path']);

            $product->images()->create([
                'image_type'        => ($isFirst && $promoteFirstToMain) ? 'main' : 'gallery',
                'file_path'         => $stored['path'],
                'thumb_path'        => $stored['thumb_path'],
                'original_filename' => $file->getClientOriginalName(),
                'mime_type'         => $storedMime ?: $file->getMimeType(),
                'file_size'         => Storage::disk('public')->size($stored['path']),
            ]);

            $isFirst = false;
        }
    }

    /**
     * Mime type of the file as it was stored: an opaque PNG is flattened to
     * JPEG on the way in, so the uploaded file's type can no longer be trusted.
     */
    private function storedMime(string $path): ?string
    {
        try {
            return Storage::disk('public')->mimeType($path);
        } catch (\Throwable) {
            return null;
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

        ActivityLogger::productUpdated($product, 'main image changed');

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

        ActivityLogger::productUpdated($product, 'image removed');

        return back()->with('success', 'Image removed successfully.');
    }

    /**
     * Toggle whether the signed-in user has saved this product for later.
     */
    public function toggleSave(Product $product)
    {
        $user = Auth::user();

        abort_unless($user, 401);

        // Only products a shopper could actually open on their own (active
        // listings) can be saved; a store owner may still save their own draft.
        abort_unless($product->status === 'active' || $product->user_id === $user->id, 404);

        if ($user->savedProducts()->whereKey($product->id)->exists()) {
            $user->savedProducts()->detach($product);
            $saved = false;
        } else {
            $user->savedProducts()->attach($product);
            $saved = true;
        }

        return response()->json(['saved' => $saved]);
    }

    private function authorizeProductOwner(Product $product): void
    {
        if ($product->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}