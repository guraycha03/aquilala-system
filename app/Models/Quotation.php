<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Quotation extends Model
{
    // ── Status Workflow Constants ──────────────────────────────────
    const STATUS_PENDING    = 'pending';    // Farmer submitted, awaiting store computation
    const STATUS_COMPUTED   = 'computed';   // Store owner computed prices
    const STATUS_DISPATCHED = 'dispatched'; // Store owner dispatched the quotation to farmer
    const STATUS_ACCEPTED   = 'accepted';   // Farmer accepted the quotation
    const STATUS_REJECTED   = 'rejected';   // Farmer rejected the quotation

    protected $fillable = [
        'user_id',
        'store_user_id',
        'quotation_number',
        'crop_type',
        'season',
        'land_area',
        'estimated_total_cost',
        'status',
        'valid_until',
        'notes',
        'store_notes',
    ];

    protected $casts = [
        'valid_until' => 'datetime',
        'land_area'   => 'decimal:2',
    ];

    // ── Accessors ──────────────────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until instanceof Carbon
            ? Carbon::now()->isAfter($this->valid_until)
            : false;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING    => 'Pending Response',
            self::STATUS_COMPUTED   => 'Estimate Computed',
            self::STATUS_DISPATCHED => 'Estimate Dispatched',
            self::STATUS_ACCEPTED   => 'Accepted',
            self::STATUS_REJECTED   => 'Rejected',
            default                 => ucfirst($this->status ?? 'pending'),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING    => '#d97706',
            self::STATUS_COMPUTED   => '#0284c7',
            self::STATUS_DISPATCHED => '#7c3aed',
            self::STATUS_ACCEPTED   => '#16a34a',
            self::STATUS_REJECTED   => '#dc2626',
            default                 => '#6b7280',
        };
    }

    public function getStatusBgAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING    => '#fef3c7',
            self::STATUS_COMPUTED   => '#e0f2fe',
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

    public function scopeAvailableForStore($query)
    {
        // Pending quotations that no store has claimed yet, OR assigned to this store
        return $query->where(function ($q) {
            $q->whereNull('store_user_id')
              ->orWhere('store_user_id', auth()->id());
        });
    }
}
