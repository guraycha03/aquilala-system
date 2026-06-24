@extends('layouts.sidebar')

@section('title', 'Merchant Hub Dashboard')

@section('content')
<div class="header-panel" style="margin-bottom:24px;">
    <h1 style="font-size:24px;font-weight:700;color:#1e2922;">
        Merchant Hub Console
    </h1>

    <p style="color:#606d64;font-size:14px;">
        Welcome back, {{ auth()->user()->name }}.
        Manage your product catalog, recommendations, quotations, and pricing formulas.
    </p>
</div>

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
        value="48"
        trendValue="+6"
        trendDirection="up"
        trendLabel="added this month"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                <path d="M20 7L12 3 4 7l8 4 8-4z"/>
                <path d="M4 7v10l8 4 8-4V7"/>
            </svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Pending Quotations"
        value="5"
        trendValue="+2"
        trendDirection="up"
        trendLabel="awaiting response"
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
            "
        >
            No quotation requests have been processed yet. Once farmers request quotations for your listed products, activity records will appear here.
        </p>
    </div>
</div>
@endsection