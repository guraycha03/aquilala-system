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
            self::STATUS_COMPUTED   => 'Estimate Ready',
            self::STATUS_VIEWED     => 'Viewed by Store',
            self::STATUS_EXPIRED    => 'Expired',
            self::STATUS_PENDING    => 'Pending',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_ACCEPTED   => 'Accepted',
            self::STATUS_REJECTED   => 'Rejected',
            default                 => ucfirst($this->effective_status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->effective_status) {
            self::STATUS_COMPUTED   => '#0284c7',
            self::STATUS_VIEWED     => '#7c3aed',
            self::STATUS_EXPIRED    => '#94a3b8',
            self::STATUS_PENDING    => '#d97706',
            self::STATUS_DISPATCHED => '#7c3aed',
            self::STATUS_ACCEPTED   => '#16a34a',
            self::STATUS_REJECTED   => '#dc2626',
            default                 => '#6b7280',
        };
    }

    public function getStatusBgAttribute(): string
    {
        return match ($this->effective_status) {
            self::STATUS_COMPUTED   => '#e0f2fe',
            self::STATUS_VIEWED     => '#f3e8ff',
            self::STATUS_EXPIRED    => '#f1f5f9',
            self::STATUS_PENDING    => '#fef3c7',
            self::STATUS_DISPATCHED => '#f3e8ff',
            self::STATUS_ACCEPTED   => '#f0fdf4',
            self::STATUS_REJECTED   => '#fef2f2',
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
}
