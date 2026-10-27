<?php

namespace App\Providers;

use App\Helpers\SeasonHelper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shared rate limiters for credential / OTP endpoints. They key on the
        // request IP so brute-force attempts against login, activation, and
        // password-reset code entry are throttled hard.
        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->ip().'|login');
        });

        RateLimiter::for('otp', function ($request) {
            return Limit::perMinute(6)->by($request->ip().'|otp');
        });

        RateLimiter::for('password', function ($request) {
            return Limit::perMinute(5)->by($request->ip().'|password');
        });

        // Privileged self-service actions (granting administrator access).
        // Keyed on the acting account, not the IP: it is a credential check an
        // already-signed-in admin is performing, so a wrong password must be
        // retryable a few times but never brute-forceable.
        RateLimiter::for('admin-grant', function ($request) {
            return Limit::perMinute(5)->by($request->user()?->id.'|admin-grant');
        });

        // Share global season/climate data with ALL views
        View::share('globalSeason', SeasonHelper::getCurrentSeason());
        View::share('globalSeasonLabel', SeasonHelper::getSeasonLabel());
        View::share('globalClimateInfo', SeasonHelper::getClimateInfo());
    }
}
