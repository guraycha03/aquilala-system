<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerCrop extends Model
{
    protected $fillable = [
        'user_id',
        'crop_id',
        'product_id',
        'batch_name',
        'land_area',
        'land_area_unit',
        'planting_date',
        'expected_harvest_date',
        'actual_harvest_date',
        'growth_stage',
        'season',
        'estimated_yield',
        'yield_unit',
        'actual_yield',
        'estimated_cost',
        'notes',
    ];

    protected $casts = [
        'planting_date' => 'date',
        'expected_harvest_date' => 'date',
        'actual_harvest_date' => 'date',
        'land_area' => 'decimal:2',
        'estimated_yield' => 'decimal:2',
        'actual_yield' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getGrowthStageLabelAttribute(): string
    {
        return match ($this->growth_stage) {
            'land_prep' => 'Land Preparation',
            'seeding' => 'Seeding / Sowing',
            'vegetative' => 'Vegetative Growth',
            'reproductive' => 'Flowering / Fruiting',
            'ripening' => 'Ripening',
            'harvested' => 'Harvested',
            'failed' => 'Failed',
            default => ucfirst($this->growth_stage),
        };
    }

    public function getGrowthStageColorAttribute(): string
    {
        return match ($this->growth_stage) {
            'land_prep' => '#78716c',
            'seeding' => '#2563eb',
            'vegetative' => '#16a34a',
            'reproductive' => '#d97706',
            'ripening' => '#ea580c',
            'harvested' => '#6d28d9',
            'failed' => '#dc2626',
            default => '#64748b',
        };
    }

    public function getDaysSincePlantingAttribute(): int
    {
        return (int) $this->planting_date->diffInDays(now());
    }

    public function getDaysUntilHarvestAttribute(): ?int
    {
        if (!$this->expected_harvest_date) return null;
        $days = (int) now()->diffInDays($this->expected_harvest_date, false);
        return $days < 0 ? 0 : $days;
    }

    public function getProgressPercentAttribute(): int
    {
        if (!$this->expected_harvest_date || !$this->planting_date) return 0;
        $total = $this->planting_date->diffInDays($this->expected_harvest_date);
        $elapsed = $this->planting_date->diffInDays(now());
        $pct = (int) round(($elapsed / max($total, 1)) * 100);
        return min(max($pct, 0), 100);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('growth_stage', ['harvested', 'failed']);
    }

    public function scopeForSeason($query, string $season)
    {
        return $query->where('season', $season);
    }
}
