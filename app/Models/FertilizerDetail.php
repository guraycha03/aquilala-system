<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FertilizerDetail extends Model
{
    protected $fillable = [
        'product_id',
        'nitrogen_percentage',
        'phosphorus_percentage',
        'potassium_percentage',
        'form_type',
        'application_rate_min',
        'application_rate_max',
        'application_rate_unit',
        'application_method',
        'application_timing',
    ];

    protected function casts(): array
    {
        return [
            'nitrogen_percentage' => 'decimal:2',
            'phosphorus_percentage' => 'decimal:2',
            'potassium_percentage' => 'decimal:2',
            'application_rate_min' => 'decimal:2',
            'application_rate_max' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getNpkDisplayAttribute(): string
    {
        $parts = [];
        if ($this->nitrogen_percentage !== null) {
            $parts[] = "N {$this->nitrogen_percentage}%";
        }
        if ($this->phosphorus_percentage !== null) {
            $parts[] = "P {$this->phosphorus_percentage}%";
        }
        if ($this->potassium_percentage !== null) {
            $parts[] = "K {$this->potassium_percentage}%";
        }
        return implode(' | ', $parts);
    }
}
