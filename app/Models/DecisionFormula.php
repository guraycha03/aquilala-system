<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionFormula extends Model
{
    protected $fillable = ['crop_type', 'season', 'product_id', 'rate_per_hectare', 'unit', 'notes'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}