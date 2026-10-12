<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An entry on the association's shared calendar — free input releases,
 * water scheduling, meetings/seminars and other association events.
 * Only admins create them; farmers read them on the day they arrive.
 */
class AssociationEvent extends Model
{
    public const TYPE_FREE_INPUT     = 'free_input';
    public const TYPE_WATER_SCHEDULE = 'water_schedule';
    public const TYPE_MEETING        = 'meeting';
    public const TYPE_OTHER          = 'other';

    public const TYPES = [
        self::TYPE_FREE_INPUT     => 'Free Farming Input',
        self::TYPE_WATER_SCHEDULE => 'Water Scheduling',
        self::TYPE_MEETING        => 'Meeting / Seminar',
        self::TYPE_OTHER          => 'Other Event',
    ];

    public const TYPE_COLORS = [
        self::TYPE_FREE_INPUT     => '#16a34a',
        self::TYPE_WATER_SCHEDULE => '#0284c7',
        self::TYPE_MEETING        => '#7c3aed',
        self::TYPE_OTHER          => '#d97706',
    ];

    protected $fillable = [
        'title',
        'event_type',
        'event_date',
        'start_time',
        'end_time',
        'location',
        'description',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────────
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->orderBy('start_time');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->whereDate('event_date', '<', now()->toDateString())
            ->orderByDesc('event_date')
            ->orderByDesc('start_time');
    }

    // ── Helpers ──────────────────────────────────────────────────
    public function isToday(): bool
    {
        return $this->event_date?->isToday();
    }

    public function isPast(): bool
    {
        return $this->event_date?->lt(today());
    }

    // ── Display ──────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->event_type] ?? 'Other Event';
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->event_type] ?? self::TYPE_COLORS[self::TYPE_OTHER];
    }

    /** "2:00 PM" when a time is set, null for all-day events. */
    public function getStartTimeLabelAttribute(): ?string
    {
        return $this->start_time?->format('g:i A');
    }

    public function getEndTimeLabelAttribute(): ?string
    {
        return $this->end_time?->format('g:i A');
    }

    /** "2:00 PM – 4:00 PM", "2:00 PM", or null for all-day. */
    public function getTimeLabelAttribute(): ?string
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->format('g:i A').' – '.$this->end_time->format('g:i A');
        }

        return $this->start_time?->format('g:i A');
    }
}
