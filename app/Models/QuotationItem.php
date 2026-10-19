<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id', 'product_id', 'product_name',
        'price_at_generation', 'calculated_quantity', 'subtotal',
        'override_price', 'override_quantity', 'is_deferred', 'priority',
    ];

    protected $casts = [
        'price_at_generation' => 'decimal:2',
        'calculated_quantity' => 'decimal:2',
        'subtotal'            => 'decimal:2',
        'override_price'      => 'decimal:2',
        'override_quantity'   => 'decimal:2',
        'is_deferred'         => 'boolean',
    ];

    /**
     * The effective price after store override (or default price_at_generation).
     */
    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->override_price ?? $this->price_at_generation);
    }

    /**
     * The effective quantity after store override (or default calculated_quantity).
     */
    public function getEffectiveQuantityAttribute(): float
    {
        return (float) ($this->override_quantity ?? $this->calculated_quantity);
    }

    /**
     * Recalculate subtotal based on effective price & quantity.
     */
    public function getEffectiveSubtotalAttribute(): float
    {
        return $this->effective_price * $this->effective_quantity;
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
