<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single-use, expiring email verification code. The stored value is always
 * a hash (Hash::make) of the 6-digit code — plaintext codes never touch the
 * database, logs, or URLs.
 */
class EmailOtp extends Model
{
    protected $table = 'email_otps';

    public const PURPOSE_ACTIVATION = 'activation';

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    protected $fillable = [
        'user_id',
        'purpose',
        'otp_hash',
        'expires_at',
        'attempts',
        'used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
