<?php

namespace App\Models;

use Carbon\CarbonInterface;
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

    /**
     * Where an event sits on the timeline. Never stored — recomputed from
     * the date/time fields on every read so a notice written last week
     * cannot keep calling a finished event "upcoming".
     */
    public const STATE_UPCOMING = 'upcoming'; // not started yet
    public const STATE_ONGOING  = 'ongoing';  // started, not finished
    public const STATE_DONE     = 'done';     // date passed (or ended)

    public const STATE_LABELS = [
        self::STATE_UPCOMING => 'Upcoming',
        self::STATE_ONGOING  => 'Happening now',
        self::STATE_DONE     => 'Done',
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
        'reminded_at' => 'datetime',
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

    /**
     * Live status of this event: 'upcoming' until the start time passes,
     * 'ongoing' while it is running, 'done' once the date (or end time)
     * is behind us. All-day events count as ongoing for the whole day.
     */
    public function state(): string
    {
        return self::computeState($this->event_date, $this->start_time, $this->end_time);
    }

    /**
     * Same computation, detached from a model instance, so a notification
     * row can rebuild the state from its stored metadata after the event
     * itself has been deleted. Times are compared as times of day (both
     * sides are wall-clock on the event's date), so parsing a bare
     * "14:00:00" against today's clock is always correct.
     */
    public static function computeState(CarbonInterface $date, ?CarbonInterface $start = null, ?CarbonInterface $end = null): string
    {
        if ($date->lt(today())) {
            return self::STATE_DONE;
        }

        if ($date->gt(today())) {
            return self::STATE_UPCOMING;
        }

        // The event is dated today: narrow it down by its times, if any.
        $now = now()->format('H:i:s');

        if ($start && $now < $start->format('H:i:s')) {
            return self::STATE_UPCOMING;
        }

        if ($end && $now > $end->format('H:i:s')) {
            return self::STATE_DONE;
        }

        return self::STATE_ONGOING;
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
