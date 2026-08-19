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

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showSignup'])->name('register');
    Route::post('/register', [AuthController::class, 'signup'])->name('register.post');
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
        Route::post('/quotations/{quotation}/accept', [App\Http\Controllers\QuotationController::class, 'acceptQuotation'])->name('quotations.accept');
        Route::post('/quotations/{quotation}/reject', [App\Http\Controllers\QuotationController::class, 'rejectQuotation'])->name('quotations.reject');

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
        Route::post('/quotations/{quotation}/claim', [App\Http\Controllers\QuotationController::class, 'claimQuotation'])->name('quotations.claim');
        Route::post('/quotations/{quotation}/compute', [App\Http\Controllers\QuotationController::class, 'computePrices'])->name('quotations.compute');
        Route::post('/quotations/{quotation}/dispatch', [App\Http\Controllers\QuotationController::class, 'dispatchQuotation'])->name('quotations.dispatch');
        Route::post('/quotations/{item}/update-item', [App\Http\Controllers\QuotationController::class, 'updateItem'])->name('quotations.update-item');
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

        Route::get('/users', function () {
            $users = \App\Models\User::orderBy('created_at', 'desc')->get();
            return view('admin.users.index', compact('users'));
        })->name('users.index');
        Route::get('/approvals', function () { return view('admin.approvals.index'); })->name('approvals.index');
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

            return view('admin.analytics.index', compact('formulas', 'grouped'));
        })->name('analytics.index');
        Route::get('/settings', function () { return view('admin.settings.index'); })->name('settings.index');
        Route::get('/audit', function () { return view('admin.audit.index'); })->name('audit.index');
    });

    // Explicit fallback alias for dashboard.admin reference in layout and AuthController
    Route::get('/admin/dashboard', [AuthController::class, 'showAdminDashboard'])->name('dashboard.admin');
});