<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Novu (novu.co) — hosted notification infrastructure.
 *
 * One trigger fans a notice out to every channel the dashboard workflow has:
 * the in-app Inbox feed (bell in the shared header) now, and browser push
 * through Firebase (FCM) once the workflow's Push step exists and
 * NOVU_PUSH_ENABLED is true.
 *
 * Every method is fire-and-forget and NEVER throws: a missing key, a wrong
 * workflow id or a network timeout must not break the action that produced
 * the notice. Failures are logged as warnings so setup mistakes are visible
 * without failing requests.
 */
class NovuService
{
    private const API_BASE = 'https://api.novu.co/v1';

    /** true when an account + key are configured — i.e. calls will be made. */
    public static function enabled(): bool
    {
        return (bool) config('novu.app_id') && (bool) config('novu.api_key');
    }

    /** true when the FCM (browser push) half is configured on top of that. */
    public static function firebaseConfigured(): bool
    {
        return (bool) config('novu.firebase.api_key');
    }

    /**
     * HMAC proof of the subscriber's identity for the front-end Inbox —
     * only produced when a secret is configured, otherwise null (HMAC off).
     */
    public static function subscriberHash(string $subscriberId): ?string
    {
        $secret = (string) config('novu.hmac_secret');

        if ($secret === '') {
            return null;
        }

        return hash_hmac('sha256', $subscriberId, $secret);
    }

    /**
     * Create/refresh the Novu subscriber behind a User so the feed can show
     * a name and email. Novu auto-creates bare subscribers on first trigger;
     * this just keeps them enriched.
     */
    public static function identify(User $user): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            $res = self::client()->post(self::API_BASE . '/subscribers', [
                'subscriberId' => (string) $user->id,
                'firstName'    => $user->first_name ?: ($user->name ?: null),
                'lastName'     => $user->last_name ?: null,
                'email'        => $user->email ?: null,
            ]);

            self::warnOnFailure($res, 'identify subscriber ' . $user->id);
        } catch (\Throwable $e) {
            Log::warning('Novu identify failed: ' . $e->getMessage());
        }
    }

    /**
     * Trigger the shared notice workflow for a set of recipients.
     *
     * Payload feeds the workflow's templates: subject {{payload.title}},
     * body {{payload.body}}, link {{payload.link}}. Recipients go in chunks
     * of 100 — the API's per-request limit.
     */
    public static function triggerNotice(string $title, string $body, $userIds, ?string $link = null): void
    {
        if (! self::enabled()) {
            return;
        }

        $ids = collect($userIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $workflow = (string) (config('novu.workflow_notice') ?: 'aquilala-important-notice');
        $payload  = array_filter([
            'title' => $title,
            'body'  => $body,
            'link'  => $link,
        ], fn ($v) => $v !== null && $v !== '');

        try {
            foreach ($ids->chunk(100) as $chunk) {
                $res = self::client()->post(self::API_BASE . '/events/trigger', [
                    'name'    => $workflow,
                    'to'      => $chunk
                        ->map(fn ($id) => ['subscriberId' => (string) $id])
                        ->values()
                        ->all(),
                    'payload' => $payload,
                ]);

                self::warnOnFailure($res, "trigger workflow {$workflow}");
            }
        } catch (\Throwable $e) {
            Log::warning('Novu trigger failed: ' . $e->getMessage());
        }
    }

    /**
     * Replace this subscriber's FCM device tokens on Novu (the push
     * provider's registration list). Called with the full per-user list so
     * multiple devices merge instead of overwriting each other.
     */
    public static function setDeviceTokens(string $subscriberId, array $tokens): void
    {
        if (! self::enabled()) {
            return;
        }

        $tokens = collect($tokens)->filter()->unique()->values()->all();

        try {
            $res = self::client()->put(
                self::API_BASE . '/subscribers/' . rawurlencode($subscriberId) . '/credentials',
                [
                    'providerId'  => 'fcm',
                    'credentials' => ['deviceTokens' => $tokens],
                ]
            );

            self::warnOnFailure($res, 'set FCM device tokens for subscriber ' . $subscriberId);
        } catch (\Throwable $e) {
            Log::warning('Novu device token update failed: ' . $e->getMessage());
        }
    }

    private static function client()
    {
        return Http::withHeaders([
            'Authorization' => 'ApiKey ' . config('novu.api_key'),
            'Content-Type'  => 'application/json',
        ])->timeout(6);
    }

    private static function warnOnFailure($response, string $action): void
    {
        if ($response->failed()) {
            Log::warning(
                "Novu: {$action} rejected (HTTP {$response->status()}): "
                . Str::limit($response->body(), 300)
            );
        }
    }
}
