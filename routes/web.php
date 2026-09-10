<?php

use App\Http\Controllers\AuthController;
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

    // Phone Verification via Email Confirmation — landing page is viewable by
    // anyone (an activated user opening their old link should see "already active").
    Route::get('/confirm-account', [App\Http\Controllers\AccountActivationController::class, 'show'])
        ->name('account.activation.show');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.post');
        Route::get('/register', [AuthController::class, 'showSignup'])->name('register');
        Route::post('/register', [AuthController::class, 'signup'])->name('register.post');
        Route::get('/registration-submitted', [AuthController::class, 'showRegistrationSubmitted'])->name('register.submitted');

        // Completing activation signs the user in, so it must stay guest-only.
        Route::post('/confirm-account', [App\Http\Controllers\AccountActivationController::class, 'complete'])
            ->name('account.activation.complete');
    });

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Requires Session & Active CSRF Token)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Handled by AuthController@logout to invalidate sessions and prevent 419 Page Expired errors
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Centralized Dashboard Redirector
    Route::get('/dashboard', function () {
        $role = Auth::user()->role;
        return match ($role) {
            'admin'       => redirect()->route('dashboard.admin'),
            'store_owner' => redirect()->route('store.dashboard'),
            'farmer'      => redirect()->route('dashboard.farmer'),
            default       => abort(403, 'Unauthorized role access.'),
        };
    })->name('dashboard.redirect');

    /*
    |--------------------------------------------------------------------------
    | Farmer Space
    |--------------------------------------------------------------------------
    */
    Route::prefix('farmer')->name('farmer.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\FarmerDashboardController::class, 'index'])->name('dashboard'); // referenced as dashboard.farmer or farmer.dashboard

        // Crops Management
        Route::get('/crops', [App\Http\Controllers\FarmerCropController::class, 'index'])->name('crops.index');
        Route::post('/crops', [App\Http\Controllers\FarmerCropController::class, 'store'])->name('crops.store');
        Route::put('/crops/{farmerCrop}', [App\Http\Controllers\FarmerCropController::class, 'update'])->name('crops.update');
        Route::delete('/crops/{farmerCrop}', [App\Http\Controllers\FarmerCropController::class, 'destroy'])->name('crops.destroy');
        Route::put('/crops-default-land', [App\Http\Controllers\FarmerCropController::class, 'updateDefaultLand'])->name('crops.default-land');

        // Recommendations
        Route::get('/recommendations', [App\Http\Controllers\ProductController::class, 'recommendations'])->name('recommendations.index');

        // AI Chat
        Route::get('/ai-chat', [App\Http\Controllers\AiChatController::class, 'index'])->name('ai-chat.index');
        Route::post('/ai-chat', [App\Http\Controllers\AiChatController::class, 'send'])->name('ai-chat.send');
        Route::post('/ai-chat/suggestions', [App\Http\Controllers\AiChatController::class, 'suggestions'])->name('ai-chat.suggestions');
        Route::get('/ai-chat/histories', [App\Http\Controllers\AiChatController::class, 'histories'])->name('ai-chat.histories');
        Route::get('/ai-chat/histories/{chatHistory}', [App\Http\Controllers\AiChatController::class, 'history'])->name('ai-chat.history.show');
        Route::post('/ai-chat/histories', [App\Http\Controllers\AiChatController::class, 'saveHistory'])->name('ai-chat.history.save');
        Route::delete('/ai-chat/histories/{chatHistory}', [App\Http\Controllers\AiChatController::class, 'destroyHistory'])->name('ai-chat.history.destroy');

        // Supported Crops Directory
        Route::get('/crops-directory', [App\Http\Controllers\CropsDirectoryController::class, 'index'])->name('crops-directory.index');

        // Products & Store Browsing
        Route::get('/products/browse', [App\Http\Controllers\ProductController::class, 'browse'])->name('products.browse');
        Route::get('/products/{product}', [App\Http\Controllers\ProductController::class, 'show'])->name('products.show');
        Route::get('/stores', [App\Http\Controllers\StoreDirectoryController::class, 'index'])->name('stores.index');

        // Quotations
        Route::get('/quotations', [App\Http\Controllers\QuotationController::class, 'farmerIndex'])->name('quotations.index');
        Route::get('/quotations/request', [App\Http\Controllers\QuotationController::class, 'farmerCreate'])->name('quotations.request');
        Route::get('/quotations/products', [App\Http\Controllers\QuotationController::class, 'farmerProducts'])->name('quotations.products');
        Route::get('/quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'farmerShow'])->name('quotations.show');
        Route::post('/quotations', [App\Http\Controllers\QuotationController::class, 'farmerStore'])->name('quotations.store');
        Route::delete('/quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'farmerDestroy'])->name('quotations.destroy');

        // Activity Log
        Route::get('/activity', [App\Http\Controllers\QuotationController::class, 'farmerActivity'])->name('activity.index');

        // Notifications
        Route::get('/notifications', function () { return view('farmer.notifications.index'); })->name('notifications.index');
    });

    // Explicit fallback alias for dashboard.farmer reference in layout
    Route::get('/farmer/dashboard', [App\Http\Controllers\FarmerDashboardController::class, 'index'])->name('dashboard.farmer');

    /*
    |--------------------------------------------------------------------------
    | Store Owner Space
    |--------------------------------------------------------------------------
    */
    Route::prefix('store')->name('store.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\ProductController::class, 'index'])->name('dashboard');
        Route::get('/inventory', [App\Http\Controllers\InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/{product}/update-stock', [App\Http\Controllers\InventoryController::class, 'updateStock'])->name('inventory.update');
        Route::get('/quotations', [App\Http\Controllers\QuotationController::class, 'storeIndex'])->name('quotations.index');
        Route::get('/quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'storeShow'])->name('quotations.show');
        Route::post('/quotations/ai-overview', [App\Http\Controllers\QuotationController::class, 'storeAiOverview'])->name('quotations.ai-overview');
        Route::get('/storefront', [App\Http\Controllers\ProductController::class, 'storefront'])->name('storefront.index');

        // DSS Input Formulas (rule matrix management)
        Route::get('/formulas', [App\Http\Controllers\FormulaController::class, 'index'])->name('formulas.index');
        Route::get('/formulas/create', [App\Http\Controllers\FormulaController::class, 'create'])->name('formulas.create');
        Route::post('/formulas', [App\Http\Controllers\FormulaController::class, 'store'])->name('formulas.store');
        Route::get('/formulas/{formula}/edit', [App\Http\Controllers\FormulaController::class, 'edit'])->name('formulas.edit');
        Route::put('/formulas/{formula}', [App\Http\Controllers\FormulaController::class, 'update'])->name('formulas.update');
        Route::delete('/formulas/{formula}', [App\Http\Controllers\FormulaController::class, 'destroy'])->name('formulas.destroy');
    
        // Product management routes
        Route::get('/products/create', [App\Http\Controllers\ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [App\Http\Controllers\ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}', [App\Http\Controllers\ProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [App\Http\Controllers\ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [App\Http\Controllers\ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('products.destroy');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Admin Space
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'showAdminDashboard'])->name('dashboard');

        Route::get('/users', [App\Http\Controllers\AdminUserController::class, 'index'])->name('users.index');

        // Admin pre-registration with phone/email uniqueness check + free email activation flow
        Route::get('/users/create', [App\Http\Controllers\AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [App\Http\Controllers\AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [App\Http\Controllers\AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [App\Http\Controllers\AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/resend-invitation', [App\Http\Controllers\AdminUserController::class, 'resend'])
            ->name('users.resend');
        Route::post('/users/{user}/approve', [App\Http\Controllers\AdminUserController::class, 'approve'])
            ->name('users.approve');
        Route::post('/users/{user}/reject', [App\Http\Controllers\AdminUserController::class, 'reject'])
            ->name('users.reject');

        Route::get('/approvals', [App\Http\Controllers\AdminUserController::class, 'approvals'])
            ->name('approvals.index');
        Route::get('/quotations', [App\Http\Controllers\QuotationController::class, 'adminIndex'])->name('quotations.index');
        Route::get('/activity', [App\Http\Controllers\QuotationController::class, 'adminActivity'])->name('activity.index');
        Route::get('/analytics', function () {
            $formulas = \App\Models\DecisionFormula::with('product.category')
                ->orderBy('crop_type')
                ->orderBy('season')
                ->get();

            $grouped = $formulas->groupBy('crop_type')->map(function ($cropFormulas) {
                return $cropFormulas->groupBy('season');
            });

            $days = 30;
            $startDate = now()->subDays($days);

            // ── User Stats ──
            $totalUsers = \App\Models\User::count();
            $farmers = \App\Models\User::where('role', 'farmer')->count();
            $storeOwners = \App\Models\User::where('role', 'store_owner')->count();
            $activeUsers = \App\Models\User::where('status', 'active')->count();

            // User signups by day (120 days so the chart can page back through past months)
            $userSignups = \App\Models\User::where('created_at', '>=', now()->subDays(120))
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // ── Quotation Stats (Cost Estimates) ──
            $totalQuotations = \App\Models\Quotation::count();
            $quotationsThisMonth = \App\Models\Quotation::where('created_at', '>=', $startDate)->count();
            $quotationsBySeason = \App\Models\Quotation::selectRaw('season, COUNT(*) as count')
                ->groupBy('season')
                ->get();

            // Quotations by status
            $quotationsByStatus = \App\Models\Quotation::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();

            // Quotations by day
            $quotationsByDay = \App\Models\Quotation::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // ── Crop Tracking ──
            $totalCrops = \App\Models\FarmerCrop::count();
            $activeCrops = \App\Models\FarmerCrop::whereNotIn('growth_stage', ['harvested', 'failed'])->count();
            $harvestedCrops = \App\Models\FarmerCrop::where('growth_stage', 'harvested')->count();

            // Crops by season
            $cropsBySeason = \App\Models\FarmerCrop::selectRaw('season, COUNT(*) as count')
                ->groupBy('season')
                ->get();

            // Crops by growth stage
            $cropsByStage = \App\Models\FarmerCrop::selectRaw('growth_stage, COUNT(*) as count')
                ->groupBy('growth_stage')
                ->orderByDesc('count')
                ->get();

            // Crops tracked by day
            $cropsByDay = \App\Models\FarmerCrop::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // ── Harvest Reports ──
            $totalHarvests = \App\Models\HarvestReport::count();
            $totalYield = \App\Models\HarvestReport::sum('actual_yield_metric_tons');
            $totalExpenses = \App\Models\HarvestReport::sum('actual_total_expenses');

            // Harvests by crop type
            $harvestsByCrop = \App\Models\HarvestReport::selectRaw('crop_type, COUNT(*) as count, SUM(actual_yield_metric_tons) as yield, SUM(actual_total_expenses) as expenses')
                ->groupBy('crop_type')
                ->get();

            // ── Product Stats ──
            $totalProducts = \App\Models\Product::count();
            $activeProducts = \App\Models\Product::where('status', 'active')->count();
            $lowStockProducts = \App\Models\Product::whereColumn('stock_quantity', '<=', 'reorder_threshold')
                ->where('reorder_threshold', '>', 0)
                ->count();

            // Products by category
            $productsByCategory = \App\Models\Product::join('categories', 'products.category_id', '=', 'categories.id')
                ->selectRaw('categories.name as category, COUNT(*) as count')
                ->groupBy('categories.name')
                ->get();

            // ── Activity Stats ──
            $totalActivities = \App\Models\ActivityLog::count();
            $activitiesThisMonth = \App\Models\ActivityLog::where('created_at', '>=', $startDate)->count();

            // Activities by type
            $activitiesByType = \App\Models\ActivityLog::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->orderByDesc('count')
                ->get();

            // Activities by day
            $activitiesByDay = \App\Models\ActivityLog::where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return view('admin.analytics.index', compact(
                'formulas', 'grouped',
                'totalUsers', 'farmers', 'storeOwners', 'activeUsers', 'userSignups',
                'totalQuotations', 'quotationsThisMonth', 'quotationsBySeason', 'quotationsByStatus', 'quotationsByDay',
                'totalCrops', 'activeCrops', 'harvestedCrops', 'cropsBySeason', 'cropsByStage', 'cropsByDay',
                'totalHarvests', 'totalYield', 'totalExpenses', 'harvestsByCrop',
                'totalProducts', 'activeProducts', 'lowStockProducts', 'productsByCategory',
                'totalActivities', 'activitiesThisMonth', 'activitiesByType', 'activitiesByDay'
            ));
        })->name('analytics.index');
        Route::get('/analytics/matrix', function () {
            $formulas = \App\Models\DecisionFormula::with('product.category')
                ->orderBy('crop_type')
                ->orderBy('season')
                ->get();

            $grouped = $formulas->groupBy('crop_type')->map(function ($cropFormulas) {
                return $cropFormulas->groupBy('season');
            });

            $ruleCount = $formulas->count();
            $cropCount = $grouped->count();
            $seasonLabels = $grouped->flatMap(fn($s) => $s->keys())->unique()->values()->map(fn($n) => ucfirst($n))->implode(' · ');

            return view('admin.analytics.matrix', compact('formulas', 'grouped', 'ruleCount', 'cropCount', 'seasonLabels'));
        })->name('analytics.matrix');
        Route::get('/settings', function () { return view('admin.settings.index'); })->name('settings.index');
        Route::post('/settings/interface', function (\Illuminate\Http\Request $request) {
            $data = $request->validate([
                'yield_unit'            => ['required', 'in:auto,mt,kg,cavan'],
                'yield_decimals'        => ['required', 'integer', 'min:0', 'max:2'],
                'yield_number_separator'=> ['required', 'in:comma,period,none'],
                'yield_zero_dash'       => ['sometimes', 'boolean'],
            ]);

            $separators = ['comma' => ',', 'period' => '.', 'none' => ''];

            \App\Helpers\SettingsHelper::set('yield_unit', $data['yield_unit']);
            \App\Helpers\SettingsHelper::set('yield_decimals', (string) $data['yield_decimals']);
            \App\Helpers\SettingsHelper::set('yield_number_separator', $separators[$data['yield_number_separator']]);
            \App\Helpers\SettingsHelper::set('yield_zero_dash', ($data['yield_zero_dash'] ?? false) ? '1' : '0');

            return response()->json(['ok' => true, 'preview' => \App\Helpers\SettingsHelper::formatYield(2.45)]);
        })->name('settings.interface');
        Route::get('/audit', function () { return view('admin.audit.index'); })->name('audit.index');
    });

    // Explicit fallback alias for dashboard.admin reference in layout and AuthController
    Route::get('/admin/dashboard', [AuthController::class, 'showAdminDashboard'])->name('dashboard.admin');
});