<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionFormula extends Model
{
    /**
     * Application stages a rule can apply at. A formula is no longer one flat
     * number: the same product can be scheduled at planting (basal), again
     * during growth (topdress), and so on — the engines add the stages up.
     *
     * `standard` is the whole-season/single-application default so rows that
     * predate stages keep working unchanged.
     */
    public const STAGES = [
        'standard'   => 'Standard (single application)',
        'basal'      => 'Basal (at planting)',
        'topdress'   => 'Topdress (during growth)',
        'foliar'     => 'Foliar (leaf spray)',
        'pest_control' => 'Pest control (as needed)',
    ];

    /**
     * Applies to unsaved instances too: the column default only appears once
     * the row is re-read from the database.
     */
    protected $attributes = ['stage' => 'standard'];

    protected $fillable = [
        'crop_type',
        'season',
        'stage',
        'product_id',
        'rate_per_hectare',
        'min_rate_per_hectare',
        'max_rate_per_hectare',
        'unit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rate_per_hectare'     => 'decimal:2',
            'min_rate_per_hectare' => 'decimal:2',
            'max_rate_per_hectare' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Store crop types consistently as Title Case (e.g., "Rice", "Sweet Corn")
     * so the rule engine can always match regardless of input casing.
     */
    public function setCropTypeAttribute($value): void
    {
        $this->attributes['crop_type'] = ucwords(strtolower(trim((string) $value)));
    }

    /**
     * Case-insensitive crop matching — protects against "rice" vs "Rice" mismatches.
     */
    public function scopeWhereCrop($query, string $cropType)
    {
        return $query->whereRaw('LOWER(crop_type) = ?', [strtolower(trim($cropType))]);
    }

    /**
     * Human label for the stage badge (falls back to the raw value for any
     * stage a future migration added without updating this list).
     */
    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? ucfirst(str_replace('_', ' ', (string) $this->stage));
    }

    /**
     * The rate the engines actually apply: the entered rate clamped into the
     * min/max guardrails, so a typo or an outdated entry can never push a
     * quotation outside the agronomic safety window.
     */
    public function effectiveRate(): float
    {
        $rate = (float) $this->rate_per_hectare;

        if ($this->min_rate_per_hectare !== null && $rate < (float) $this->min_rate_per_hectare) {
            $rate = (float) $this->min_rate_per_hectare;
        }

        if ($this->max_rate_per_hectare !== null && $rate > (float) $this->max_rate_per_hectare) {
            $rate = (float) $this->max_rate_per_hectare;
        }

        return $rate;
    }

    /**
     * Whether clamping actually moved the rate (worth telling the user about).
     */
    public function rateNeedsClamp(): bool
    {
        return abs($this->effectiveRate() - (float) $this->rate_per_hectare) > 0.001;
    }
}
