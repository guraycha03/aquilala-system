<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionFormula extends Model
{
    protected $fillable = ['crop_type', 'season', 'product_id', 'rate_per_hectare', 'unit', 'notes'];

    protected function casts(): array
    {
        return [
            'rate_per_hectare' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Store crop types consistently as Title Case (e.g., "Rice", "Sweet Corn")
     * so the rule engine can always match regardless of input casing.
     */
    public function setCropTypeAttribute($value): void
    {
        $this->attributes['crop_type'] = ucwords(strtolower(trim((string) $value)));
    }

    /**
     * Case-insensitive crop matching — protects against "rice" vs "Rice" mismatches.
     */
    public function scopeWhereCrop($query, string $cropType)
    {
        return $query->whereRaw('LOWER(crop_type) = ?', [strtolower(trim($cropType))]);
    }
}