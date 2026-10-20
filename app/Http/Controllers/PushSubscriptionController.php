<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use App\Models\PushSubscription;
use App\Services\NovuService;
use App\Services\PushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Device endpoints for Web Push: serve the VAPID public key, store or drop
 * the browser's push subscription for the signed-in account.
 *
 * Everything degrades quietly: with no VAPID keys configured the public key
 * comes back empty and the frontend simply never shows the opt-in — the app
 * is unaffected either way.
 */
class PushSubscriptionController extends Controller
{
    /**
     * The public half of the VAPID keypair, used as applicationServerKey
     * when the browser subscribes. Null/empty means push is not configured.
     */
    public function publicKey(): JsonResponse
    {
        return response()->json([
            'key' => PushService::publicKey(),
        ]);
    }

    /**
     * Save the browser's push subscription for this account. Re-subscribing
     * from the same browser upserts (the endpoint is the device's unique
     * address), so re-enabling never duplicates rows.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint'    => ['required', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:128'],
            'keys.auth'   => ['required', 'string', 'max:64'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id'    => Auth::id(),
                'p256dh'     => $data['keys']['p256dh'],
                'auth'       => $data['keys']['auth'],
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'last_used_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Drop this browser's subscription. Only the owner's rows can be
     * removed — an endpoint belongs to exactly one account.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        PushSubscription::where('user_id', Auth::id())
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Novu / FCM half of device opt-in: store this browser's Firebase
     * Cloud Messaging token, then push the account's full token list to
     * Novu so phones and desktops merge instead of overwriting each other.
     * `revoke` drops just this browser's token (disable on this device).
     *
     * With Novu or Firebase not configured it answers not-configured and
     * touches nothing — the VAPID flow beside it is unaffected either way.
     */
    public function novuToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'  => ['nullable', 'string', 'max:512'],
            'revoke' => ['nullable', 'boolean'],
        ]);

        if (! NovuService::enabled() || ! NovuService::firebaseConfigured()) {
            return response()->json(['ok' => false, 'reason' => 'not-configured']);
        }

        $userId = Auth::id();
        $token  = trim((string) ($data['token'] ?? ''));

        if ($data['revoke'] ?? false) {
            if ($token !== '') {
                FcmToken::where('user_id', $userId)->where('token', $token)->delete();
            }
        } elseif ($token !== '') {
            FcmToken::updateOrCreate(
                ['user_id' => $userId, 'token' => $token],
                [
                    'user_agent'   => Str::limit((string) $request->userAgent(), 250, ''),
                    'last_used_at' => now(),
                ]
            );
        } else {
            return response()->json(['ok' => false, 'reason' => 'missing-token'], 422);
        }

        // One source of truth per account: whatever rows remain is exactly
        // what Novu should hold for this subscriber.
        NovuService::setDeviceTokens((string) $userId, FcmToken::where('user_id', $userId)->pluck('token')->all());

        return response()->json(['ok' => true]);
    }
}
