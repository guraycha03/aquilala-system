<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductApplication extends Model
{
    protected $fillable = [
        'product_id',
        'crop_id',
        'application_rate_min',
        'application_rate_max',
        'application_rate_unit',
        'application_method',
        'application_timing',
        'notes',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function getEstimatedCoverage(float $packageSize, ?string $packageUnit): ?array
    {
        if (!$this->application_rate_min) {
            return null;
        }

        $min = $this->application_rate_min;
        $max = $this->application_rate_max ?? $min;
        $unit = $this->application_rate_unit ?? 'ha';

        $coverageMin = $packageSize / $max;
        $coverageMax = $packageSize / $min;

        return [
            'min' => round($coverageMin, 2),
            'max' => round($coverageMax, 2),
            'unit' => $unit,
        ];
    }
}
