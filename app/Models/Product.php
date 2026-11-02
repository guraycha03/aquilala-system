<?php

namespace App\Models;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /**
     * Products are archived, never hard-deleted, from the storefront: the
     * row, its images and its spec records survive so the listing can be
     * restored. The global scope hides trashed rows from every query except
     * the store owner's Archive screen (Product::onlyTrashed()).
     */
    use SoftDeletes;
    /**
     * Upper bound on how many images one product may hold, across both the
     * create and update forms. Enforced server side in the form requests and
     * mirrored by the image picker so the UI never offers slots the backend
     * will reject.
     */
    public const MAX_IMAGES = 10;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'brand',
        'sku',
        'product_type',
        'description',
        'package_size',
        'package_unit',
        'packaging_type',
        'price',
        'price_unit',
        'stock_level',
        'stock_quantity',
        'stock_unit',
        'reorder_threshold',
        'views_count',
        'recommended_season',
        'status',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'package_size' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reorder_threshold' => 'integer',
            'views_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // `stock_level` predates `stock_quantity` and is no longer written by
        // the forms, which left every row at 0 while the real value lived in
        // `stock_quantity`. Mirror it on each save so the two can never drift
        // apart and raw queries, exports and reports stay correct.
        static::saving(function (self $product) {
            if ($product->stock_quantity !== null) {
                $product->stock_level = (int) $product->stock_quantity;
            }
        });
    }

    /**
     * The label stored in the legacy `products.category` text column.
     *
     * That column has always held the TOP-LEVEL group name ("Fertilizer",
     * "Seeds", "Crop Protection") while `category_id` points at the specific
     * sub-category, so it resolves to the parent and falls back to the
     * category's own name for top-level rows. Writing the leaf name instead
     * would make new rows inconsistent with the existing catalog.
     */
    public static function legacyCategoryLabel(?Category $category): string
    {
        if (! $category) {
            return 'Uncategorized';
        }

        if (! $category->relationLoaded('parent')) {
            $category->loadMissing('parent');
        }

        return $category->parent?->name ?? $category->name;
    }

    public function storeOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function fertilizerDetail(): HasOne
    {
        return $this->hasOne(FertilizerDetail::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ProductApplication::class);
    }

    public function registration(): HasOne
    {
        return $this->hasOne(ProductRegistration::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany('id', 'min')->where('image_type', 'main');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class);
    }

    public function decisionFormulas(): HasMany
    {
        return $this->hasMany(DecisionFormula::class);
    }

    public function cropTypes(): HasMany
    {
        return $this->hasMany(ProductCropType::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_products')->withTimestamps();
    }

    /**
     * Tag a batch of products with a transient `saved` flag so the shared card
     * can draw its bookmark without a query per card. One lookup for the whole
     * batch regardless of how large the catalog page is.
     */
    public static function markSavedState(
        EloquentCollection $products,
        ?User $user,
    ): void {
        $savedIds = $user?->savedProducts()->pluck('saved_products.product_id')->all() ?? [];

        foreach ($products as $product) {
            $product->saved = in_array((int) $product->getKey(), $savedIds, true);
        }
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByMerchant($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeTopViewed($query)
    {
        return $query->orderByDesc('views_count');
    }

    /**
     * Products ranked by distinct farmer quotations that include them.
     * A quotation is a real cost-planning request, so this counts actual
     * purchase intent rather than unverifiable opinions.
     */
    public function scopeTopQuoted($query)
    {
        return $query->whereHas('quotationItems')
            ->withCount('quotationItems')
            ->orderByDesc('quotation_items_count');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->reorder_threshold;
    }

    /**
     * Canonical casing for the units merchants type into `package_unit`.
     * Anything unrecognised is passed through as the merchant wrote it.
     *
     * Public so the spreadsheet importer normalises units the same way the
     * manual form and the live name preview do.
     */
    public static function normalizeUnit(?string $unit): ?string
    {
        $unit = $unit === null ? '' : trim($unit);

        if ($unit === '') {
            return null;
        }

        return match (strtolower($unit)) {
            'kg', 'kgs', 'kilogram', 'kilograms' => 'kg',
            'g', 'gram', 'grams'                 => 'g',
            'ml'                                 => 'mL',
            'l', 'ltr', 'liter', 'liters', 'litre', 'litres' => 'L',
            'piece', 'pieces'                    => 'piece',
            'pcs'                                => 'pcs',
            default                              => $unit,
        };
    }

    /**
     * Suggested values for the measure unit field. Free text is still allowed
     * — this only powers the datalist so merchants pick the usual casing.
     *
     * @return array<int, string>
     */
    public static function sizeUnitSuggestions(): array
    {
        return ['kg', 'g', 'mL', 'L', 'piece', 'pcs', 'pack', 'packet', 'sachet', 'bag', 'sack', 'box', 'bottle', 'can', 'roll', 'm'];
    }

    /**
     * The measure part of a product, formatted for display — e.g. "50 kg",
     * "500 mL", "1 L". Null when the product has no size on record.
     */
    public function getSizeLabelAttribute(): ?string
    {
        $size = $this->package_size;

        if ($size === null || $size === '') {
            return null;
        }

        $number = rtrim(rtrim(number_format((float) $size, 2, '.', ''), '0'), '.');
        $number = $number === '' ? '0' : $number;

        $unit = self::normalizeUnit($this->package_unit);

        return $unit === null ? $number : $number . ' ' . $unit;
    }

    /**
     * Full product name with the measure appended, for places that want to
     * read like the product label — catalog cards, detail headers, tables.
     * Plain `name` stays measure-free for search, filters and compact lists.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = trim((string) $this->name);
        $size = $this->size_label;

        if ($size === null) {
            return $name;
        }

        // Merchants sometimes still type the measure into the name. Drop that
        // trailing copy so the label reads "Urea 50 kg" rather than
        // "Urea 50 kg — 50 kg".
        $stripped = preg_replace(
            '/\s*[\x{2014}\x{2013}\-]\s*' . preg_quote($size, '/') . '\s*$/u',
            '',
            $name
        );

        if (is_string($stripped) && $stripped !== '') {
            $name = trim($stripped);
        }

        return $name === '' ? $size : $name . ' — ' . $size;
    }

    /**
     * Every product image with the main one first, then the rest by upload
     * order. Powers the gallery on the detail page and the image manager on
     * the edit form. Falls back to a query when `images` was not eager
     * loaded, so callers stay correct at the cost of one extra query.
     *
     * @return \Illuminate\Support\Collection<int, ProductImage>
     */
    public function getGalleryAttribute()
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();

        return $images
            ->sortBy(fn (ProductImage $image) => [$image->image_type === 'main' ? 0 : 1, $image->id])
            ->values();
    }

    public function getCategoryDisplayNameAttribute(): string
    {
        // The products table keeps a legacy string `category` column that
        // shadows the `category()` relation on attribute access. Resolve the
        // display name from the relation (when loaded, preferred) with the
        // legacy string as fallback.
        if ($this->relationLoaded('category') && $this->relations['category'] instanceof Category) {
            $name = $this->relations['category']->name;
            if (is_string($name) && trim($name) !== '') {
                return $name;
            }
        }

        $legacy = $this->getRawOriginal('category');
        return is_string($legacy) && trim($legacy) !== '' ? $legacy : 'Uncategorized';
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->status === 'discontinued') {
            return 'Discontinued';
        }
        if ($this->status === 'out_of_stock' || $this->stock_quantity <= 0) {
            return 'Out of Stock';
        }
        if ($this->is_low_stock) {
            return 'Low Stock — ' . $this->stock_quantity . ' ' . ($this->stock_unit ?? 'pieces') . ' remaining';
        }
        return 'In Stock — ' . $this->stock_quantity . ' ' . ($this->stock_unit ?? 'pieces') . ' available';
    }

    public function getCoverageEstimateAttribute(): ?string
    {
        $app = $this->applications->first();
        if (!$app || !$this->package_size || !$app->application_rate_min) {
            return null;
        }
        $coverage = $app->getEstimatedCoverage((float)$this->package_size, $this->package_unit);
        if (!$coverage) {
            return null;
        }
        if ($coverage['min'] === $coverage['max']) {
            return 'Approximately ' . $coverage['min'] . ' ' . $coverage['unit'] . ' per ' .
                $this->package_size . ' ' . ($this->package_unit ?? 'piece') . ' package';
        }
        return 'Approximately ' . $coverage['min'] . '–' . $coverage['max'] . ' ' . $coverage['unit'] . ' per ' .
            $this->package_size . ' ' . ($this->package_unit ?? 'piece') . ' package';
    }

    /**
     * The facets the shared <x-catalog-filters> bar filters and sorts on,
     * keyed by the data-cf-* attribute the engine reads.
     *
     * Every catalog surface renders its cards from this one method, including
     * the two that use bespoke markup, so a facet can never be defined twice
     * and drift apart between pages.
     *
     * @return array<string, float|int|string>
     */
    public function catalogFacets(): array
    {
        $category = trim((string) $this->category_display_name);

        // `products.category` is a legacy text column that shadows the
        // `category()` relation on attribute access, so the relation has to be
        // read explicitly rather than through the property.
        $leaf = $this->relationLoaded('category') ? $this->getRelation('category') : null;
        $leaf = $leaf instanceof Category ? $leaf : null;

        // The taxonomy is two levels deep, so a product belongs to both its
        // leaf category and its top-level group. Filtering on either works.
        $group = ($leaf && $leaf->parent_id && $leaf->relationLoaded('parent') && $leaf->parent)
            ? (string) $leaf->parent->name
            : $category;

        $season = in_array($this->recommended_season, ['wet', 'dry'], true)
            ? $this->recommended_season
            : 'both';

        $quantity = (int) ($this->stock_quantity ?? 0);
        $stock = $quantity <= 0
            ? 'out'
            : ($quantity <= (int) ($this->reorder_threshold ?? 5) ? 'low' : 'in');

        // Season and stock are spelled out in the haystack so a search for
        // "wet season" or "out of stock" finds the product, not just a title.
        $seasonWord = $season === 'wet' ? 'wet season' : ($season === 'dry' ? 'dry season' : 'all seasons year round');
        $stockWord = $stock === 'out' ? 'out of stock' : ($stock === 'low' ? 'low stock' : 'in stock');

        return [
            'data-cf-search' => mb_strtolower(trim(implode(' ', array_filter([
                $this->display_name,
                $this->name,
                $this->brand,
                $this->sku,
                $category,
                $group,
                $seasonWord,
                $stockWord,
                $this->description,
            ])))),
            'data-cf-name' => mb_strtolower($this->display_name),
            'data-cf-category' => mb_strtolower($category),
            'data-cf-group' => mb_strtolower($group),
            'data-cf-season' => $season,
            'data-cf-stock' => $stock,
            'data-cf-price' => (float) $this->price,
            'data-cf-date' => optional($this->created_at)->getTimestamp() ?? 0,
        ];
    }
}
