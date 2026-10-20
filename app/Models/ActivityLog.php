<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Store-owner notifications: cost estimates that involve this store's
     * products (demand signals). QuotationController fans one recipient row
     * out to every involved store when an estimate is created or deleted —
     * strictly anonymously (no farmer name or actor, see ActivityLogger).
     */
    public function scopeStoreNotifications($query)
    {
        return $query->whereIn('type', [
            self::TYPE_QUOTATION_CREATED,
            self::TYPE_QUOTATION_DELETED,
        ]);
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
    public function getIconAttribute(): string
    {
        return match (true) {
            str_starts_with($this->type, 'quotation') => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
            str_starts_with($this->type, 'crop')     => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
            str_starts_with($this->type, 'product')  => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
            str_starts_with($this->type, 'formula')  => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="m9 17 6-10"/><path d="m5 11 4 4"/><path d="m9 11 4 4"/></svg>',
            str_starts_with($this->type, 'review')   => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            str_starts_with($this->type, 'event')    => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
            str_starts_with($this->type, 'land')      => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
            str_starts_with($this->type, 'auth')      => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>',
            str_starts_with($this->type, 'user')
                || str_starts_with($this->type, 'admin')
                || str_starts_with($this->type, 'account')
                || str_starts_with($this->type, 'email') => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            default => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/></svg>',
        };
    }

    public function getColorAttribute(): string
    {
        return match (true) {
            str_ends_with($this->type, 'created') => '#16a34a',
            str_ends_with($this->type, 'approved') => '#16a34a',
            str_ends_with($this->type, 'confirmed') => '#16a34a',
            str_ends_with($this->type, 'planted')  => '#16a34a',
            str_ends_with($this->type, 'restored') => '#16a34a',
            str_ends_with($this->type, 'activated') => '#16a34a',
            str_ends_with($this->type, 'updated')  => '#d97706',
            str_ends_with($this->type, 'imported') => '#d97706',
            str_ends_with($this->type, 'harvested') => '#0284c7',
            str_ends_with($this->type, 'invited')  => '#0284c7',
            str_ends_with($this->type, 'deleted')  => '#dc2626',
            str_ends_with($this->type, 'rejected') => '#dc2626',
            str_ends_with($this->type, 'failed')   => '#dc2626',
            str_ends_with($this->type, 'viewed')   => '#7c3aed',
            str_ends_with($this->type, 'login')    => '#64748b',
            str_ends_with($this->type, 'logout')   => '#64748b',
            default => '#64748b',
        };
    }

    public function getBgAttribute(): string
    {
        return match (true) {
            str_ends_with($this->type, 'created') => '#f0fdf4',
            str_ends_with($this->type, 'approved') => '#f0fdf4',
            str_ends_with($this->type, 'confirmed') => '#f0fdf4',
            str_ends_with($this->type, 'planted')  => '#f0fdf4',
            str_ends_with($this->type, 'restored') => '#f0fdf4',
            str_ends_with($this->type, 'activated') => '#f0fdf4',
            str_ends_with($this->type, 'updated')  => '#fffbeb',
            str_ends_with($this->type, 'imported') => '#fffbeb',
            str_ends_with($this->type, 'harvested') => '#f0f9ff',
            str_ends_with($this->type, 'invited')  => '#f0f9ff',
            str_ends_with($this->type, 'deleted')  => '#fef2f2',
            str_ends_with($this->type, 'rejected') => '#fef2f2',
            str_ends_with($this->type, 'failed')   => '#fef2f2',
            str_ends_with($this->type, 'viewed')   => '#faf5ff',
            str_ends_with($this->type, 'login')    => '#f8fafc',
            str_ends_with($this->type, 'logout')   => '#f8fafc',
            default => '#f8fafc',
        };
    }
}
