<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A browser's Firebase Cloud Messaging token — the device address Novu's
 * push step delivers to. One row per (account, browser); the full per-user
 * list is what gets pushed to Novu, so phones and desktops merge instead of
 * overwriting each other. Rows are replaced when a token rotates and drop
 * with the account.
 */
class FcmToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'user_agent',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
