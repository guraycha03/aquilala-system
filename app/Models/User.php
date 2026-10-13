<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        'address',
        'contact_number',
        'barangay',
        'city',
        'province',
        'farm_location',
        'default_land_area',
        'default_land_area_unit',
        'land_ownership',
        'years_farming',
        'assoc_member_no',
        'approved_at',
        'sidebar_theme',
        'theme_mode',
        'activation_completed_at',
        'must_change_password',
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
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
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
}
