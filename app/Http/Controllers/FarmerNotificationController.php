<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FarmerNotificationController extends Controller
{
    /**
     * Farmer's notifications — quotation events, newest first.
     */
    public function index(): View
    {
        $notifications = ActivityLog::forUser(Auth::id())
            ->farmerNotifications()
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $unreadCount = ActivityLog::forUser(Auth::id())
            ->farmerNotifications()
            ->unread()
            ->count();

        return view('farmer.notifications.index', compact('notifications', 'unreadCount'));
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