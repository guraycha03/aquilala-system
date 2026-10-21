<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\NotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function __construct(private readonly NotificationFeed $feed)
    {
    }

    /**
     * Admin's notifications — association-wide events an admin acts on:
     * new cost estimates, planting requests awaiting confirmation and new
     * harvest reports. Rendered by the shared notifications view.
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
            ->with(['user', 'actor'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount'   => $this->feed->unreadCount($user),
            'routePrefix'   => 'admin.notifications',
            'subtitle'      => 'Association-wide updates: new cost estimates, planting requests and harvest reports.',
            'emptyText'     => "There's nothing here right now. New cost estimates, planting requests awaiting confirmation and harvest reports from farmers will appear here.",
        ]);
    }

    /**
     * Mark every unread admin notification as read.
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
     * Mark a single notification as read. Only rows owned by this admin
     * can be marked.
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
            'items'        => $this->feed->items($user, 8, 'admin.notifications'),
            'unread'       => $this->feed->unreadCount($user),
            'read_all_url' => route('admin.notifications.read-all'),
            'all_url'      => route('admin.notifications.index'),
        ];
    }
}
