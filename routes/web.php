<?php

use App\Http\Controllers\AuthController;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [AuthController::class, 'showSignup'])->name('register');
Route::post('/register', [AuthController::class, 'signup'])->name('register.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('dashboard.redirect', ['role' => auth()->user()->role]);
    });

    Route::get('/dashboard/redirect/{role}', function ($role) {
        return match ($role) {
            'admin' => view('dashboard.admin'),
            'store_owner' => view('dashboard.store_owner'),
            default => view('dashboard.farmer'),
        };
    })->name('dashboard.redirect');

    Route::middleware(RoleMiddleware::class.':admin')->group(function () {
        Route::get('/dashboard/admin', fn () => view('dashboard.admin'))->name('dashboard.admin');
    });

    Route::middleware(RoleMiddleware::class.':store_owner')->group(function () {
        Route::get('/dashboard/store-owner', fn () => view('dashboard.store_owner'))->name('dashboard.store_owner');
    });

    Route::middleware(RoleMiddleware::class.':farmer')->group(function () {
        Route::get('/dashboard/farmer', fn () => view('dashboard.farmer'))->name('dashboard.farmer');
    });
});

