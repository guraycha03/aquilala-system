<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

/**
 * Web Push — outside-the-system notifications (installed PWA / browser).
 *
 * The same hard rule as the AI layer: never break the system. Every public
 * method is wrapped so a missing package, missing VAPID keys, a dead push
 * endpoint or a network failure can never fail the request that triggered
 * it. With no keys configured the whole feature is a silent no-op — the app
 * works exactly as before.
 *
 * Delivery runs after the HTTP response is sent (dispatch()->afterResponse(),
 * which needs no queue worker), so creating an event never waits on push.
 * Endpoints the push service reports gone (404/410) are pruned on the spot.
 */
class PushService
{
    /** Time-to-live for a pushed message: one day, then the push service drops it. */
    private const TTL_SECONDS = 86400;

    /**
     * Push is only live when the package is installed AND a VAPID keypair is
     * configured. Otherwise subscribe/send quietly do nothing.
     */
    public static function available(): bool
    {
        try {
            return class_exists(WebPush::class)
                && filled(config('push.vapid_public_key'))
                && filled(config('push.vapid_private_key'));
        } catch (\Throwable) {
            return false;
        }
    }

    public static function publicKey(): ?string
    {
        try {
            return self::available() ? (string) config('push.vapid_public_key') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Send a push to many users. Each role can land on its own page inside
     * the app (the notification click opens $url), so callers pass one call
     * per role when the target page differs.
     *
     * @param iterable<int|string> $userIds
     */
    public static function sendToUsers($userIds, string $title, string $body, string $url, string $tag = 'aquilala'): void
    {
        try {
            if (! self::available()) {
                return;
            }

            $ids = collect($userIds)->filter()->unique()->values()->all();
            if ($ids === []) {
                return;
            }

            $subscriptions = PushSubscription::whereIn('user_id', $ids)->get();
            if ($subscriptions->isEmpty()) {
                return;
            }

            $payload = json_encode([
                'title' => $title,
                'body'  => $body,
                'url'   => $url,
                'tag'   => $tag,
            ], JSON_UNESCAPED_SLASHES);

            // afterResponse() runs after the response is sent, in this same
            // process — no queue worker required, no latency for the user.
            dispatch(function () use ($subscriptions, $payload): void {
                try {
                    self::deliver($subscriptions, $payload);
                } catch (\Throwable) {
                    // A push failure must never surface anywhere.
                }
            })->afterResponse();
        } catch (\Throwable) {
            // Same rule as the AI layer: fail quiet, fail safe.
        }
    }

    /**
     * One push per subscription. A dead or failing device never stops the
     * rest of the list; endpoints the push service reports gone are deleted.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, PushSubscription> $subscriptions
     */
    private static function deliver($subscriptions, string $payload): void
    {
        $webpush = new WebPush([
            'VAPID' => [
                'subject'    => (string) config('push.vapid_subject'),
                'publicKey'  => (string) config('push.vapid_public_key'),
                'privateKey' => (string) config('push.vapid_private_key'),
            ],
        ], [
            'TTL'     => self::TTL_SECONDS,
            'urgency' => 'normal',
        ]);

        foreach ($subscriptions as $subscription) {
            try {
                $report = $webpush->sendOneNotification(
                    Subscription::create($subscription->toWebPushPayload()),
                    $payload
                );

                if ($report->isSubscriptionExpired()) {
                    // 404/410 — the browser rotated or removed this endpoint.
                    $subscription->delete();
                    continue;
                }

                if ($report->isSuccess()) {
                    $subscription->forceFill(['last_used_at' => now()])->save();
                }
            } catch (\Throwable) {
                // One unreachable device must not stop the others.
                continue;
            }
        }
    }

    /**
     * Generate a fresh VAPID keypair (base64url, ready for .env / the
     * browser's applicationServerKey). Used by `php artisan push:generate-keys`.
     *
     * @return array{public: string, private: string}
     */
    public static function generateKeyPair(): array
    {
        $keys = VAPID::createVapidKeys();

        return [
            'public'  => $keys['publicKey'],
            'private' => $keys['privateKey'],
        ];
    }
}
