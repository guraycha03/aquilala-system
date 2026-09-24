<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
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

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Reviews that have been moderated and approved by an admin.
     * Farmer-facing ratings and the public review list only use these.
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->approved();
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

    public function scopeTopRated($query)
    {
        return $query->withCount('approvedReviews')
            ->withAvg('approvedReviews as avg_rating', 'rating')
            ->having('approved_reviews_count', '>', 0)
            ->orderByDesc('avg_rating')
            ->orderByDesc('approved_reviews_count');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->reorder_threshold;
    }

    private function reviewSource()
    {
        return $this->relationLoaded('approvedReviews')
            ? $this->approvedReviews
            : $this->reviews;
    }

    public function getAverageRatingAttribute(): ?float
    {
        $reviews = $this->reviewSource();

        if ($reviews->isEmpty()) {
            return null;
        }

        return round((float) $reviews->avg('rating'), 1);
    }

    public function getReviewCountAttribute(): int
    {
        return $this->reviewSource()->count();
    }

    public function getRatingBarsAttribute(): array
    {
        $counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        foreach ($this->reviewSource() as $review) {
            $counts[(int) $review->rating] = ($counts[(int) $review->rating] ?? 0) + 1;
        }

        return $counts;
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
}
