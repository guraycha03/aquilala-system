<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Crop extends Model
{
    protected $fillable = [
        'name',
        'category',
        'description',
        'is_category_placeholder',
    ];

    /**
     * The placeholder flag is a fact about the row, not a display option, so it
     * reads as a boolean everywhere. Left out of $casts it arrived from the
     * database as 0/1 and compared falsily in ways that were easy to misread.
     */
    protected $casts = [
        'is_category_placeholder' => 'boolean',
    ];

    /**
     * True when this row is a category label - "Vegetables", "Root Crops" - and
     * not a plant a farmer can put in the ground.
     */
    public function isPlaceholder(): bool
    {
        return $this->is_category_placeholder === true;
    }

    public function productApplications(): HasMany
    {
        return $this->hasMany(ProductApplication::class);
    }
}
