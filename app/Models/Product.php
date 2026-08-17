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

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->reorder_threshold;
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
