<?php

namespace App\Models;

use App\Services\ActivityLogger;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerCrop extends Model
{
    protected $fillable = [
        'user_id',
        'crop_id',
        'variety',
        'variety_key',
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
        'confirmation_status',
        'confirmed_by',
        'confirmed_at',
        'admin_remarks',
        'actual_yield',
        'estimated_cost',
        'notes',
        'harvest_report_id',
    ];

    protected $casts = [
        'planting_date' => 'date',
        'expected_harvest_date' => 'date',
        'actual_harvest_date' => 'date',
        'confirmed_at' => 'datetime',
        'land_area' => 'decimal:2',
        'estimated_yield' => 'decimal:2',
        'actual_yield' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
    ];

    public const CONFIRMATION_PENDING = 'pending';
    public const CONFIRMATION_CONFIRMED = 'confirmed';
    public const CONFIRMATION_REJECTED = 'rejected';

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

    public function harvestReport(): BelongsTo
    {
        return $this->belongsTo(HarvestReport::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmation_status === self::CONFIRMATION_CONFIRMED;
    }

    public function isPending(): bool
    {
        return $this->confirmation_status === self::CONFIRMATION_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->confirmation_status === self::CONFIRMATION_REJECTED;
    }

    public function getConfirmationStatusLabelAttribute(): string
    {
        return match ($this->confirmation_status) {
            self::CONFIRMATION_CONFIRMED => 'Confirmed',
            self::CONFIRMATION_REJECTED => 'Rejected',
            default => 'Pending',
        };
    }

    public function getConfirmationStatusColorAttribute(): string
    {
        return match ($this->confirmation_status) {
            self::CONFIRMATION_CONFIRMED => '#16a34a',
            self::CONFIRMATION_REJECTED => '#dc2626',
            default => '#d97706',
        };
    }

    /**
     * Keep the linked Harvest Report in sync with this crop batch.
     * Called when a batch reaches the "harvested" stage. Creates a new
     * report when none is linked yet, or updates the linked one so the
     * farmer's My Crops and the Harvest Reports page always match.
     */
    public function syncHarvestReport(): ?HarvestReport
    {
        if ($this->growth_stage !== 'harvested' || $this->actual_yield === null) {
            return $this->harvestReport;
        }

        $report = $this->harvestReport;

        $data = [
            'user_id' => $this->user_id,
            'crop_type' => $this->crop?->name ?? 'Unknown',
            'season' => $this->season,
            'land_area' => $this->land_area,
            'actual_yield_metric_tons' => $this->actual_yield,
            'actual_total_expenses' => $this->estimated_cost ?? $report?->actual_total_expenses ?? 0,
            'harvest_date' => $this->actual_harvest_date ?? now()->toDateString(),
        ];

        if ($report) {
            $report->update($data);
        } else {
            $data['quotation_id'] = null;
            $report = HarvestReport::create($data);
            $this->update(['harvest_report_id' => $report->id]);
            ActivityLogger::harvestReportCreated($report);
        }

        return $report;
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
            'seeding' => '#4A7A54',
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
        if (! $this->expected_harvest_date) {
            return null;
        }
        $days = (int) now()->diffInDays($this->expected_harvest_date, false);

        return $days < 0 ? 0 : $days;
    }

    public function getProgressPercentAttribute(): int
    {
        if (! $this->expected_harvest_date || ! $this->planting_date) {
            return 0;
        }
        $total = $this->planting_date->diffInDays($this->expected_harvest_date);
        $elapsed = $this->planting_date->diffInDays(now());
        $pct = (int) round(($elapsed / max($total, 1)) * 100);

        return min(max($pct, 0), 100);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('growth_stage', ['harvested', 'failed']);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('confirmation_status', self::CONFIRMATION_CONFIRMED);
    }

    public function scopePendingConfirmation($query)
    {
        return $query->where('confirmation_status', self::CONFIRMATION_PENDING);
    }

    public function scopeRejected($query)
    {
        return $query->where('confirmation_status', self::CONFIRMATION_REJECTED);
    }

    /**
     * Auto-generate a unique batch name in the form
     *   {CROP}-FIELD{N}-{DDMMYY}
     * e.g. TOMATO-FIELD1-160926. The field number keeps growing per crop
     * until the full name is unique for the given user.
     */
    public static function uniqueBatchName(int $userId, int $cropId, string $cropName, $plantingDate, ?int $exceptId = null): string
    {
        $slug = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '', (string) $cropName));
        $date = $plantingDate instanceof CarbonInterface
            ? $plantingDate->format('dmy')
            : Carbon::parse($plantingDate)->format('dmy');

        $fieldNum = static::where('user_id', $userId)
            ->where('crop_id', $cropId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->count() + 1;

        do {
            $name = $slug.'-FIELD'.$fieldNum.'-'.$date;
            $exists = static::where('user_id', $userId)
                ->where('batch_name', $name)
                ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
                ->exists();
            if (! $exists) {
                return $name;
            }
            $fieldNum++;
        } while (true);
    }

    public function scopeForSeason($query, string $season)
    {
        return $query->where('season', $season);
    }
}
