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
    ];

    public function productApplications(): HasMany
    {
        return $this->hasMany(ProductApplication::class);
    }
}
