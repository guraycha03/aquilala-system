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
        'type',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ── Types ─────────────────────────────────────────────────────
    const TYPE_QUOTATION_CREATED = 'quotation.created';
    const TYPE_QUOTATION_DELETED = 'quotation.deleted';
    const TYPE_QUOTATION_VIEWED  = 'quotation.viewed';
    const TYPE_CROP_PLANTED      = 'crop.planted';
    const TYPE_CROP_UPDATED      = 'crop.updated';
    const TYPE_CROP_HARVESTED    = 'crop.harvested';
    const TYPE_CROP_DELETED      = 'crop.deleted';
    const TYPE_LAND_UPDATED      = 'land.updated';
    const TYPE_PRODUCT_CREATED   = 'product.created';
    const TYPE_PRODUCT_UPDATED   = 'product.updated';
    const TYPE_PRODUCT_DELETED   = 'product.deleted';
    const TYPE_LOGIN             = 'auth.login';

    // ── Relationships ─────────────────────────────────────────────
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
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

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ── Accessors ─────────────────────────────────────────────────
    public function getIconAttribute(): string
    {
        return match (true) {
            str_starts_with($this->type, 'quotation') => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
            str_starts_with($this->type, 'crop')     => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
            str_starts_with($this->type, 'product')  => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
            str_starts_with($this->type, 'land')      => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
            str_starts_with($this->type, 'auth')      => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>',
            default => '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/></svg>',
        };
    }

    public function getColorAttribute(): string
    {
        return match (true) {
            str_ends_with($this->type, 'created') => '#16a34a',
            str_ends_with($this->type, 'planted')  => '#16a34a',
            str_ends_with($this->type, 'updated')  => '#d97706',
            str_ends_with($this->type, 'harvested') => '#0284c7',
            str_ends_with($this->type, 'deleted')  => '#dc2626',
            str_ends_with($this->type, 'viewed')   => '#7c3aed',
            str_ends_with($this->type, 'login')    => '#64748b',
            default => '#64748b',
        };
    }

    public function getBgAttribute(): string
    {
        return match (true) {
            str_ends_with($this->type, 'created') => '#f0fdf4',
            str_ends_with($this->type, 'planted')  => '#f0fdf4',
            str_ends_with($this->type, 'updated')  => '#fffbeb',
            str_ends_with($this->type, 'harvested') => '#f0f9ff',
            str_ends_with($this->type, 'deleted')  => '#fef2f2',
            str_ends_with($this->type, 'viewed')   => '#faf5ff',
            str_ends_with($this->type, 'login')    => '#f8fafc',
            default => '#f8fafc',
        };
    }
}
