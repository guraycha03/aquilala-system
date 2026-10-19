<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A device/browser the user opted into for Web Push. Rows are written by
 * the subscribe endpoint from the browser's PushManager subscription and
 * removed on unsubscribe — or by PushService when the push service reports
 * the endpoint gone (404/410).
 */
class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint',
        'p256dh',
        'auth',
        'user_agent',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Shape for \Minishlink\WebPush\Subscription::create().
     */
    public function toWebPushPayload(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys'     => [
                'p256dh' => $this->p256dh,
                'auth'   => $this->auth,
            ],
        ];
    }
}
