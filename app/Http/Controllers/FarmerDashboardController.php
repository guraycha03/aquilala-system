<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\WeatherService;
use App\Services\SoilAgronomyService;
use App\Helpers\SeasonHelper;

class FarmerDashboardController extends Controller
{
    /**
     * Display the farmer dashboard with real data.
     */
    public function index()
    {
        $farmerId = Auth::id();

        // ─── KPI Metrics ──────────────────────────────────────────

        // 1. Crops — count from the crops table
        $totalCrops = Crop::count();

        // 2. Recommended Products — count of active products with matching formulas
        $recommendedCount = Product::active()
            ->whereHas('decisionFormulas')
            ->count();

        // 3. Quotations — farmer's own quotation requests
        $quotationCount = Quotation::where('user_id', $farmerId)->count();
        $pendingQuotations = Quotation::where('user_id', $farmerId)
            ->where('status', Quotation::STATUS_PENDING)
            ->count();
        $dispatchedQuotations = Quotation::where('user_id', $farmerId)
            ->where('status', Quotation::STATUS_DISPATCHED)
            ->count();

        // 4. Stores — count of registered store owners
        $storeCount = User::where('role', 'store_owner')->count();

        // ─── Active Products (browse view) ─────────────────────────
        $activeProducts = Product::active()
            ->with(['storeOwner', 'category', 'mainImage'])
            ->latest()
            ->take(8)
            ->get();

        // ─── Stores Directory — store owners with addresses ────────
        $stores = User::where('role', 'store_owner')
            ->whereNotNull('store_name')
            ->with('products')
            ->get();

        // ─── Recent Quotations ─────────────────────────────────────
        $recentQuotations = Quotation::with('storeOwner')
            ->where('user_id', $farmerId)
            ->latest()
            ->take(5)
            ->get();

        // ─── Crop Types available via DecisionFormulas ─────────────
        $supportedCrops = DecisionFormula::select('crop_type')
            ->distinct()
            ->pluck('crop_type');

        // ─── Weather & Climate Data (Real-time from Open-Meteo) ───
        $weatherData = WeatherService::getCurrentWeather();
        $hourlyForecast = WeatherService::getHourlyForecast();
        $weatherAlerts = WeatherService::getWeatherAlerts();
        $weatherContext = WeatherService::getWeatherContextForEngine();

        // ─── Soil & Agronomy Data ─────────────────────────────────
        $soilIndicators = SoilAgronomyService::getSoilIndicators();

        return view('dashboard.farmer', compact(
            'totalCrops',
            'recommendedCount',
            'quotationCount',
            'pendingQuotations',
            'dispatchedQuotations',
            'storeCount',
            'activeProducts',
            'stores',
            'recentQuotations',
            'supportedCrops',
            'weatherData',
            'hourlyForecast',
            'weatherAlerts',
            'weatherContext',
            'soilIndicators',
        ));
    }
}
