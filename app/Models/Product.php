<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'user_id', 'sku', 'name', 'category', 'price', 
        'stock_level', 'reorder_threshold', 'recommended_season', 'description'
    ];

    public function storeOwner(): BelongsTo
    {
        // FIX: Changed $table to $this
        return $this->belongsTo(User::class, 'user_id');
    }

    public function decisionFormulas(): HasMany
    {
        return $this->hasMany(DecisionFormula::class);
    }
}