<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FarmerNotificationController extends Controller
{
    /**
     * Farmer's notifications — important notices only: the admin's
     * decisions on their planting requests and association events. The
     * farmer's own action history (planting batches, harvest reports,
     * quotations) lives under Settings → Activity history, not here.
     * Rendered by the shared notifications view every role uses.
     */
    public function index(): View
    {
        $notifications = ActivityLog::forUser(Auth::id())
            ->farmerNotifications()
            ->with(['user', 'actor'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $unreadCount = ActivityLog::forUser(Auth::id())
            ->farmerNotifications()
            ->unread()
            ->count();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'routePrefix'   => 'farmer.notifications',
            'subtitle'      => 'Important notices — planting decisions and association events.',
            'emptyText'     => "There's nothing here right now. Decisions on your planting requests and upcoming association events will appear here.",
        ]);
    }

    /**
     * Mark every unread farmer notification as read.
     */
    public function markAllRead(): RedirectResponse
    {
        ActivityLog::forUser(Auth::id())
            ->farmerNotifications()
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark a single notification as read. Only rows owned by the farmer.
     */
    public function markRead(ActivityLog $activityLog): RedirectResponse
    {
        abort_unless($activityLog->user_id === Auth::id(), 403);

        $activityLog->forceFill(['read_at' => now()])->save();

        return back();
    }
}