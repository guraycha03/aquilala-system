<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Quotation extends Model
{
    // ── Status Constants (Informational / Demand-Visibility Model) ──
    // Farmer creates → auto-computed instantly → store sees demand intel
    const STATUS_COMPUTED   = 'computed';   // Auto-computed estimate (default)
    const STATUS_VIEWED     = 'viewed';     // Store owner viewed the quotation
    const STATUS_EXPIRED    = 'expired';    // Past valid_until date

    // Legacy statuses kept for backward compatibility
    const STATUS_PENDING    = 'pending';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_REJECTED   = 'rejected';

    protected $fillable = [
        'user_id',
        'store_user_id',
        'quotation_number',
        'crop_type',
        'season',
        'soil_type',
        'weather_context',
        'land_area',
        'budget_cap',
        'budget_strict',
        'estimated_total_cost',
        'status',
        'viewed_at',
        'valid_until',
        'notes',
        'store_notes',
    ];

    protected $casts = [
        'valid_until' => 'datetime',
        'viewed_at'   => 'datetime',
        'land_area'   => 'decimal:2',
        'budget_cap'  => 'decimal:2',
        'budget_strict' => 'boolean',
    ];

    // ── Accessors ──────────────────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until instanceof Carbon
            ? Carbon::now()->isAfter($this->valid_until)
            : false;
    }

    /**
     * Stored status is never written as "expired" — expiry is derived from
     * valid_until. Use this accessor anywhere a consumer needs the status
     * that the farmer/admin actually sees.
     */
    public function getEffectiveStatusAttribute(): string
    {
        return $this->is_expired ? self::STATUS_EXPIRED : (string) ($this->status ?? self::STATUS_COMPUTED);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->effective_status) {
            self::STATUS_COMPUTED   => 'Active',
            // The state, not the watcher. A farmer should not feel a store
            // is standing over their shoulder while they price an input.
            self::STATUS_VIEWED     => 'Viewed',
            self::STATUS_EXPIRED    => 'Expired',
            self::STATUS_PENDING    => 'Pending',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_ACCEPTED   => 'Accepted',
            self::STATUS_REJECTED   => 'Rejected',
            default                 => ucfirst($this->effective_status),
        };
    }

    /**
     * Status colours for the store's read-only quotation view.
     *
     * Muted on purpose: the page is a reference sheet, not an alert board,
     * so a status chip should sit on the page rather than shout across it.
     * These two accessors are read by that page only.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->effective_status) {
            self::STATUS_COMPUTED   => '#5b7f92',
            self::STATUS_VIEWED     => '#4b5c50',
            self::STATUS_EXPIRED    => '#94a3b8',
            self::STATUS_PENDING    => '#7A6A3A',
            self::STATUS_DISPATCHED => '#5b7f92',
            self::STATUS_ACCEPTED   => '#3f6b4a',
            self::STATUS_REJECTED   => '#a34a4a',
            default                 => '#6b7280',
        };
    }

    public function getStatusBgAttribute(): string
    {
        return match ($this->effective_status) {
            self::STATUS_COMPUTED   => '#EDF1F0',
            self::STATUS_VIEWED     => '#F3F5F0',
            self::STATUS_EXPIRED    => '#f1f5f9',
            self::STATUS_PENDING    => '#FAF6E8',
            self::STATUS_DISPATCHED => '#EDF1F0',
            self::STATUS_ACCEPTED   => '#EDF1E9',
            self::STATUS_REJECTED   => '#F8EDED',
            default                 => '#f9fafb',
        };
    }

    // ── Relationships ──────────────────────────────────────────────

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function storeOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'store_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    /**
     * Get the unique store owners whose products appear in this quotation.
     */
    public function getInvolvedStoreIds(): array
    {
        return $this->items()
            ->join('products', 'quotation_items.product_id', '=', 'products.id')
            ->pluck('products.user_id')
            ->unique()
            ->values()
            ->toArray();
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeByStore($query, $storeUserId)
    {
        return $query->where('store_user_id', $storeUserId);
    }

    public function scopeByFarmer($query, $farmerUserId)
    {
        return $query->where('user_id', $farmerUserId);
    }

    /**
     * Quotations that contain products belonging to the given store owner.
     */
    public function scopeInvolvingStore($query, int $storeUserId)
    {
        return $query->whereHas('items.product', function ($q) use ($storeUserId) {
            $q->where('products.user_id', $storeUserId);
        });
    }

    /**
     * Order by how far along a quotation is: a fresh estimate first, then one
     * the store has already seen, then an expired one — anything else last.
     *
     * Written as a CASE instead of MySQL's FIELD() so the same query runs on
     * every driver the app is tested against. FIELD() has no SQLite
     * equivalent and made this page untestable there. The numbers match
     * FIELD()'s exactly: 1, 2, 3 for the three listed statuses and 0 for
     * anything else, so an unknown status still sorts ahead of them.
     */
    public function scopeOrderedByStatusPriority($query)
    {
        return $query->orderByRaw(
            "CASE status"
            . " WHEN '" . self::STATUS_COMPUTED . "' THEN 1"
            . " WHEN '" . self::STATUS_VIEWED . "' THEN 2"
            . " WHEN '" . self::STATUS_EXPIRED . "' THEN 3"
            . " ELSE 0 END"
        );
    }
}
