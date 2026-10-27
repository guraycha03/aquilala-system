<?php

namespace App\Http\Controllers;

use App\Helpers\SeasonHelper;
use App\Models\AssociationEvent;
use App\Models\Category;
use App\Models\HarvestReport;
use App\Models\Member;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Login method toggle: users may sign in with email OR phone number
        $method = $request->input('login_method') === 'phone' ? 'phone' : 'email';
        $errorKey = $method === 'phone' ? 'phone' : 'email';

        if ($method === 'phone') {
            $validated = $request->validate([
                'phone' => ['required', 'string', 'max:25'],
                'password' => ['required', 'string'],
            ], [
                'phone.required' => 'Please enter your mobile number.',
                'phone.max' => 'Mobile number is too long.',
                'password.required' => 'Please enter your password.',
            ]);

            // Accepts +639XXXXXXXXX, 09XXXXXXXXX, 9XXXXXXXXX etc.
            $phone = UserInvitationService::normalizePhone($validated['phone']);

            if ($phone === null) {
                return back()->withErrors([
                    'phone' => 'Enter a valid Philippine mobile number — the 9 digits after +639.',
                ])->withInput($request->except('password'));
            }

            $credentials = [
                'phone' => $phone,
                'password' => $validated['password'],
            ];
        } else {
            // Real-world secure: use dynamic validation toggles based on environment
            $emailRule = app()->environment('production') ? 'email:rfc,dns,spoof' : 'email:rfc';
            $validated = $request->validate([
                'email' => [
                    'required',
                    'string',
                    'max:254',
                    $emailRule,
                ],
                'password' => ['required', 'string'],
            ], [
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Enter a valid email address.',
                'password.required' => 'Please enter your password.',
            ]);

            $credentials = [
                'email' => $validated['email'],
                'password' => $validated['password'],
            ];
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->logFailedLogin($method, $credentials);

            return back()->withErrors([
                // Deliberately anonymous: the login page never reveals whether
                // an email/phone is registered or whether the password is wrong.
                $errorKey => 'The provided credentials are incorrect.',
            ])->withInput($request->except('password'));
        }

        $user = Auth::user();

        // Pending accounts (admin-created, awaiting OTP + password setup) and
        // disabled accounts must not sign in. A pending user is routed to the
        // correct continuation page; everyone else sees the same anonymous
        // message as a bad credential.
        if ($user->isPending() || $user->isDisabled()) {
            $isPending = $user->isPending();

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Guide genuinely pending users onward without leaking account
            // existence to anyone probing random addresses.
            if ($isPending && $user->role !== 'admin') {
                if ($method === 'email') {
                    $request->session()->put('activation_email', strtolower(trim($validated['email'])));
                }

                return redirect()->route('account.activation.index')->with(
                    'info',
                    'Your account has not been activated yet. A verification code was sent to your email when your account was created.'
                );
            }

            if ($isPending) {
                return redirect()->route('email.verify.show')->with(
                    'info',
                    'Your account is awaiting email verification. Enter the 6-digit code sent to your email.'
                );
            }

            return back()->withErrors([
                $errorKey => 'The provided credentials are incorrect.',
            ])->withInput($request->except('password'));
        }

        ActivityLogger::userLoggedIn($user, $method === 'phone' ? 'phone number' : 'email');

        return redirect()->route('dashboard.redirect', ['role' => $user->role]);
    }

    public function logout(Request $request)
    {
        // Record the sign-out while the user is still authenticated — after
        // Auth::logout() there is nobody left to attribute the row to.
        if ($user = Auth::user()) {
            ActivityLogger::userLoggedOut($user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Record a rejected sign-in against the account it targeted.
     *
     * Attempts against identifiers that match no account are skipped: the
     * activity table requires a real user_id, and an unknown identifier tells
     * the association nothing it can act on.
     */
    private function logFailedLogin(string $method, array $credentials): void
    {
        if ($method === 'phone') {
            $user = User::where('phone', $credentials['phone'] ?? '')->first();
            $via = 'phone number';
        } else {
            $user = User::where('email', strtolower(trim($credentials['email'] ?? '')))->first();
            $via = 'email';
        }

        if ($user) {
            ActivityLogger::userLoginFailed($user, $via);
        }
    }

    /**
     * Display the Admin Dashboard with live database metrics and user logs.
     */
    public function showAdminDashboard()
    {
        // 1. Calculate live totals directly from the database table
        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $storeOwnersCount = User::where('role', 'store_owner')->count();
        $farmersCount = User::where('role', 'farmer')->count();

        // The members list is the association's registry of people, held
        // apart from the accounts above — an import adds to this list
        // without creating a single login, so the dashboard card counts
        // members here, never farmer accounts.
        $totalMembers = Member::count();

        // 2. Fetch the latest 10 profile registrations
        $recentUsers = User::latest()->take(10)->get();

        // ─── Admin Dashboard Metrics ──────────────────────────────
        // Platform catalogue & activity
        $totalCategories = Category::count();
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $outOfStockProducts = Product::where('status', 'out_of_stock')
            ->orWhere('stock_quantity', 0)
            ->count();
        $discontinuedProducts = Product::where('status', 'discontinued')->count();

        $totalHarvests = HarvestReport::count();
        $totalHarvestYield = HarvestReport::sum('actual_yield_metric_tons');
        $totalHarvestExpenses = HarvestReport::sum('actual_total_expenses');

        // ─── Association season & attention queue ──────────────────
        $currentSeason = SeasonHelper::getCurrentSeason();
        $currentSeasonLabel = SeasonHelper::getSeasonLabel();
        $currentSeasonHarvests = HarvestReport::whereRaw('LOWER(season) LIKE ?', [strtolower($currentSeason).'%'])->count();
        $pendingCount = User::where('status', User::STATUS_PENDING)->count();

        // The one event the dashboard should point at: the nearest entry on
        // the association calendar that hasn't happened yet (scopeUpcoming
        // already orders by date, then start time).
        $upcomingEvent = AssociationEvent::upcoming()->first();

        // Harvest report activity over the past 14 days
        $harvestCountsByDate = DB::table('harvest_reports')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('date')
            ->pluck('total', 'date');

        $harvestTrend = collect(range(13, 0))
            ->map(function ($offset) use ($harvestCountsByDate) {
                $day = now()->subDays($offset);

                return [
                    'date' => $day->toDateString(),
                    'label' => $day->format('M j'),
                    'count' => (int) ($harvestCountsByDate[$day->toDateString()] ?? 0),
                ];
            })
            ->values();

        // Harvest report activity grouped per day for the dashboard graph (last 12 months).
        // Bucketed by harvest_date — WHEN the crop was harvested — not by
        // created_at. The association loads years of paper records through
        // the importer, and every imported row carries the day of the upload
        // as its created_at: plotting that would pile the entire history
        // onto a single day of the current month.
        $harvestDaily = DB::table('harvest_reports')
            ->selectRaw('DATE(harvest_date) as date, COUNT(*) as total, COALESCE(SUM(actual_yield_metric_tons), 0) as yield, COALESCE(SUM(actual_total_expenses), 0) as expenses')
            ->where('harvest_date', '>=', now()->subDays(364)->toDateString())
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $harvestByMonth = $harvestDaily->groupBy(fn ($row, $key) => substr((string) $key, 0, 7));

        $monthRows = function (Carbon $month) use ($harvestByMonth) {
            return $harvestByMonth->get($month->format('Y-m'), collect());
        };

        $harvestGraphYear = collect(range(11, 0))
            ->map(function ($i) use ($monthRows) {
                $m = now()->startOfMonth()->subMonths($i);
                $rows = $monthRows($m);

                return [
                    'label' => $m->format('M'),
                    'count' => (int) $rows->sum('total'),
                    'yield' => (float) $rows->sum('yield'),
                    'expenses' => (float) $rows->sum('expenses'),
                ];
            })
            ->values();

        $dayRow = fn ($d) => $harvestDaily->get($d->toDateString());

        $harvestGraphMonth = collect(range(0, (int) now()->day - 1))
            ->map(function ($d) use ($dayRow) {
                $day = now()->startOfMonth()->addDays($d);
                $row = $dayRow($day);

                return [
                    'label' => (string) $day->day,
                    'count' => (int) ($row->total ?? 0),
                    'yield' => (float) ($row->yield ?? 0),
                    'expenses' => (float) ($row->expenses ?? 0),
                ];
            })
            ->values();

        $harvestGraphWeek = collect(range(6, 0))
            ->map(function ($i) use ($dayRow) {
                $day = now()->subDays($i);
                $row = $dayRow($day);

                return [
                    'label' => $day->format('D j'),
                    'count' => (int) ($row->total ?? 0),
                    'yield' => (float) ($row->yield ?? 0),
                    'expenses' => (float) ($row->expenses ?? 0),
                ];
            })
            ->values();

        $harvestGraphDays = collect(range(29, 0))
            ->map(function ($i) use ($dayRow) {
                $day = now()->subDays($i);
                $row = $dayRow($day);

                return [
                    'label' => $day->format('M j'),
                    'count' => (int) ($row->total ?? 0),
                    'yield' => (float) ($row->yield ?? 0),
                    'expenses' => (float) ($row->expenses ?? 0),
                ];
            })
            ->values();

        $totalQuotations = Quotation::count();
        $quotationStatusCounts = [
            'computed' => Quotation::where('status', Quotation::STATUS_COMPUTED)
                ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()))->count(),
            'viewed' => Quotation::where('status', Quotation::STATUS_VIEWED)
                ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()))->count(),
            'expired' => Quotation::where('valid_until', '<', now())->count(),
        ];
        $computedQuotations = $quotationStatusCounts['computed'];
        $viewedQuotations = $quotationStatusCounts['viewed'];

        // Quotations that need attention: expired, or still unseen by any store
        $attentionQuotations = Quotation::with(['farmer'])
            ->get()
            ->filter(fn ($q) => $q->is_expired || $q->status === Quotation::STATUS_COMPUTED)
            ->sortBy(fn ($q) => $q->is_expired ? 0 : 1)
            ->sortBy(fn ($q) => $q->valid_until?->getTimestamp() ?? PHP_INT_MAX)
            ->take(8)
            ->values();

        // Recent platform-wide quotation activity
        $recentPlatformQuotations = Quotation::with(['farmer', 'items.product.storeOwner'])
            ->latest()
            ->take(5)
            ->get();

        // Latest listings across the marketplace
        $recentProducts = Product::with(['storeOwner', 'category'])
            ->latest()
            ->take(5)
            ->get();

        // Stock health summary (matches the per-product status logic)
        $stockCounts = [
            'in_stock' => Product::where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '>', 'reorder_threshold')
                ->count(),
            'low_stock' => Product::where('stock_quantity', '>', 0)
                ->whereColumn('stock_quantity', '<=', 'reorder_threshold')
                ->count(),
            'out_of_stock' => Product::where('stock_quantity', '<=', 0)->count(),
        ];

        // 3. Render the administration dashboard layout view safely with variables
        return view('dashboard.admin', compact(
            'totalUsers',
            'adminCount',
            'storeOwnersCount',
            'farmersCount',
            'totalMembers',
            'recentUsers',
            'totalCategories',
            'totalProducts',
            'activeProducts',
            'outOfStockProducts',
            'discontinuedProducts',
            'totalHarvests',
            'totalHarvestYield',
            'totalHarvestExpenses',
            'currentSeason',
            'currentSeasonLabel',
            'currentSeasonHarvests',
            'pendingCount',
            'upcomingEvent',
            'harvestTrend',
            'harvestGraphYear',
            'harvestGraphMonth',
            'harvestGraphWeek',
            'harvestGraphDays',
            'totalQuotations',
            'quotationStatusCounts',
            'computedQuotations',
            'viewedQuotations',
            'attentionQuotations',
            'recentPlatformQuotations',
            'recentProducts',
            'stockCounts'
        ));
    }
}
