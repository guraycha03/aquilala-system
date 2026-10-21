<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'actor_id',
        'type',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
        'read_at',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at'  => 'datetime',
    ];

    // ── Types ─────────────────────────────────────────────────────
    const TYPE_QUOTATION_CREATED = 'quotation.created';
    const TYPE_QUOTATION_DELETED = 'quotation.deleted';
    const TYPE_QUOTATION_VIEWED  = 'quotation.viewed';
    const TYPE_CROP_PLANTED      = 'crop.planted';
    const TYPE_CROP_UPDATED      = 'crop.updated';
    const TYPE_CROP_HARVESTED    = 'crop.harvested';
    const TYPE_CROP_DELETED      = 'crop.deleted';
    const TYPE_CROP_CONFIRMED    = 'crop.confirmed';
    const TYPE_CROP_REJECTED     = 'crop.rejected';
    const TYPE_LAND_UPDATED      = 'land.updated';
    const TYPE_PRODUCT_CREATED   = 'product.created';
    const TYPE_PRODUCT_UPDATED   = 'product.updated';
    const TYPE_PRODUCT_DELETED   = 'product.deleted';
    const TYPE_PRODUCT_RESTORED  = 'product.restored';
    const TYPE_PRODUCT_IMPORTED  = 'product.imported';
    const TYPE_FORMULA_CREATED   = 'formula.created';
    const TYPE_FORMULA_UPDATED   = 'formula.updated';
    const TYPE_FORMULA_DELETED   = 'formula.deleted';
    const TYPE_REVIEW_CREATED    = 'review.created';
    const TYPE_REVIEW_DELETED    = 'review.deleted';
    const TYPE_REVIEW_APPROVED   = 'review.approved';
    const TYPE_REVIEW_REJECTED   = 'review.rejected';
    const TYPE_HARVEST_CREATED   = 'harvest.created';
    const TYPE_HARVEST_DELETED   = 'harvest.deleted';
    const TYPE_WEATHER_CREATED   = 'weather.created';
    const TYPE_WEATHER_DELETED   = 'weather.deleted';
    const TYPE_EVENT_CREATED     = 'event.created';
    const TYPE_EVENT_UPDATED     = 'event.updated';
    const TYPE_EVENT_DELETED     = 'event.deleted';
    const TYPE_LOGIN             = 'auth.login';
    const TYPE_LOGOUT            = 'auth.logout';
    const TYPE_LOGIN_FAILED      = 'auth.login_failed';
    const TYPE_USER_INVITED      = 'user.invited';
    const TYPE_USER_APPROVED     = 'user.approved';
    const TYPE_USER_REJECTED     = 'user.rejected';
    const TYPE_USER_IMPORTED     = 'user.imported';
    const TYPE_USER_ACTIVATED    = 'user.activated';
    const TYPE_ADMIN_CREATED     = 'admin.created';
    const TYPE_EMAIL_VERIFIED    = 'email.verified';
    const TYPE_ACCOUNT_PROVISIONED = 'account.provisioned';

    /**
     * How long a store's notice about a farmer's estimate stays in the
     * list.
     *
     * Three days: long enough to act on a new estimate, short enough that
     * the list is only what is new. The estimate itself does not go
     * anywhere - the demand trends page keeps the full history.
     */
    const QUOTATION_NOTICE_DAYS = 3;

    /**
     * Audit categories: a type prefix (before the first dot) folded into the
     * buckets the admin audit page filters on. Several prefixes map onto one
     * bucket so "user.invited", "admin.created" and "email.verified" all land
     * under Account Management.
     */
    const CATEGORY_PREFIXES = [
        'auth'     => ['auth'],
        'account'  => ['user', 'admin', 'email', 'account'],
        'product'  => ['product'],
        'formula'  => ['formula'],
        'quotation' => ['quotation'],
        'farm'     => ['crop', 'land', 'harvest', 'weather', 'event'],
        'review'   => ['review'],
    ];

    const CATEGORY_LABELS = [
        'auth'      => 'Authentication',
        'account'   => 'Account Management',
        'product'   => 'Products',
        'formula'   => 'Input Formulas',
        'quotation' => 'Quotations',
        'farm'      => 'Farming',
        'review'    => 'Reviews',
        'system'    => 'System',
    ];

    // ── Relationships ─────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Who performed the action. Null on rows written before actor tracking
     * existed — callers should fall back to the recipient (user()).
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * The acting user: the actor when known, otherwise the row owner, which
     * for self-authored rows is one and the same.
     */
    public function getActorNameAttribute(): string
    {
        return $this->actor?->name ?? $this->user?->name ?? 'System';
    }

    /**
     * The actor to show beside a notice — or nothing.
     *
     * Two cases show no name. A row whose actor is the reader is the
     * reader's own action, and an anonymised row (a farmer's estimate
     * reaching a store) must never name the farmer: the store learns that
     * the demand exists, not who created it.
     */
    public function getDisplayActorNameAttribute(): string
    {
        if ($this->type === self::TYPE_QUOTATION_CREATED && $this->user?->role === 'store_owner') {
            return '';
        }

        if (($this->metadata['anonymized'] ?? false) === true) {
            return '';
        }

        $actor = $this->actor_name;

        return $actor === (string) $this->user?->name ? '' : $actor;
    }

    public function getCategoryAttribute(): string
    {
        $prefix = explode('.', (string) $this->type)[0];

        foreach (self::CATEGORY_PREFIXES as $category => $prefixes) {
            if (in_array($prefix, $prefixes, true)) {
                return $category;
            }
        }

        return 'system';
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? 'System';
    }

    public function getTypeLabelAttribute(): string
    {
        return str_replace(['.', '_'], ' ', (string) $this->type);
    }

    // ── Scopes ────────────────────────────────────────────────────
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Filter by audit category (see CATEGORY_PREFIXES).
     */
    public function scopeInCategory($query, string $category)
    {
        $prefixes = self::CATEGORY_PREFIXES[$category] ?? null;

        if ($prefixes === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($prefixes) {
            foreach ($prefixes as $prefix) {
                $q->orWhere('type', 'like', $prefix . '.%');
            }
        });
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Farmer notifications — important notices only, not a history log.
     * The page carries only things that happen TO the farmer: the admin's
     * decision on their planting request (confirmed/rejected) and
     * association events (the most important notice). The farmer's own
     * actions — planting a batch, filing a harvest report, generating a
     * quotation — are their activity history and live under Settings →
     * Activity history, never here. Harvest reports in particular are
     * self-submitted with no admin action on them, so there is no outcome
     * to notify about; only the admin copy ("New harvest report from …")
     * matters and that lives in the admin scope.
     */
    public function scopeFarmerNotifications($query)
    {
        return $query->whereIn('type', [
            self::TYPE_CROP_CONFIRMED,
            self::TYPE_CROP_REJECTED,
            self::TYPE_EVENT_CREATED,
            self::TYPE_EVENT_UPDATED,
        ]);
    }

    /**
     * Store-owner notifications: what happens TO the store, never its own
     * history. One kind of notice lives here - a farmer's cost estimate
     * that involves this store's products (fanned out by ActivityLogger
     * strictly anonymously: no farmer name or actor). The store's own
     * actions stay under Settings → Activity history.
     *
     * Two things are deliberately not here. A deleted estimate is the
     * farmer's undo, and it belongs in that farmer's activity history: the
     * store is told what reached it, not what the farmer tidied up
     * afterwards - and when the estimate is deleted, its notice goes with
     * it. And association events are not the store's business - a store
     * owner is not a member of the association and has no calendar, so no
     * event notice reaches them.
     */
    public function scopeStoreNotifications($query)
    {
        // A farmer's cost estimate is a notice, not a record: it is worth
        // reading the day it arrives and for a few days after, because the
        // full estimate lives on the demand trends page. So a quotation
        // notice leaves the list after three days, and the list stays
        // short and fresh - new estimates, not a backlog.
        return $query->where('type', self::TYPE_QUOTATION_CREATED)
            ->where('created_at', '>=', now()->subDays(self::QUOTATION_NOTICE_DAYS));
    }

    /**
     * Admin notifications: association-wide events an admin acts on — new
     * cost estimates, planting requests awaiting confirmation and new
     * harvest reports. ActivityLogger fans one recipient row out to every
     * admin account for each event.
     */
    public function scopeAdminNotifications($query)
    {
        return $query->whereIn('type', [
            self::TYPE_QUOTATION_CREATED,
            self::TYPE_CROP_PLANTED,
            self::TYPE_HARVEST_CREATED,
        ]);
    }

    // ── Accessors ─────────────────────────────────────────────────

    /**
     * Where the association event this row announces sits right now:
     * 'upcoming', 'ongoing' or 'done'. Null for rows that do not announce
     * a calendar event. Read at render time (never stored) so a notice
     * written when the event was still ahead stops claiming that once the
     * date passes. When the event itself has been deleted the state is
     * rebuilt from the row's own metadata instead.
     */
    public function getEventStateAttribute(): ?string
    {
        if (! str_starts_with((string) $this->type, 'event.')) {
            return null;
        }

        if ($this->subject_type === AssociationEvent::class && $event = $this->subject) {
            return $event->state();
        }

        // Event deleted: fall back to what this row stored at creation.
        $date = $this->metadata['event_date'] ?? null;

        if (! $date) {
            return null;
        }

        $start = $this->metadata['start_time'] ?? null;
        $end   = $this->metadata['end_time'] ?? null;

        return AssociationEvent::computeState(
            Carbon::parse($date),
            $start ? Carbon::parse($start) : null,
            $end ? Carbon::parse($end) : null,
        );
    }

    /** "Upcoming" / "Happening now" / "Done" for the status chip. */
    public function getEventStateLabelAttribute(): ?string
    {
        $state = $this->event_state;

        return $state !== null ? (AssociationEvent::STATE_LABELS[$state] ?? null) : null;
    }

    /**
     * The row's text as it should read TODAY: the status prefix baked in at
     * creation time is corrected to the event's live status ("Past event" /
     * "Happening now"), so the wording can never go stale as the date
     * passes — including legacy rows that still open with "Upcoming event".
     * Rows for non-event notices come through untouched.
     */
    public function getDisplayDescriptionAttribute(): string
    {
        $prefix = match ($this->event_state) {
            AssociationEvent::STATE_DONE     => 'Past event',
            AssociationEvent::STATE_ONGOING  => 'Happening now',
            default                          => null,
        };

        if ($prefix === null) {
            return (string) $this->description;
        }

        return preg_replace('/^(?:Upcoming event|Happening now|Past event):/', $prefix . ':', (string) $this->description, 1)
            ?: (string) $this->description;
    }

    /**
     * The Lucide icon that stands for this activity.
     *
     * One icon per type, so the reader can tell a created product from an
     * updated one without reading the sentence. The name is handed to
     * Lucide by the page, which turns it into the official glyph.
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_QUOTATION_CREATED  => 'file-plus',
            self::TYPE_QUOTATION_VIEWED   => 'file-text',
            self::TYPE_QUOTATION_DELETED  => 'file-x',

            self::TYPE_CROP_PLANTED       => 'sprout',
            self::TYPE_CROP_UPDATED       => 'leaf',
            self::TYPE_CROP_HARVESTED     => 'wheat',
            self::TYPE_CROP_DELETED       => 'trash-2',
            self::TYPE_CROP_CONFIRMED     => 'circle-check',
            self::TYPE_CROP_REJECTED      => 'circle-x',

            self::TYPE_LAND_UPDATED       => 'map',

            self::TYPE_PRODUCT_CREATED    => 'package-plus',
            self::TYPE_PRODUCT_UPDATED    => 'package',
            self::TYPE_PRODUCT_DELETED    => 'package-x',
            self::TYPE_PRODUCT_RESTORED   => 'package-check',
            self::TYPE_PRODUCT_IMPORTED   => 'package-plus',

            self::TYPE_FORMULA_CREATED    => 'calculator',
            self::TYPE_FORMULA_UPDATED    => 'calculator',
            self::TYPE_FORMULA_DELETED    => 'calculator',

            self::TYPE_REVIEW_CREATED     => 'badge-info',
            self::TYPE_REVIEW_APPROVED    => 'badge-check',
            self::TYPE_REVIEW_REJECTED    => 'circle-x',
            self::TYPE_REVIEW_DELETED     => 'trash-2',

            self::TYPE_HARVEST_CREATED    => 'wheat',
            self::TYPE_HARVEST_DELETED    => 'trash-2',

            self::TYPE_WEATHER_CREATED    => 'cloud-sun',

            self::TYPE_EVENT_CREATED      => 'calendar-plus',
            self::TYPE_EVENT_UPDATED      => 'calendar',
            self::TYPE_EVENT_DELETED      => 'calendar-x',

            self::TYPE_LOGIN              => 'log-in',
            self::TYPE_LOGOUT             => 'log-out',
            self::TYPE_LOGIN_FAILED       => 'circle-alert',

            self::TYPE_USER_INVITED       => 'user-plus',
            self::TYPE_USER_APPROVED      => 'user-check',
            self::TYPE_USER_REJECTED      => 'circle-x',
            self::TYPE_USER_IMPORTED      => 'users',
            self::TYPE_USER_ACTIVATED     => 'user-check',
            self::TYPE_ADMIN_CREATED      => 'user',
            self::TYPE_EMAIL_VERIFIED     => 'mail',
            self::TYPE_ACCOUNT_PROVISIONED => 'key',

            default => 'history',
        };
    }

    /**
     * One colour for every activity.
     *
     * The rows used to be tinted by what happened - green for a creation,
     * red for a deletion, blue for a harvest. A wall of colours says
     * nothing once there are twenty rows, so the whole history now speaks
     * in one voice and the wording does the work.
     */
    public function getColorAttribute(): string
    {
        return '#4A7A54';
    }

    public function getBgAttribute(): string
    {
        return '#EDF1E9';
    }
}
