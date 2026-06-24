@extends('layouts.sidebar')

@section('title', 'Farmer Dashboard')

@section('content')
<div class="header-panel" style="margin-bottom:24px;">
    <h1 style="font-size:24px;font-weight:700;color:#1e2922;">
        Producer Hub
    </h1>

    <p style="color:#606d64;font-size:14px;">
        Welcome back, {{ auth()->user()->name }}.
    </p>
</div>

<div
    class="status-alert"
    style="
        display:flex;
        align-items:center;
        gap:8px;
        background:#e6f4ea;
        padding:12px 16px;
        border-radius:8px;
        margin-bottom:24px;
        color:#137333;
        font-size:14px;
    "
>
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="16" x2="12" y2="12"/>
        <line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>

    <span>
        New product recommendations and quotation opportunities may be available based on your listed crops.
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
        title="My Crop Listings"
        value="12"
        trendValue="+2"
        trendDirection="up"
        trendLabel="added this month"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Recommended Products"
        value="28"
        trendValue="+5"
        trendDirection="up"
        trendLabel="new matches"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                <path d="M9 18h6"/>
                <path d="M10 22h4"/>
                <path d="M12 2a7 7 0 0 0-4 12.75V17h8v-2.25A7 7 0 0 0 12 2z"/>
            </svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Quotation Requests"
        value="7"
        trendValue="+3"
        trendDirection="up"
        trendLabel="this week"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <path d="M14 2v6h6"/>
            </svg>
        </x-slot:icon>
    </x-kpi-card>

    <x-kpi-card
        title="Matched Stores"
        value="15"
        trendValue="0"
        trendDirection="neutral"
        trendLabel="currently available"
    >
        <x-slot:icon>
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
                <path d="M3 9l1-5h16l1 5"/>
                <path d="M5 9v11h14V9"/>
                <path d="M9 20v-6h6v6"/>
            </svg>
        </x-slot:icon>
    </x-kpi-card>

</section>

<div
    class="data-table-container"
    style="
        background:#ffffff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:24px;
    "
>
    <h2
        style="
            font-size:16px;
            font-weight:600;
            color:#1e2922;
            margin-bottom:12px;
        "
    >
        Active Production Overview
    </h2>

    <p
        style="
            color:#606d64;
            font-size:14px;
            line-height:1.7;
        "
    >
        No active crop records have been listed yet. Add crops to your inventory to receive product recommendations and generate quotations from participating agricultural stores.
    </p>
</div>
@endsection