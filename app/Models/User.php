<?php

namespace App\Models;

use App\Services\AiAvatars;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public const DELETED_AT = 'archived_at';

    /**
     * @var list<string>
     */
    public const STATUS_PENDING = 'pending';       // created by admin; awaiting OTP + password setup

    public const STATUS_ACTIVE = 'active';         // fully activated; may sign in normally

    public const STATUS_DISABLED = 'disabled';     // intentionally disabled by an administrator

    protected $fillable = [
        'name',
        'first_name',
        'middle_initial',
        'last_name',
        'sex',
        'birth_date',
        'civil_status',
        'email',
        'phone',
        'role',
        'status',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'store_name',
        'business_type',
        'ownership_type',
        'business_description',
        'business_registration_number',
        'tax_identification_number',
        'year_established',
        'years_operating',
        'operating_status',
        'operating_days',
        'operating_hours',
        'address',
        'contact_number',
        'barangay',
        'city',
        'province',
        'owner_address',
        'farm_location',
        'default_land_area',
        'default_land_area_unit',
        'land_ownership',
        'years_farming',
        'assoc_member_no',
        'approved_at',
        'sidebar_theme',
        'theme_mode',
        'ai_avatar',
        'profile_photo',
        'activation_completed_at',
        'must_change_password',
        'notify_planting_updates',
        'notify_association_events',
    ];

    /**
     * Defaults for the notification preference columns. The migration
     * declares both as `true`, so a freshly built account is opted in —
     * without this, an in-memory row reads null and NotificationFeed
     * treats it as an opt-out.
     */
    protected $attributes = [
        'notify_planting_updates' => true,
        'notify_association_events' => true,
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'activation_completed_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'birth_date' => 'date',
            'default_land_area' => 'decimal:2',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'notify_planting_updates' => 'boolean',
            'notify_association_events' => 'boolean',
        ];
    }

    /**
     * Whether the account is required to set a password before anything else.
     *
     * Only the CSV/OCR bulk-import path creates these: imported accounts are
     * active but arrive with a temporary password, so they are forced through
     * the change-password screen until the member replaces it. Accounts
     * created one at a time from the Users page go through the OTP activation
     * flow instead and never set this flag.
     */
    public function mustChangePassword(): bool
    {
        return (bool) $this->must_change_password;
    }

    /**
     * The AI assistant avatar this account sees in the chat header and beside
     * every AI reply. The avatar is one of the system's own set - there is no
     * upload - so the account only stores which one it picked.
     */
    public function aiAvatarUrl(): string
    {
        if (! $this->ai_avatar) {
            return AiAvatars::url(AiAvatars::defaultKey());
        }

        if (AiAvatars::has($this->ai_avatar)) {
            return AiAvatars::url($this->ai_avatar);
        }

        // An account that still carries an uploaded image from before the
        // avatar set existed keeps it until the next pick.
        if (Storage::disk('public')->exists($this->ai_avatar)) {
            return Storage::disk('public')->url($this->ai_avatar);
        }

        return AiAvatars::url(AiAvatars::defaultKey());
    }

    /**
     * This account's own picture, if it has one.
     *
     * The file lives on the public disk; the row holds its path. A picture
     * that has gone missing is treated as no picture, so the account falls
     * back to its initials rather than showing a broken image.
     */
    public function profilePhotoUrl(): ?string
    {
        if (! $this->profile_photo) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->profile_photo)) {
            return null;
        }

        return Storage::disk('public')->url($this->profile_photo);
    }

    /**
     * The initials for this account: the first letter of the first and the
     * last name. Used wherever there is no picture.
     */
    public function initials(): string
    {
        $name = trim((string) $this->name);
        $words = preg_split('/\s+/', $name) ?: [];
        $words = array_values(array_filter($words, fn ($word) => $word !== ''));

        if (count($words) >= 2) {
            return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr(end($words), 0, 1));
        }

        return mb_strtoupper(mb_substr($words[0] ?? '?', 0, 2));
    }

    /**
     * The name as a store owner is allowed to see it: the first letter of
     * every part and nothing else. "Juan Cruz" becomes "Jxxx Cxxx".
     *
     * A farmer who creates a quotation must never feel watched, and a
     * store must never sit on a false hope that the farmer is coming to
     * buy. The store learns that the demand exists, not who it belongs to.
     */
    public function maskedName(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $words = array_values(array_filter($words, fn ($word) => $word !== ''));

        if ($words === []) {
            return 'Axxx';
        }

        return implode(' ', array_map(fn ($word) => mb_substr($word, 0, 1).'xxx', $words));
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }

    public function activations(): HasMany
    {
        return $this->hasMany(UserActivation::class);
    }

    public function emailOtps(): HasMany
    {
        return $this->hasMany(EmailOtp::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    public function savedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'saved_products')->withTimestamps();
    }

    public function farmerCrops(): HasMany
    {
        return $this->hasMany(FarmerCrop::class, 'user_id');
    }

    public function chatHistories(): HasMany
    {
        return $this->hasMany(ChatHistory::class, 'user_id');
    }

    /**
     * The member record this account was created from, if any. Farmer
     * accounts always have one; store owners are outsiders and have none.
     */
    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    /**
     * Legal paperwork filed against this store.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(StoreDocument::class);
    }
}
