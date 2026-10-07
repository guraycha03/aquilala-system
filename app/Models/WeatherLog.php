<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single dated weather event recorded by an admin.
 *
 * The whole point of this model is the date range: harvest reports carry a
 * harvest_date, these carry start_date/end_date, and the two are lined up in
 * HarvestInsightService to turn "yield dropped" into "yield dropped during a
 * logged severe typhoon".
 */
class WeatherLog extends Model
{
    /** Controlled vocabulary — keeps the AI's wording and the UI filters stable. */
    public const TYPES = [
        'typhoon'    => 'Typhoon',
        'flood'      => 'Flooding',
        'heavy_rain' => 'Heavy Rainfall',
        'drought'    => 'Drought',
        'heat_wave'  => 'Heat Wave',
        'storm_surge'=> 'Storm Surge',
        'el_nino'    => 'El Niño',
        'la_nina'    => 'La Niña',
        'landslide'  => 'Landslide',
        'other'      => 'Other',
    ];

    /** Weakest to strongest. Order matters — used for sorting and comparisons. */
    public const SEVERITIES = [
        'minor'    => 'Minor',
        'moderate' => 'Moderate',
        'severe'   => 'Severe',
        'extreme'  => 'Extreme',
    ];

    protected $fillable = [
        'logged_by', 'event_type', 'severity', 'title',
        'start_date', 'end_date', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    /**
     * Last day of the event — a single-day event has no end_date.
     */
    public function endDate(): Carbon
    {
        return $this->end_date ?? $this->start_date;
    }

    /**
     * Does this event touch the window $from..$to at all?
     *
     * Two ranges fail to overlap only when one sits entirely before the other,
     * which is the only case worth excluding.
     */
    public function overlaps(Carbon $from, Carbon $to): bool
    {
        return $this->start_date->lte($to) && $this->endDate()->gte($from);
    }

    /** Human label, falling back to the raw key for unknown legacy values. */
    public function typeLabel(): string
    {
        return self::TYPES[$this->event_type] ?? ucfirst(str_replace('_', ' ', $this->event_type));
    }

    public function severityLabel(): string
    {
        return self::SEVERITIES[$this->severity] ?? ucfirst($this->severity);
    }

    /** "Sep 10 – 13, 2026", or "Sep 10, 2026" when it is a single day. */
    public function dateRangeLabel(): string
    {
        if ($this->end_date === null || $this->end_date->isSameDay($this->start_date)) {
            return $this->start_date->format('M d, Y');
        }

        // Same month and year reads better as "Sep 10 – 13, 2026".
        if ($this->start_date->isSameMonth($this->end_date) && $this->start_date->isSameYear($this->end_date)) {
            return $this->start_date->format('M d') . ' – ' . $this->end_date->format('d, Y');
        }

        return $this->start_date->format('M d, Y') . ' – ' . $this->end_date->format('M d, Y');
    }

    /** Events that touch the given window, strongest and newest first. */
    public function scopeOverlapping(Builder $query, Carbon $from, Carbon $to): Builder
    {
        return $query->where('start_date', '<=', $to)->where(function ($q) use ($from) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $from);
        });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('event_type', $type);
    }
}
