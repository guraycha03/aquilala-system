<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\NotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StoreOwnerActivityController extends Controller
{
    public function __construct(private readonly NotificationFeed $feed)
    {
    }
    /**
     * Store owner's activity history with smart filters
     * (search, date period, and activity type).
     */
    public function index(): View
    {
        $search = request('q');
        $period = request('period', 'all');
        $from   = request('from');
        $to     = request('to');
        $type   = request('type');

        $userId = Auth::id();
        $today  = now()->endOfDay();

        // ── Date range resolution ──
        $fromDate = null;
        $toDate   = null;

        $parseDate = function ($value) {
            if (!$value || strtotime($value) === false) {
                return null;
            }
            return \Illuminate\Support\Carbon::parse($value);
        };

        if ($from && ($parsedFrom = $parseDate($from)) && $parsedFrom->startOfDay()->lte($today)) {
            $from = $parsedFrom->toDateString();
            $fromDate = $parsedFrom->startOfDay();
        } else {
            $from = null;
        }

        if ($to && ($parsedTo = $parseDate($to))) {
            $toDate = $parsedTo->startOfDay()->gt($today) ? $today : $parsedTo->endOfDay();
            $to = $toDate->toDateString();
        } else {
            $to = null;
        }

        if ($fromDate && $toDate && $fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
            $from = $fromDate->toDateString();
            $to   = $toDate->toDateString();
        }

        $start = $fromDate;
        $end   = $toDate;

        if (in_array($period, ['today', '7days', '30days', 'month'], true) && !$from && !$to) {
            [$periodStart, $periodEnd] = match ($period) {
                'today'   => [now()->startOfDay(), now()->endOfDay()],
                '7days'   => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
                '30days'  => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
                'month'   => [now()->startOfMonth(), now()->endOfMonth()],
            };
            $start = $periodStart;
            $end   = $periodEnd;
            $from  = $periodStart->toDateString();
            $to    = $periodEnd->toDateString();
        } elseif ($period !== 'all' && !$from && !$to) {
            $period = 'all';
        }

        // ── Build the filtered query ──
        // Sign-in/sign-out rows belong to the admin audit trail; the store
        // history is about work done on the store (products, formulas, …).
        $query = ActivityLog::where('user_id', $userId)
            ->where('type', 'not like', 'auth.%');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($start) {
            $query->where('created_at', '>=', $start);
        }
        if ($end) {
            $query->where('created_at', '<=', $end);
        }
        if ($type) {
            $query->where('type', $type);
        }

        $activities = $query->latest()->paginate(10)->withQueryString();

        // ── Aggregate stats from the store owner's FULL history ──
        $statsBase = ActivityLog::where('user_id', $userId);
        $totalProducts   = (clone $statsBase)->where('type', ActivityLog::TYPE_PRODUCT_CREATED)->count();
        $totalUpdated    = (clone $statsBase)->where('type', ActivityLog::TYPE_PRODUCT_UPDATED)->count();

        $uniqueTypes = ActivityLog::where('user_id', $userId)
            ->where('type', 'not like', 'auth.%')
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->sort()
            ->values()
            ->toArray();

        return view('store.activity.index', compact(
            'activities',
            'search',
            'period',
            'from',
            'to',
            'type',
            'uniqueTypes',
            'totalProducts',
            'totalUpdated'
        ));
    }

    /**
     * Store owner's notifications — news that reaches the store from
     * other people: farmers making cost estimates that include this
     * store's products, and the association's upcoming events. The
     * store's own actions are history, not notifications, and live
     * under Settings → Activity history. Farmer identities are never
     * shown here: stores learn that demand exists, not who generated it.
     */
    public function notifications(Request $request): View|JsonResponse
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
            'routePrefix'   => 'store.notifications',
            'subtitle'      => "News that reaches your store — farmer quotations that include your products, and upcoming association events.",
            'emptyText'     => "There's nothing new right now. When a farmer creates a quotation that includes your products, or the association posts an event, it will appear here.",
        ]);
    }

    /**
     * Mark every unread store notification as read.
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
     * Mark a single notification as read. Only rows owned by this store
     * owner can be marked.
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
            'items'        => $this->feed->items($user, 8, 'store.notifications'),
            'unread'       => $this->feed->unreadCount($user),
            'read_all_url' => route('store.notifications.read-all'),
            'all_url'      => route('store.notifications.index'),
        ];
    }
}