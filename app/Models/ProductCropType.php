<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCropType extends Model
{
    protected $fillable = [
        'product_id',
        'crop_type',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
