<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    /**
     * System audit trail.
     *
     * Every row is a real activity_logs record (the page used to ship with a
     * hard-coded sample list). Filters are server side so the KPI cards, the
     * table and the pagination all agree on the same result set.
     */
    public function index(Request $request): View
    {
        $search   = trim((string) $request->query('q', ''));
        $category = (string) $request->query('cat', '');
        $period   = (string) $request->query('period', 'all');
        $userId   = (string) $request->query('user_id', '');

        if (! array_key_exists($category, ActivityLog::CATEGORY_PREFIXES)) {
            $category = '';
        }

        $query = ActivityLog::query()->with(['user', 'actor']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('actor', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($category !== '') {
            $query->inCategory($category);
        }

        if ($userId !== '' && ctype_digit($userId)) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', (int) $userId)
                    ->orWhere('actor_id', (int) $userId);
            });
        }

        $rangeStart = match ($period) {
            'today'  => now()->startOfDay(),
            '7days'  => now()->subDays(6)->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            'month'  => now()->startOfMonth(),
            default  => null,
        };

        if ($rangeStart !== null) {
            $query->where('created_at', '>=', $rangeStart);
        }

        $activities = (clone $query)->latest()->paginate(20)->withQueryString();

        $kpis = [
            'total'            => ActivityLog::count(),
            'today'            => ActivityLog::whereDate('created_at', today())->count(),
            'authentications'  => ActivityLog::inCategory('auth')->count(),
            'changes'          => $this->changeEvents()->count(),
        ];

        $categories = collect(ActivityLog::CATEGORY_PREFIXES)
            ->mapWithKeys(fn ($prefixes, $key) => [$key => ActivityLog::CATEGORY_LABELS[$key] ?? $key]);

        $users = User::orderBy('name')->get(['id', 'name', 'role']);

        return view('admin.audit.index', compact(
            'activities',
            'kpis',
            'categories',
            'users',
            'search',
            'category',
            'period',
            'userId'
        ));
    }

    /**
     * Rows that record a create/change/delete anywhere in the system.
     */
    private function changeEvents()
    {
        return ActivityLog::where(function ($q) {
            foreach (['%.created', '%.updated', '%.deleted', '%.restored', '%.approved', '%.rejected', '%.imported'] as $like) {
                $q->orWhere('type', 'like', $like);
            }
        });
    }
}
