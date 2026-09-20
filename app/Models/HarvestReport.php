<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarvestReport extends Model
{
    protected $fillable = [
        'user_id', 'quotation_id', 'crop_type', 'season', 
        'land_area', 'actual_yield_metric_tons', 'actual_total_expenses', 'harvest_date'
    ];

    protected $casts = [
        'harvest_date'             => 'date',
        'land_area'                => 'decimal:2',
        'actual_yield_metric_tons' => 'decimal:2',
        'actual_total_expenses'    => 'decimal:2',
    ];

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}