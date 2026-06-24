<?php

use App\Http\Controllers\AuthController;
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
        Route::get('/quotations', fn () => view('admin.quotations.index'))->name('admin.quotations.index');
        Route::get('/analytics', fn () => view('admin.analytics.index'))->name('admin.analytics.index');
        Route::get('/settings', fn () => view('admin.settings.index'))->name('admin.settings.index');
        Route::get('/audit', fn () => view('admin.audit.index'))->name('admin.audit.index');
    });

    // --- Store Owner Space (B2B Store Pipeline) ---
    Route::middleware(RoleMiddleware::class . ':store_owner')->prefix('store')->group(function () {
        Route::get('/dashboard', fn () => view('dashboard.store_owner'))->name('dashboard.store_owner');
        Route::get('/inventory', fn () => view('store.inventory.index'))->name('store.inventory.index');
        Route::get('/quotations', fn () => view('store.quotations.index'))->name('store.quotations.index');
        Route::get('/storefront', fn () => view('store.storefront.index'))->name('store.storefront.index');
        Route::get('/formulas', fn () => view('store.formulas.index'))->name('store.formulas.index');
      
    });

    // --- Farmer Space ---
    // --- Farmer Space ---
    Route::middleware(RoleMiddleware::class . ':farmer')->prefix('farmer')->group(function () {
        Route::get('/dashboard', fn () => view('dashboard.farmer'))->name('dashboard.farmer');
        Route::get('/crops', fn () => view('farmer.crops.index'))->name('farmer.crops.index');
        Route::get('/recommendations', fn () => view('farmer.recommendations.index'))->name('farmer.recommendations.index');
        Route::get('/products/browse', fn () => view('farmer.products.browse'))->name('farmer.products.browse');
        Route::get('/quotations/request', fn () => view('farmer.quotations.request'))->name('farmer.quotations.request');
        Route::get('/quotations', fn () => view('farmer.quotations.index'))->name('farmer.quotations.index');
        Route::get('/stores', fn () => view('farmer.stores.index'))->name('farmer.stores.index');
        Route::get('/notifications', fn () => view('farmer.notifications.index'))->name('farmer.notifications.index');
    });
});

