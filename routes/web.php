<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FarmerDashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\StoreDirectoryController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// --- Public Guest Access ---
Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showSignup'])->name('register');
Route::post('/register', [AuthController::class, 'signup'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- Authenticated User Space ---
Route::middleware('auth')->group(function () {
    // Core Entrance Hub Redirect
    Route::get('/dashboard', function () {
        return redirect()->route('dashboard.redirect', ['role' => Auth::user()->role]);
    });

    Route::get('/dashboard/redirect/{role}', function ($role) {
        return match ($role) {
            'admin' => redirect()->route('dashboard.admin'),
            'store_owner' => redirect()->route('dashboard.store_owner'),
            default => redirect()->route('dashboard.farmer'),
        };
    })->name('dashboard.redirect');

    // --- Admin Control Panel ---
    Route::middleware(RoleMiddleware::class . ':admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AuthController::class, 'showAdminDashboard'])->name('dashboard.admin');
        Route::get('/users', fn () => view('admin.users.index'))->name('admin.users.index');
        Route::get('/approvals', fn () => view('admin.approvals.index'))->name('admin.approvals.index');
        Route::get('/formulas', fn () => view('admin.formulas.index'))->name('admin.formulas.index');
        Route::get('/quotations', [QuotationController::class, 'adminIndex'])->name('admin.quotations.index');
        Route::get('/analytics', fn () => view('admin.analytics.index'))->name('admin.analytics.index');
        Route::get('/settings', fn () => view('admin.settings.index'))->name('admin.settings.index');
        Route::get('/audit', fn () => view('admin.audit.index'))->name('admin.audit.index');
    });

    // --- Store Owner Space (B2B Store Pipeline) ---
    Route::middleware(RoleMiddleware::class . ':store_owner')->prefix('store')->group(function () {
        
        // ✅ FIXED: Point to ProductController@index instead of a raw closure
        Route::get('/dashboard', [ProductController::class, 'index'])->name('dashboard.store_owner');
        
        Route::get('/inventory', [InventoryController::class, 'index'])->name('store.inventory.index');
        Route::get('/inventory/stats', [InventoryController::class, 'stats'])->name('store.inventory.stats');
        Route::post('/inventory/{product}/update-stock', [InventoryController::class, 'updateStock'])->name('store.inventory.update-stock');
        // Quotation routes for store owner
        Route::get('/quotations', [QuotationController::class, 'storeIndex'])->name('store.quotations.index');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'storeShow'])->name('store.quotations.show');
        Route::post('/quotations/{quotation}/claim', [QuotationController::class, 'claimQuotation'])->name('store.quotations.claim');
        Route::post('/quotations/{quotation}/compute', [QuotationController::class, 'computePrices'])->name('store.quotations.compute');
        Route::put('/quotations/items/{item}', [QuotationController::class, 'updateItem'])->name('store.quotations.update-item');
        Route::post('/quotations/{quotation}/dispatch', [QuotationController::class, 'dispatchQuotation'])->name('store.quotations.dispatch');

        Route::get('/storefront', [ProductController::class, 'index'])->name('store.storefront.index');
        Route::get('/formulas', fn () => view('store.formulas.index'))->name('store.formulas.index');

        // Product CRUD routes
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('store.products.index');
            Route::get('/create', [ProductController::class, 'create'])->name('store.products.create');
            Route::post('/', [ProductController::class, 'store'])->name('store.products.store');
            Route::get('/{product}', [ProductController::class, 'show'])->name('store.products.show');
            Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('store.products.edit');
            Route::put('/{product}', [ProductController::class, 'update'])->name('store.products.update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('store.products.destroy');
        });
    });

    // --- Farmer Space ---
    Route::middleware(RoleMiddleware::class . ':farmer')->prefix('farmer')->group(function () {
        Route::get('/dashboard', [FarmerDashboardController::class, 'index'])->name('dashboard.farmer');
        Route::get('/crops', fn () => view('farmer.crops.index'))->name('farmer.crops.index');
        Route::get('/recommendations', function () {
            $products = \App\Models\Product::active()
                ->with(['category', 'storeOwner', 'mainImage'])
                ->orderBy('created_at', 'desc')
                ->get();
            return view('farmer.recommendations.index', compact('products'));
        })->name('farmer.recommendations.index');
        Route::get('/products/browse', [ProductController::class, 'browse'])->name('farmer.products.browse');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('farmer.products.show');
        Route::get('/quotations/request', [QuotationController::class, 'farmerCreate'])->name('farmer.quotations.request');
        Route::post('/quotations', [QuotationController::class, 'farmerStore'])->name('farmer.quotations.store');
        Route::get('/quotations', [QuotationController::class, 'farmerIndex'])->name('farmer.quotations.index');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'farmerShow'])->name('farmer.quotations.show');
        Route::post('/quotations/{quotation}/accept', [QuotationController::class, 'acceptQuotation'])->name('farmer.quotations.accept');
        Route::post('/quotations/{quotation}/reject', [QuotationController::class, 'rejectQuotation'])->name('farmer.quotations.reject');
        Route::get('/stores', [StoreDirectoryController::class, 'index'])->name('farmer.stores.index');
        Route::get('/notifications', fn () => view('farmer.notifications.index'))->name('farmer.notifications.index');
    });
});
