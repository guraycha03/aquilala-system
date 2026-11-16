<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A person on the association's list of members.
 *
 * Members are not accounts. They hold no password and cannot sign in — they
 * are the roster an administrator picks from when creating a system user.
 */
class Member extends Model
{
    use SoftDeletes;

    public const DELETED_AT = 'archived_at';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'assoc_member_no',
        'first_name',
        'middle_initial',
        'last_name',
        'name',
        'sex',
        'birth_date',
        'civil_status',
        'email',
        'phone',
        'address',
        'barangay',
        'city',
        'province',
        'farm_location',
        'land_ownership',
        'land_area',
        'land_area_unit',
        'years_farming',
        'status',
        'notes',
        'user_id',
        'created_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'land_area' => 'decimal:2',
        'years_farming' => 'integer',
    ];

    /**
     * The system account minted from this member, if one exists.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Harvest reports filed against this member — the association's
     * historical records, imported before (or without) an account.
     */
    public function harvestReports(): HasMany
    {
        return $this->hasMany(HarvestReport::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Members that do not yet have a system account — the only ones an
     * administrator may turn into users.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';

            $q->where('name', 'like', $like)
                ->orWhere('assoc_member_no', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('barangay', 'like', $like)
                ->orWhere('city', 'like', $like);
        });
    }

    /**
     * Composed full name. Falls back to whatever the record carries when the
     * name came in as one unbroken string (OCR, free-typed imports).
     */
    public function fullName(): string
    {
        if (trim((string) $this->name) !== '') {
            return (string) $this->name;
        }

        $parts = array_filter([
            trim((string) $this->first_name),
            $this->middle_initial ? strtoupper(trim($this->middle_initial)) . '.' : '',
            trim((string) $this->last_name),
        ]);

        return implode(' ', $parts);
    }

    /**
     * Two-letter monogram used where no photograph is held.
     */
    public function initials(): string
    {
        $first = mb_substr(trim((string) $this->first_name), 0, 1);
        $last = mb_substr(trim((string) $this->last_name), 0, 1);

        if ($first === '' && $last === '') {
            $words = preg_split('/\s+/', trim($this->fullName()));
            $first = mb_substr($words[0] ?? '', 0, 1);
            $last = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';
        }

        return strtoupper($first . $last);
    }

    /**
     * Members hold no photograph yet — the avatar component still asks for a
     * URL, so answer it with the fallback (the monogram) instead of erroring.
     */
    public function profilePhotoUrl(): ?string
    {
        return null;
    }

    /**
     * Residential address on one line, blanks collapsed.
     */
    public function addressLine(): string
    {
        return collect([
            trim((string) $this->address),
            trim((string) $this->barangay),
            trim((string) $this->city),
            trim((string) $this->province),
        ])->filter()->unique()->implode(', ');
    }

    /**
     * Farm address, falling back to the residential one.
     */
    public function farmAddressLine(): string
    {
        $farm = trim((string) $this->farm_location);

        return $farm !== '' ? $farm : $this->addressLine();
    }

    /**
     * True once this member has been turned into a system account.
     */
    public function hasAccount(): bool
    {
        return $this->user_id !== null;
    }
}
