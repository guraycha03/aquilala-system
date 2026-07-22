@extends('layouts.sidebar')

@section('title', 'Merchant Hub Dashboard')

@section('content')

<style>
    /* Card Hover & Grid Adjustments */
    .dashboard-product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 16px;
    }

    .dashboard-product-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: all 0.2s ease;
    }

    .dashboard-product-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
    }

    .dash-img-wrapper {
        height: 140px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid #f1f5f9;
        position: relative;
    }

    .dash-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .category-pill {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        color: #0f766e;
        background: #ccfbf1;
        padding: 2px 6px;
        border-radius: 4px;
        letter-spacing: 0.03em;
    }

    .badge-stock-ok { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
    .badge-stock-low { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

    .btn-manage-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #14532d;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s;
    }

    .btn-manage-link:hover {
        color: #166534;
        text-decoration: underline;
    }
</style>

<!-- Header Panel -->
<div class="header-panel" style="margin-bottom:24px;">
    <h1 style="font-size:24px;font-weight:700;color:#1e2922;margin:0 0 4px 0;">
        Merchant Hub Console
    </h1>

    <p style="color:#606d64;font-size:14px;margin:0;">
        Welcome back, {{ auth()->user()->name }}.
        Manage your product catalog, recommendations, quotations, and pricing formulas.
    </p>
</div>

<!-- Notification Banner -->
<div
    style="
        display:flex;
        align-items:center;
        gap:8px;
        background:#fffbeb;
        padding:12px 16px;
        border-radius:8px;
        margin-bottom:24px;
        color:#b45309;
        border:1px solid #fef3c7;
        font-size:14px;
    "
>
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="16" x2="12" y2="12"/>
        <line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>

    <span>
        New quotation requests and product recommendation opportunities are available.
    </span>
</div>

<!-- KPI Metrics Grid -->
<section
    class="metrics-grid"
    style="
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
        gap:16px;
        margin-bottom:24px;
    "
>
    <x-kpi-card
        title="Product Listings"
        value="{{ isset($products) ? $products->count() : '0' }}"
        trendValue="+{{ isset($products) ? $products->count() : '0' }}"
        trendDirection="up"
        trendLabel="active items"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                <path d="M20 7L12 3 4 7l8 4 8-4z"/>
                <path d="M4 7v10l8 4 8-4V7"/>
            </svg>
        </x-slot:icon>
    </x-kpi-card>

    @php
        $pendingQuotationsCount = \App\Models\Quotation::where(function($q) {
            $q->whereNull('store_user_id')->orWhere('store_user_id', Auth::id());
        })->where('status', 'pending')->count();
        $dispatchedQuotationsCount = \App\Models\Quotation::where('store_user_id', Auth::id())->where('status', 'dispatched')->count();
    @endphp

    <x-kpi-card
        title="Pending Quotations"
        value="{{ $pendingQuotationsCount }}"
        trendValue="+{{ $dispatchedQuotationsCount }}"
        trendDirection="up"
        trendLabel="dispatched"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <path d="M14 2v6h6"/>
            </svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Recommendation Matches"
        value="24"
        trendValue="+8"
        trendDirection="up"
        trendLabel="new matches"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                <circle cx="12" cy="12" r="10"/>
                <path d="M8 12l2.5 2.5L16 9"/>
            </svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Pricing Formulas"
        value="4"
        trendValue="0"
        trendDirection="neutral"
        trendLabel="active rules"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                <path d="M9 3H5a2 2 0 0 0-2 2v4"/>
                <path d="M15 21h4a2 2 0 0 0 2-2v-4"/>
                <path d="M21 9V5a2 2 0 0 0-2-2h-4"/>
                <path d="M3 15v4a2 2 0 0 0 2 2h4"/>
            </svg>
        </x-slot:icon>
    </x-kpi-card>
</section>

<!-- SECTION: AVAILABLE STORE PRODUCTS -->
<div
    style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        overflow:hidden;
        margin-bottom:24px;
    "
>
    <div
        style="
            padding:18px 20px;
            border-bottom:1px solid #e5e7eb;
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:12px;
        "
    >
        <div>
            <h2 style="font-size:16px;font-weight:700;color:#1e2922;margin:0;">
                Available Products
            </h2>
            <p style="font-size:12px;color:#606d64;margin:2px 0 0 0;">
                Items currently listed in your store inventory and visible to farmers.
            </p>
        </div>

        <a href="{{ route('store.storefront.index') }}" class="btn-manage-link">
            Manage Catalog
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
    </div>

    <div style="padding:20px;">
        @if(isset($products) && $products->count() > 0)
            <div class="dashboard-product-grid">
                @foreach($products as $product)
                    <div class="dashboard-product-card">
                        <div class="dash-img-wrapper">
                            @if($product->mainImage && !empty($product->mainImage->file_path))
                                <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}">
                            @elseif($product->images && $product->images->first())
                                <img src="{{ asset('storage/' . $product->images->first()->file_path) }}" alt="{{ $product->name }}">
                            @else
                                <svg viewBox="0 0 24 24" width="36" height="36" stroke="#94a3b8" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21 15 16 10 5 21"/>
                                </svg>
                            @endif
                        </div>

                        <div style="padding:14px; flex-grow:1; display:flex; flex-direction:column; justify-content:space-between;">
                            <div>
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                    <span class="category-pill">{{ $product->category->name ?? $product->category ?? 'General' }}</span>
                                    <span style="font-size:11px; color:#94a3b8; font-family:monospace;">{{ $product->sku }}</span>
                                </div>

                                <h3 style="font-size:14px; font-weight:700; color:#1e2922; margin:0 0 4px 0; line-height:1.3;">
                                    {{ $product->name }}
                                </h3>

                                <p style="font-size:12px; color:#64748b; margin:0 0 12px 0; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                    {{ $product->description ?: 'No description provided.' }}
                                </p>
                            </div>

                            <div style="padding-top:10px; border-top:1px dashed #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                                <div style="font-size:16px; font-weight:800; color:#0f172a;">
                                    ₱{{ number_format($product->price, 2) }}
                                </div>

                                @php $qty = $product->stock_quantity ?? 0; @endphp
                                <span style="font-size:11px; font-weight:600; padding:2px 8px; border-radius:6px;" class="{{ $qty <= ($product->reorder_threshold ?? 5) ? 'badge-stock-low' : 'badge-stock-ok' }}">
                                    {{ $qty }} in stock
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="text-align:center; padding:36px 16px; background:#f8fafc; border:1px dashed #e2e8f0; border-radius:10px;">
                <svg viewBox="0 0 24 24" width="40" height="40" stroke="#94a3b8" stroke-width="1.5" fill="none" style="margin-bottom:8px;">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h4 style="font-size:15px; font-weight:700; color:#1e2922; margin:0 0 4px 0;">No Products Available</h4>
                <p style="font-size:13px; color:#64748b; margin:0 0 14px 0;">You haven't listed any products in your store storefront yet.</p>
                <a href="{{ route('store.products.create') }}" style="background:#14532d; color:#fff; font-size:13px; font-weight:600; padding:8px 14px; border-radius:6px; text-decoration:none; display:inline-block;">
                    + Add New Product
                </a>
            </div>
        @endif
    </div>
</div>

<!-- SECTION: RECENT QUOTATION ACTIVITY -->
<div
    style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        overflow:hidden;
    "
>
    <div
        style="
            padding:20px;
            border-bottom:1px solid #e5e7eb;
        "
    >
        <h2
            style="
                font-size:16px;
                font-weight:700;
                color:#1e2922;
                margin:0;
            "
        >
            Recent Quotation Activity
        </h2>
    </div>

    <div style="padding:24px;">
        <p
            style="
                color:#606d64;
                font-size:14px;
                line-height:1.7;
                margin:0;
            "
        >
            No quotation requests have been processed yet. Once farmers request quotations for your listed products, activity records will appear here.
        </p>
    </div>
</div>

@endsection