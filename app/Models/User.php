<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

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
            'phone_verified_at' => 'datetime',
            'birth_date'        => 'date',
            'approved_at'       => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function isActivated(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->phone_verified_at !== null;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isPendingVerification(): bool
    {
        return $this->status === self::STATUS_PENDING_VERIFICATION;
    }

    public function activations(): HasMany
    {
        return $this->hasMany(UserActivation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    public function farmerCrops(): HasMany
    {
        return $this->hasMany(FarmerCrop::class, 'user_id');
    }
}
