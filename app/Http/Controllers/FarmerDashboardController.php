<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
        $activeProductCount = Product::active()->count();

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

        return view('dashboard.farmer', compact(
            'totalCrops',
            'recommendedCount',
            'activeProductCount',
            'quotationCount',
            'pendingQuotations',
            'dispatchedQuotations',
            'storeCount',
            'stores',
            'recentQuotations',
            'supportedCrops',
        ));
    }
}
