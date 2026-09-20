<?php

use App\Helpers\SeasonHelper;
use App\Helpers\SettingsHelper;
use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\AdminReviewController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminUserImportController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CropsDirectoryController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\FarmerCropController;
use App\Http\Controllers\FarmerDashboardController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\HarvestReportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\StoreDirectoryController;
use App\Http\Controllers\StoreOwnerActivityController;
use App\Models\DecisionFormula;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & Guest Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard.redirect', ['role' => Auth::user()->role]);
    }

    return redirect()->route('login');
})->name('home');

// Guest-only access. Completing activation and password reset sign the
// user in / back out respectively, so every step of these flows stays
// guest-only.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:login');

    // ── Account activation (admin-created farmer / store-owner accounts) ──
    Route::get('/activate', [AccountActivationController::class, 'index'])->name('account.activation.index');
    Route::post('/activate/verify', [AccountActivationController::class, 'verify'])
        ->name('account.activation.verify')->middleware('throttle:otp');
    Route::post('/activate/verify/resend', [AccountActivationController::class, 'resend'])
        ->name('account.activation.resend')->middleware('throttle:otp');
    Route::get('/activate/password', [AccountActivationController::class, 'password'])
        ->name('account.activation.password');
    Route::post('/activate/password', [AccountActivationController::class, 'complete'])
        ->name('account.activation.complete')->middleware('throttle:otp');

    // ── Forgot password (email OTP; account existence is never disclosed) ──
    Route::get('/forgot-password', [PasswordResetController::class, 'showRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendRequest'])
        ->name('password.email')->middleware('throttle:password');
    Route::get('/forgot-password/verify', [PasswordResetController::class, 'showVerify'])->name('password.verify');
    Route::post('/forgot-password/verify', [PasswordResetController::class, 'verifyOtp'])
        ->name('password.verify.post')->middleware('throttle:otp');
    Route::get('/forgot-password/reset', [PasswordResetController::class, 'showReset'])->name('password.reset.show');
    Route::post('/forgot-password/reset', [PasswordResetController::class, 'reset'])
        ->name('password.reset')->middleware('throttle:password');

    // Initial-administrator email/OTP verification. Guest-only by design:
    // a pending admin cannot sign in until their email is verified.
    Route::get('/verify-email', [EmailVerificationController::class, 'show'])
        ->name('email.verify.show');
    Route::post('/verify-email', [EmailVerificationController::class, 'verify'])
        ->name('email.verify')->middleware('throttle:otp');
    Route::post('/verify-email/resend', [EmailVerificationController::class, 'resend'])
        ->name('email.verify.resend')->middleware('throttle:otp');
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Requires Session & Active CSRF Token)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Handled by AuthController@logout to invalidate sessions and prevent 419 Page Expired errors
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Own profile page — available to all roles
    Route::get('/profile', function () {
        return view('profile.index', ['me' => auth()->user()]);
    })->name('profile.index');

    // Personal UI preferences (side menu theme) — available to all roles
    Route::post('/settings/ui', function (Request $request) {
        $theme = (string) $request->input('theme');

        if (! in_array($theme, array_keys(config('sidebar.themes')), true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid theme.'], 422);
        }

        $request->user()->update(['sidebar_theme' => $theme]);

        return response()->json(['ok' => true, 'theme' => $theme]);
    })->name('settings.ui');

    // Personal UI preferences (sub-page / tab navigation style) — available to all roles
    Route::post('/settings/tabs-style', function (Request $request) {
        $style = (string) $request->input('style');

        if (! in_array($style, ['top', 'sidebar'], true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid sub-page style.'], 422);
        }

        $request->user()->update(['sub_pages_style' => $style]);

        return response()->json(['ok' => true, 'style' => $style]);
    })->name('settings.tabs-style');

    // Centralized Dashboard Redirector
    Route::get('/dashboard', function () {
        $role = Auth::user()->role;

        return match ($role) {
            'admin' => redirect()->route('dashboard.admin'),
            'store_owner' => redirect()->route('store.dashboard'),
            'farmer' => redirect()->route('dashboard.farmer'),
            default => abort(403, 'Unauthorized role access.'),
        };
    })->name('dashboard.redirect');

    /*
    |--------------------------------------------------------------------------
    | Farmer Space
    |--------------------------------------------------------------------------
    */
    Route::prefix('farmer')->name('farmer.')->middleware('role:farmer')->group(function () {
        Route::get('/dashboard', [FarmerDashboardController::class, 'index'])->name('dashboard'); // referenced as dashboard.farmer or farmer.dashboard

        // Crops Management
        Route::get('/crops', [FarmerCropController::class, 'index'])->name('crops.index');
        Route::post('/crops', [FarmerCropController::class, 'store'])->name('crops.store');
        Route::put('/crops/{farmerCrop}', [FarmerCropController::class, 'update'])->name('crops.update');
        Route::delete('/crops/{farmerCrop}', [FarmerCropController::class, 'destroy'])->name('crops.destroy');
        Route::put('/crops-default-land', [FarmerCropController::class, 'updateDefaultLand'])->name('crops.default-land');

        // Recommendations
        Route::get('/recommendations', [ProductController::class, 'recommendations'])->name('recommendations.index');

        // AI Chat
        Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
        Route::post('/ai-chat', [AiChatController::class, 'send'])->name('ai-chat.send');
        Route::post('/ai-chat/suggestions', [AiChatController::class, 'suggestions'])->name('ai-chat.suggestions');
        Route::get('/ai-chat/histories', [AiChatController::class, 'histories'])->name('ai-chat.histories');
        Route::get('/ai-chat/histories/{chatHistory}', [AiChatController::class, 'history'])->name('ai-chat.history.show');
        Route::post('/ai-chat/histories', [AiChatController::class, 'saveHistory'])->name('ai-chat.history.save');
        Route::delete('/ai-chat/histories/{chatHistory}', [AiChatController::class, 'destroyHistory'])->name('ai-chat.history.destroy');

        // Crop batch detail
        Route::get('/crops/{farmerCrop}', [FarmerCropController::class, 'show'])
            ->name('crops.show')
            ->whereNumber('farmerCrop');

        // Supported Crops Directory
        Route::get('/crops-directory', [CropsDirectoryController::class, 'index'])->name('crops-directory.index');

        // Products & Store Browsing
        Route::get('/products/browse', [ProductController::class, 'browse'])->name('products.browse');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/stores', [StoreDirectoryController::class, 'index'])->name('stores.index');

        // Product Reviews & Top Rated
        Route::get('/reviews', [ProductReviewController::class, 'index'])->name('reviews.index');
        Route::post('/products/{product}/review', [ProductReviewController::class, 'store'])->name('products.review.store');
        Route::delete('/reviews/{review}', [ProductReviewController::class, 'destroy'])->name('reviews.destroy');

        // Quotations
        Route::get('/quotations', [QuotationController::class, 'farmerIndex'])->name('quotations.index');
        Route::get('/quotations/request', [QuotationController::class, 'farmerCreate'])->name('quotations.request');
        Route::get('/quotations/products', [QuotationController::class, 'farmerProducts'])->name('quotations.products');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'farmerShow'])->name('quotations.show');
        Route::post('/quotations', [QuotationController::class, 'farmerStore'])->name('quotations.store');
        Route::delete('/quotations/{quotation}', [QuotationController::class, 'farmerDestroy'])->name('quotations.destroy');

        // Activity Log
        Route::get('/activity', [QuotationController::class, 'farmerActivity'])->name('activity.index');

        // Harvest Reports (shared with admin — same harvest_reports table)
        Route::get('/harvests', [HarvestReportController::class, 'farmerIndex'])->name('harvests.index');
        Route::post('/harvests', [HarvestReportController::class, 'store'])->name('harvests.store');
        Route::delete('/harvests/{harvestReport}', [HarvestReportController::class, 'destroy'])->name('harvests.destroy');

        // Notifications
        Route::get('/notifications', function () {
            return view('farmer.notifications.index');
        })->name('notifications.index');

        // Settings (UI preferences)
        Route::get('/settings', function () {
            return view('farmer.settings.index');
        })->name('settings.index');
    });

    // Explicit fallback alias for dashboard.farmer reference in layout
    Route::get('/farmer/dashboard', [FarmerDashboardController::class, 'index'])
        ->name('dashboard.farmer')->middleware('role:farmer');

    /*
    |--------------------------------------------------------------------------
    | Store Owner Space
    |--------------------------------------------------------------------------
    */
    Route::prefix('store')->name('store.')->middleware('role:store_owner')->group(function () {
        Route::get('/dashboard', [ProductController::class, 'index'])->name('dashboard');
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/{product}/update-stock', [InventoryController::class, 'updateStock'])->name('inventory.update');
        Route::get('/quotations', [QuotationController::class, 'storeIndex'])->name('quotations.index');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'storeShow'])->name('quotations.show');
        Route::post('/quotations/ai-overview', [QuotationController::class, 'storeAiOverview'])->name('quotations.ai-overview');
        Route::get('/storefront', [ProductController::class, 'storefront'])->name('storefront.index');

        // Notifications & Activity history
        Route::get('/notifications', [StoreOwnerActivityController::class, 'notifications'])->name('notifications.index');
        Route::post('/notifications/read-all', [StoreOwnerActivityController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{activityLog}/read', [StoreOwnerActivityController::class, 'markRead'])->name('notifications.read');
        Route::get('/activity', [StoreOwnerActivityController::class, 'index'])->name('activity.index');

        // Settings (UI preferences)
        Route::get('/settings', function () {
            return view('store.settings.index');
        })->name('settings.index');

        // DSS Input Formulas (rule matrix management)
        Route::get('/formulas', [FormulaController::class, 'index'])->name('formulas.index');
        Route::get('/formulas/create', [FormulaController::class, 'create'])->name('formulas.create');
        Route::post('/formulas', [FormulaController::class, 'store'])->name('formulas.store');
        Route::get('/formulas/{formula}/edit', [FormulaController::class, 'edit'])->name('formulas.edit');
        Route::put('/formulas/{formula}', [FormulaController::class, 'update'])->name('formulas.update');
        Route::delete('/formulas/{formula}', [FormulaController::class, 'destroy'])->name('formulas.destroy');

        // AI Demand Assistant (chat)
        Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
        Route::post('/ai-chat', [AiChatController::class, 'send'])->name('ai-chat.send');
        Route::post('/ai-chat/suggestions', [AiChatController::class, 'suggestions'])->name('ai-chat.suggestions');
        Route::get('/ai-chat/histories', [AiChatController::class, 'histories'])->name('ai-chat.histories');
        Route::get('/ai-chat/histories/{chatHistory}', [AiChatController::class, 'history'])->name('ai-chat.history.show');
        Route::post('/ai-chat/histories', [AiChatController::class, 'saveHistory'])->name('ai-chat.history.save');
        Route::delete('/ai-chat/histories/{chatHistory}', [AiChatController::class, 'destroyHistory'])->name('ai-chat.history.destroy');

        // Product management routes
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Space
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'showAdminDashboard'])->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');

        // Admin pre-registration with phone/email uniqueness check + free email activation flow
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/resend-invitation', [AdminUserController::class, 'resend'])
            ->name('users.resend');
        Route::post('/users/{user}/approve', [AdminUserController::class, 'approve'])
            ->name('users.approve');
        Route::post('/users/{user}/reject', [AdminUserController::class, 'reject'])
            ->name('users.reject');

        // Bulk member import from CSV / Excel / JSON files
        Route::get('/users/import', [AdminUserImportController::class, 'create'])
            ->name('users.import');
        Route::post('/users/import/preview', [AdminUserImportController::class, 'preview'])
            ->name('users.import.preview');
        Route::post('/users/import/confirm', [AdminUserImportController::class, 'confirm'])
            ->name('users.import.confirm');
        Route::post('/users/import/cancel', [AdminUserImportController::class, 'cancel'])
            ->name('users.import.cancel');

        Route::get('/approvals', [AdminUserController::class, 'approvals'])
            ->name('approvals.index');

        // Accredited local supply store directory (Users → Stores tab)
        Route::get('/stores', function () {
            $storeOwners = User::where('role', 'store_owner')
                ->withCount('products')
                ->orderBy('name')
                ->get();

            // A "store" is currently identified by its store name (falling back
            // to the account name) so accounts can later be grouped per store.
            $storeKey = function (User $u): string {
                $name = trim($u->store_name ?? '');
                return $name !== '' ? strtolower($name) : ('user:' . $u->id);
            };

            $totals = [
                'stores'    => $storeOwners->map($storeKey)->unique()->count(),
                'accounts'  => $storeOwners->count(),
                'products'  => $storeOwners->sum('products_count'),
                'inactive'  => $storeOwners->where('status', User::STATUS_DISABLED)->count(),
            ];

            return view('admin.stores.index', compact('storeOwners', 'totals'));
        })->name('stores.index');

        // Product review moderation
        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('/reviews/{review}/disapprove', [AdminReviewController::class, 'disapprove'])->name('reviews.disapprove');
        Route::get('/quotations', [QuotationController::class, 'adminIndex'])->name('quotations.index');
        Route::get('/activity', [QuotationController::class, 'adminActivity'])->name('activity.index');
        Route::get('/analytics', function () {
            // Grouped under the new "Analytics" category — the Demand Trends tab
            // lives on /admin/quotations. Keep this URL redirecting so older
            // links (dashboard KPI cards, breadcrumbs) still land on a useful page.
            return redirect()->route('admin.quotations.index');
        })->name('analytics.index');
        // The formula matrix is now the Crops & Inputs → Formulas tab
        Route::redirect('/analytics/matrix', '/admin/operations/formulas')->name('analytics.matrix');
        Route::get('/settings', function () {
            return view('admin.settings.index');
        })->name('settings.index');
        Route::post('/settings/interface', function (Request $request) {
            $data = $request->validate([
                'yield_unit' => ['required', 'in:auto,mt,kg,cavan'],
                'yield_decimals' => ['required', 'integer', 'min:0', 'max:2'],
                'yield_number_separator' => ['required', 'in:comma,period,none'],
                'yield_zero_dash' => ['sometimes', 'boolean'],
            ]);

            $separators = ['comma' => ',', 'period' => '.', 'none' => ''];

            SettingsHelper::set('yield_unit', $data['yield_unit']);
            SettingsHelper::set('yield_decimals', (string) $data['yield_decimals']);
            SettingsHelper::set('yield_number_separator', $separators[$data['yield_number_separator']]);
            SettingsHelper::set('yield_zero_dash', ($data['yield_zero_dash'] ?? false) ? '1' : '0');

            return response()->json(['ok' => true, 'preview' => SettingsHelper::formatYield(2.45)]);
        })->name('settings.interface');
        // ── Crops & Inputs (grouped pages ──────────────────────────
        Route::get('/operations', fn () => redirect()->route('admin.operations.formulas'))->name('operations.index');
        Route::get('/operations/formulas', function () {
            $formulas = DecisionFormula::with('product.category')
                ->orderBy('crop_type')
                ->orderBy('season')
                ->get();

            $grouped = $formulas->groupBy('crop_type')->map(function ($cropFormulas) {
                return $cropFormulas->groupBy('season');
            });

            $ruleCount = $formulas->count();
            $cropCount = $grouped->count();
            $seasonLabels = $grouped->flatMap(fn ($s) => $s->keys())->unique()->values()->map(fn ($n) => ucfirst($n))->implode(' · ');

            return view('admin.analytics.matrix', compact('formulas', 'grouped', 'ruleCount', 'cropCount', 'seasonLabels'));
        })->name('operations.formulas');
        Route::get('/operations/catalog', function () {
            $products = Product::with('category')->get();

            $productStats = [
                'total' => $products->count(),
                'active' => $products->where('status', 'active')->count(),
                'out' => $products->filter(fn ($p) => $p->status === 'out_of_stock' || (int) $p->stock_quantity <= 0)->count(),
            ];

            $productsByCategory = Product::join('categories', 'products.category_id', '=', 'categories.id')
                ->selectRaw('categories.name as category, COUNT(*) as count')
                ->groupBy('categories.name')
                ->orderByDesc('count')
                ->get();

            $reviewCounts = [
                'pending' => ProductReview::pending()->count(),
                'approved' => ProductReview::approved()->count(),
                'rejected' => ProductReview::rejected()->count(),
            ];
            $reviews = ProductReview::with(['farmer', 'product.storeOwner'])
                ->latest()
                ->take(12)
                ->get();

            return view('admin.operations.catalog', compact('productStats', 'productsByCategory', 'reviewCounts', 'reviews'));
        })->name('operations.catalog');
        Route::get('/operations/seasons', function () {
            $climate = SeasonHelper::getClimateInfo();

            $formulaCounts = DecisionFormula::selectRaw('season, COUNT(*) as count')
                ->groupBy('season')->get()->pluck('count', 'season');
            $cropCounts = FarmerCrop::selectRaw('season, COUNT(*) as count')
                ->groupBy('season')->get()->pluck('count', 'season');
            $quotationCounts = Quotation::selectRaw('season, COUNT(*) as count')
                ->groupBy('season')->get()->pluck('count', 'season');

            $seasons = collect(['wet' => [
                'label' => 'Wet Season',
                'months' => 'June – January',
                'current' => $climate['season'] === 'wet',
                'icon' => 'cloud-rain',
                'typical_weather' => $climate['season'] === 'wet' ? $climate['typical_weather'] : 'Strong southwest monsoon (Habagat) with heavy rainfall and typhoons — the main cropping window.',
                'recommendation' => $climate['season'] === 'wet' ? $climate['recommendation'] : 'Use flood-tolerant rice varieties. Ensure proper drainage systems.',
                'formulas' => (int) ($formulaCounts['wet'] ?? 0),
                'crops' => (int) ($cropCounts['wet'] ?? 0),
                'quotations' => (int) ($quotationCounts['wet'] ?? 0),
            ], 'dry' => [
                'label' => 'Dry Season',
                'months' => 'February – May',
                'current' => $climate['season'] === 'dry',
                'icon' => 'sun',
                'typical_weather' => $climate['season'] === 'dry' ? $climate['typical_weather'] : 'High temperatures, very low rainfall. Requires irrigation for crops.',
                'recommendation' => $climate['season'] === 'dry' ? $climate['recommendation'] : 'Drought-resistant varieties recommended. Ensure adequate irrigation.',
                'formulas' => (int) ($formulaCounts['dry'] ?? 0),
                'crops' => (int) ($cropCounts['dry'] ?? 0),
                'quotations' => (int) ($quotationCounts['dry'] ?? 0),
            ]]);

            return view('admin.operations.seasons', compact('climate', 'seasons'));
        })->name('operations.seasons');

        // ── Analytics & Reports (grouped pages) ────────────────────
        // Expenses — budget vs actual farm expenditure analysis
        Route::get('/analytics/expenses', function () {
            $reports = HarvestReport::with('quotation')->latest()->get();

            $totals = [
                'planned' => $reports->sum(fn ($r) => (float) ($r->quotation?->estimated_total_cost ?? 0)),
                'actual' => $reports->sum(fn ($r) => (float) $r->actual_total_expenses),
                'count' => $reports->count(),
                'yield' => $reports->sum(fn ($r) => (float) $r->actual_yield_metric_tons),
            ];
            $totals['variance'] = $totals['actual'] - $totals['planned'];

            $byCrop = $reports->groupBy('crop_type')->map(function ($group) {
                return [
                    'crop' => $group->first()->crop_type,
                    'count' => $group->count(),
                    'planned' => $group->sum(fn ($r) => (float) ($r->quotation?->estimated_total_cost ?? 0)),
                    'actual' => $group->sum(fn ($r) => (float) $r->actual_total_expenses),
                ];
            })->sortByDesc('actual')->values();

            $bySeason = $reports->groupBy('season')->map(function ($group) {
                return [
                    'season' => ucfirst($group->first()->season ?? 'wet'),
                    'count' => $group->count(),
                    'planned' => $group->sum(fn ($r) => (float) ($r->quotation?->estimated_total_cost ?? 0)),
                    'actual' => $group->sum(fn ($r) => (float) $r->actual_total_expenses),
                ];
            })->values();

            return view('admin.analytics.expenses', compact('totals', 'byCrop', 'bySeason'));
        })->name('analytics.expenses');

        // ── Settings (grouped pages) ───────────────────────────────
        // Roles & Access — role scope + permission matrix
        Route::get('/settings/roles', function () {
            $officers = User::where('role', 'admin')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'status', 'created_at']);

            $storeOwners = User::where('role', 'store_owner')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'phone', 'status', 'store_name', 'approved_at']);

            return view('admin.settings.roles', compact('officers', 'storeOwners'));
        })->name('settings.roles');

        Route::get('/audit', function () {
            return view('admin.audit.index');
        })->name('audit.index');

        // Harvest Reports — admin view of every farmer's harvest reports
        Route::get('/harvests', [HarvestReportController::class, 'adminIndex'])->name('harvests.index');
    });

    // Explicit fallback alias for dashboard.admin reference in layout and AuthController
    Route::get('/admin/dashboard', [AuthController::class, 'showAdminDashboard'])
        ->name('dashboard.admin')->middleware('role:admin');
});
