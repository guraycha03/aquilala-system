<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    /**
     * Admin's notifications — association-wide events an admin acts on:
     * new cost estimates, planting requests awaiting confirmation and new
     * harvest reports. Rendered by the shared notifications view.
     */
    public function index(): View
    {
        $notifications = ActivityLog::forUser(Auth::id())
            ->adminNotifications()
            ->with(['user', 'actor'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $unreadCount = ActivityLog::forUser(Auth::id())
            ->adminNotifications()
            ->unread()
            ->count();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
            'routePrefix'   => 'admin.notifications',
            'subtitle'      => 'Association-wide updates: new cost estimates, planting requests and harvest reports.',
            'emptyText'     => "There's nothing here right now. New cost estimates, planting requests awaiting confirmation and harvest reports from farmers will appear here.",
        ]);
    }

    /**
     * Mark every unread admin notification as read.
     */
    public function markAllRead(): RedirectResponse
    {
        ActivityLog::forUser(Auth::id())
            ->adminNotifications()
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark a single notification as read. Only rows owned by this admin
     * can be marked.
     */
    public function markRead(ActivityLog $activityLog): RedirectResponse
    {
        abort_unless($activityLog->user_id === Auth::id(), 403);

        $activityLog->forceFill(['read_at' => now()])->save();

        return back();
    }
}
