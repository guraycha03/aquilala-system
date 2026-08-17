@extends('layouts.sidebar')

@section('title', $product->name)

@section('content')
<div class="header-panel" style="margin-bottom: 24px;">
    <nav style="font-size: 13px; color: #87a992; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
        <a href="{{ route('farmer.products.browse') }}" style="color: #87a992; text-decoration: none;">Browse Products</a>
        <span>›</span>
        <span style="color: #4a574e;">{{ $product->name }}</span>
    </nav>
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">{{ $product->name }}</h1>
    <p style="color: #606d64; font-size: 14px;">Supplied by <strong>{{ $product->storeOwner?->name ?? 'Unknown Merchant' }}</strong></p>
</div>

<style>
    /* Single unified white container for the whole product detail */
    .product-detail-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        padding: 28px;
        margin-bottom: 32px;
    }

    /* Top hero section: image (left) + main info (right) */
    .detail-hero {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 32px;
        align-items: stretch;
    }
    .product-image-panel {
        background: #f8fafc;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        min-height: 340px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-size: 14px;
        overflow: hidden;
    }
    .product-image-panel img {
        width: 100%;
        height: 100%;
        max-height: 380px;
        object-fit: contain;
        display: block;
        padding: 12px;
    }

    .price-display { font-size: 30px; font-weight: 800; color: #143d23; letter-spacing: -0.01em; }
    .price-unit { font-size: 14px; color: #87a992; font-weight: 400; margin-top: 2px; }

    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .badge-green { background: #f0fdf4; color: #16a34a; }
    .badge-red { background: #fef2f2; color: #dc2626; }
    .badge-blue { background: #eff6ff; color: #2563eb; }
    .badge-amber { background: #fffbeb; color: #d97706; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .status-dot-green { background: #16a34a; }
    .status-dot-red { background: #dc2626; }
    .status-dot-amber { background: #d97706; }

    .btn-quote {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        background: #143d23; color: white; padding: 12px 28px;
        border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: background 0.2s;
    }
    .btn-quote:hover { background: #1a5a30; }

    .main-info-list { margin-top: 20px; border-top: 1px solid #eef2f6; padding-top: 8px; }
    .info-row { display: flex; justify-content: space-between; gap: 16px; padding: 10px 0; border-bottom: 1px solid #f1f5f4; font-size: 14px; }
    .info-row:last-child { border-bottom: none; }
    .info-label { color: #87a992; font-weight: 500; flex-shrink: 0; }
    .info-value { color: #1e2922; font-weight: 600; text-align: right; }

    /* Bottom spec sections - no separate cards, just divider bands inside the same container */
    .spec-divider { border-top: 1px solid #e8edf2; margin: 28px 0 0; }
    .spec-section { background: #fbfcfd; border: 1px solid #eef2f6; border-radius: 10px; padding: 22px; margin-top: 20px; }
    .spec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0 32px; }
    .section-title { font-size: 13px; font-weight: 700; color: #3d4a41; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .section-title svg { width: 18px; height: 18px; stroke: #143d23; fill: none; stroke-width: 2; }
    .fertilizer-npk { display: flex; gap: 16px; margin: 12px 0 16px; }
    .npk-pill { padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 18px; text-align: center; min-width: 60px; }
    .npk-n { background: #fef2f2; color: #dc2626; }
    .npk-p { background: #eff6ff; color: #2563eb; }
    .npk-k { background: #fefce8; color: #ca8a04; }
    .desc-text { color: #3d4a41; font-size: 14px; line-height: 1.8; margin: 0; }

    /* Recommended products list */
    .recommended-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 12px; }
    .related-card { background: #fbfcfd; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; transition: box-shadow 0.2s; }
    .related-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .related-card h4 { font-size: 14px; font-weight: 600; color: #1e2922; margin-bottom: 4px; }
    .related-card .related-price { font-size: 16px; font-weight: 700; color: #143d23; }

    @media (max-width: 768px) {
        .detail-hero { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .recommended-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .related-card { padding: 12px; }
        .product-image-panel { min-height: 220px; }
        .product-detail-container { padding: 16px; }
    }
</style>

<div class="product-detail-container">
    <!-- HERO: Image (left) + Main Details (right) -->
    <div class="detail-hero">
        <!-- LEFT: Product Image -->
        <div class="product-image-panel">
            @if($product->mainImage)
                <img src="{{ $product->mainImage->url }}" alt="{{ $product->name }}">
            @else
                <div style="text-align: center;">
                    <svg viewBox="0 0 24 24" width="48" height="48" stroke="#cbd5e1" fill="none" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                    <p style="margin-top: 8px; font-size: 12px;">Product Image</p>
                </div>
            @endif
        </div>

        <!-- RIGHT: Main / Important Details -->
        <div>
            <div class="price-display">₱{{ number_format($product->price, 2) }}</div>
            <div class="price-unit">{{ $product->price_unit ?? 'each' }}</div>

            <div style="margin-top: 14px; display: flex; flex-wrap: wrap; gap: 8px;">
                <span class="badge {{ $product->status === 'active' ? 'badge-green' : ($product->status === 'discontinued' ? 'badge-red' : 'badge-amber') }}">
                    <span class="status-dot {{ $product->status === 'active' ? 'status-dot-green' : ($product->status === 'discontinued' ? 'status-dot-red' : 'status-dot-amber') }}"></span>
                    {{ ucfirst(str_replace('_', ' ', $product->status)) }}
                </span>
                <span class="badge {{ $product->is_low_stock ? 'badge-red' : 'badge-green' }}">
                    {{ $product->stock_status }}
                </span>
                <span class="badge badge-blue">In Stock: {{ $product->stock_quantity }} {{ $product->stock_unit ?? 'pieces' }}</span>
            </div>

@if(auth()->user()->role === 'farmer')
            <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="{{ route('farmer.quotations.request', ['category_id' => $product->category_id]) }}" class="btn-quote">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Request Quotation
                </a>
            </div>
            @endif

            <div class="main-info-list">
                <div class="info-row">
                    <span class="info-label">Category</span>
                    <span class="info-value">{{ $product->category?->name ?? $product->getOriginal('category') ?? 'Uncategorized' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Brand</span>
                    <span class="info-value">{{ $product->brand ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">SKU</span>
                    <span class="info-value" style="font-family: monospace;">{{ $product->sku }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Product Type</span>
                    <span class="info-value">{{ $product->product_type ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Recommended Season</span>
                    <span class="info-value">
                        @if($product->recommended_season === 'wet') 🌧️ Wet Season
                        @elseif($product->recommended_season === 'dry') ☀️ Dry Season
                        @else 🔄 All Seasons
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Merchant</span>
                    <span class="info-value">{{ $product->storeOwner?->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Divider between hero and the extra spec details -->
    <div class="spec-divider"></div>

    <!-- BOTTOM: Extra / Secondary Details (kept together in the same white container) -->
    <div class="spec-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Packaging & Pricing
        </div>
        <div class="spec-grid">
            <div class="info-row">
                <span class="info-label">Package Size</span>
                <span class="info-value">{{ $product->package_size ? $product->package_size . ' ' . ($product->package_unit ?? 'pieces') : 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Packaging Type</span>
                <span class="info-value">{{ $product->packaging_type ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Price Unit</span>
                <span class="info-value">{{ $product->price_unit ?? 'each' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Stock Quantity</span>
                <span class="info-value">{{ $product->stock_quantity }} {{ $product->stock_unit ?? 'pieces' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Reorder Threshold</span>
                <span class="info-value">{{ $product->reorder_threshold }} {{ $product->stock_unit ?? 'pieces' }}</span>
            </div>
            @if($product->coverage_estimate)
            <div class="info-row">
                <span class="info-label">Estimated Coverage</span>
                <span class="info-value" style="font-size: 12px;">{{ $product->coverage_estimate }}</span>
            </div>
            @endif
        </div>
    </div>

    @if($product->fertilizerDetail)
    <div class="spec-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            Fertilizer Composition
        </div>

        <div class="fertilizer-npk">
            @if($product->fertilizerDetail->nitrogen_percentage !== null)
            <div class="npk-pill npk-n">
                <div style="font-size: 11px; font-weight: 500; text-transform: uppercase;">N</div>
                {{ $product->fertilizerDetail->nitrogen_percentage }}%
            </div>
            @endif
            @if($product->fertilizerDetail->phosphorus_percentage !== null)
            <div class="npk-pill npk-p">
                <div style="font-size: 11px; font-weight: 500; text-transform: uppercase;">P</div>
                {{ $product->fertilizerDetail->phosphorus_percentage }}%
            </div>
            @endif
            @if($product->fertilizerDetail->potassium_percentage !== null)
            <div class="npk-pill npk-k">
                <div style="font-size: 11px; font-weight: 500; text-transform: uppercase;">K</div>
                {{ $product->fertilizerDetail->potassium_percentage }}%
            </div>
            @endif
        </div>

        <div class="spec-grid">
            <div class="info-row">
                <span class="info-label">Form Type</span>
                <span class="info-value">{{ $product->fertilizerDetail->form_type ?? 'N/A' }}</span>
            </div>
            @if($product->fertilizerDetail->application_rate_min)
            <div class="info-row">
                <span class="info-label">Application Rate</span>
                <span class="info-value">
                    {{ $product->fertilizerDetail->application_rate_min }}@if($product->fertilizerDetail->application_rate_max) – {{ $product->fertilizerDetail->application_rate_max }}@endif
                    {{ $product->fertilizerDetail->application_rate_unit ?? '' }}
                </span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Application Method</span>
                <span class="info-value">{{ $product->fertilizerDetail->application_method ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Timing</span>
                <span class="info-value">{{ $product->fertilizerDetail->application_timing ?? 'N/A' }}</span>
            </div>
        </div>
    </div>
    @endif

    @if($product->registration)
    <div class="spec-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Certification & Traceability
        </div>

        <div class="spec-grid">
            <div class="info-row">
                <span class="info-label">Registration Status</span>
                <span class="info-value">
                    <span class="badge {{ $product->registration->registration_status === 'registered' ? 'badge-green' : 'badge-amber' }}">
                        {{ ucfirst($product->registration->registration_status ?? 'N/A') }}
                    </span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Registration Authority</span>
                <span class="info-value">{{ $product->registration->registration_authority ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Registration Number</span>
                <span class="info-value" style="font-family: monospace;">{{ $product->registration->registration_number ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Lot / Batch Number</span>
                <span class="info-value" style="font-family: monospace;">{{ $product->registration->lot_number ?? $product->registration->batch_number ?? 'N/A' }}</span>
            </div>
            @if($product->registration->manufacturing_date)
            <div class="info-row">
                <span class="info-label">Manufacturing Date</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($product->registration->manufacturing_date)->format('M d, Y') }}</span>
            </div>
            @endif
            @if($product->registration->expiration_date)
            <div class="info-row">
                <span class="info-label">Expiration Date</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($product->registration->expiration_date)->format('M d, Y') }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="spec-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Product Description
        </div>
        <p class="desc-text">
            {{ $product->description ?: 'No description provided for this product.' }}
        </p>
    </div>
</div>

<!-- Recommended Products -->
@if($relatedProducts->count() > 0)
<div style="margin-top: 32px;">
    <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin-bottom: 16px;">Recommended Products in {{ $product->category?->name ?? 'this Category' }}</h2>
    <div class="recommended-grid">
        @foreach($relatedProducts as $related)
        <a href="{{ route(auth()->user()->role === 'store_owner' ? 'store.products.show' : 'farmer.products.show', $related->id) }}" style="text-decoration: none; color: inherit;">
            <div class="related-card">
                <h4>{{ $related->name }}</h4>
                <div class="related-price">₱{{ number_format($related->price, 2) }}</div>
                <div style="font-size: 12px; color: #87a992; margin-top: 4px;">
                    <span class="badge {{ $related->is_low_stock ? 'badge-red' : 'badge-green' }}" style="font-size: 10px;">
                        {{ $related->is_low_stock ? '⚠️ Low Stock' : '✓ In Stock' }}
                    </span>
                    {{ $related->stock_quantity }} {{ $related->stock_unit ?? 'pieces' }}
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif
@endsection

