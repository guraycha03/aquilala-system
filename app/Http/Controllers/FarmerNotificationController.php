<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\NotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FarmerNotificationController extends Controller
{
    public function __construct(private readonly NotificationFeed $feed)
    {
    }

    /**
     * Farmer's notifications — important notices only: the admin's
     * decisions on their planting requests and association events. The
     * farmer's own action history (planting batches, harvest reports,
     * quotations) lives under Settings → Activity history, not here.
     * Rendered by the shared notifications view every role uses.
     *
     * Asked for as JSON it is the bell's modal: the same rows, the same
     * words, without the page around them.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if ($request->expectsJson()) {
            return response()->json($this->feedPayload($user));
        }

        $notifications = $this->feed->queryFor($user)
            ->with(['user', 'actor', 'subject'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount'   => $this->feed->unreadCount($user),
            'routePrefix'   => 'farmer.notifications',
            'subtitle'      => 'Important notices — planting decisions and association events.',
            'emptyText'     => "There's nothing here right now. Decisions on your planting requests and association events will appear here.",
        ]);
    }

    /**
     * Mark every unread farmer notification as read.
     */
    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->feed->markAllRead($user);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'unread' => 0]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark a single notification as read. Only rows owned by the farmer.
     */
    public function markRead(Request $request, ActivityLog $activityLog): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->feed->markRead($user, $activityLog);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'unread' => $this->feed->unreadCount($user),
            ]);
        }

        return back();
    }

    /**
     * The payload the bell's modal reads.
     *
     * @return array<string, mixed>
     */
    private function feedPayload($user): array
    {
        return [
            'items'        => $this->feed->items($user, 8, 'farmer.notifications'),
            'unread'       => $this->feed->unreadCount($user),
            'read_all_url' => route('farmer.notifications.read-all'),
            'all_url'      => route('farmer.notifications.index'),
        ];
    }
}
