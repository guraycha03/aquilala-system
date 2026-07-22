<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Helpers\SeasonHelper;

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
        // Share global season/climate data with ALL views
        View::share('globalSeason', SeasonHelper::getCurrentSeason());
        View::share('globalSeasonLabel', SeasonHelper::getSeasonLabel());
        View::share('globalClimateInfo', SeasonHelper::getClimateInfo());
    }
}

