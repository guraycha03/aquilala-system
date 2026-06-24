<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Quotation extends Model
{
    protected $fillable = [
        'user_id', 'quotation_number', 'crop_type', 
        'season', 'land_area', 'estimated_total_cost', 'valid_until'
    ];

    protected $casts = [
        'valid_until' => 'datetime',
    ];

    // Read-only accessor attribute checking if item prices have passed the confirmation timeframe
    public function getIsExpiredAttribute(): bool
    {
        return Carbon::now()->isAfter($this->valid_until);
    }


    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }
}