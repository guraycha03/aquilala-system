@extends('layouts.sidebar')

@section('title', $product->display_name)

@section('content')
  {{-- store owners preview this page from their storefront, so the tabs and
       breadcrumb must not link into the role:farmer routes (403 on click). --}}
  @php
    $isStoreOwner = auth()->user()->role === 'store_owner';
    $browseUrl = $isStoreOwner ? route('store.storefront.index') : route('farmer.products.browse');
    $tabs = [['key' => 'browse', 'label' => $isStoreOwner ? 'My Products' : 'Browse Products', 'url' => $browseUrl]];
    if (! $isStoreOwner) {
      $tabs[] = ['key' => 'top-quoted', 'label' => 'Top Quoted Products', 'url' => route('farmer.top-quoted.index')];
      $tabs[] = ['key' => 'stores', 'label' => 'Stores Directory', 'url' => route('farmer.stores.index')];
    }
  @endphp
<x-breadcrumb :items="[
  ['label' => $isStoreOwner ? 'My Products' : 'Browse Products', 'url' => $browseUrl],
  ['label' => $product->display_name],
]"/>

<div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">{{ $product->display_name }}</h1>
    <p class="keep-sub" style="color: #606d64; font-size: 14px;">Supplied by <strong>{{ $product->storeOwner?->name ?? 'Unknown Merchant' }}</strong></p>
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
    .badge-green { background: #edf1e9; color: #648268; }
    .badge-red { background: #fef2f2; color: #dc2626; }
    .badge-blue { background: #edf1e9; color: #648268; }
    .badge-amber { background: #edf1e9; color: #648268; }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .status-dot-green { background: #4A7A54; }
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
    .npk-p { background: #edf1e9; color: #648268; }
    .npk-k { background: #fefce8; color: #ca8a04; }
    .desc-text { color: #3d4a41; font-size: 14px; line-height: 1.8; margin: 0; }

    /* Recommended products list — shared catalog product cards. */
    .recommended-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 12px; }

    @media (max-width: 768px) {
        .detail-hero { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .recommended-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .product-image-panel { min-height: 220px; }
        .product-detail-container { padding: 16px; }
    }

    /* ── Product image gallery ─────────────────────────────────── */
    .gallery { display: flex; flex-direction: column; gap: 10px; }
    .gallery-main {
        position: relative;
        background: #f8fafc;
        border: 1px solid #e8edf2;
        border-radius: 12px;
        overflow: hidden;
    }
    .gallery-track {
        position: relative;
        display: flex;
        align-items: center;
        min-height: 240px;
        overflow-x: auto;
        overflow-y: hidden;
        overscroll-behavior-x: contain;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
        -ms-overflow-style: none;
        cursor: zoom-in;
    }
    .gallery-track::-webkit-scrollbar { display: none; }
    .gallery-track:focus-visible { outline: 2px solid #166534; outline-offset: -2px; }
    .gallery-track.is-scrollable { cursor: grab; }
    .gallery-track.is-scrollable.is-dragging {
        cursor: grabbing;
        scroll-snap-type: none;
    }
    .gallery-slide {
        flex: 0 0 100%;
        width: 100%;
        max-height: 380px;
        object-fit: contain;
        display: block;
        scroll-snap-align: center;
        user-select: none;
        -webkit-user-drag: none;
        pointer-events: none;
    }
    .gallery-empty {
        min-height: 240px;
        display: flex; align-items: center; justify-content: center;
    }
    .gallery-thumbs {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(64px, 1fr));
        gap: 8px;
    }
    .gallery-thumb {
        position: relative;
        aspect-ratio: 1 / 1;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        background: #f8fafc;
        cursor: pointer;
        padding: 0;
        transition: border-color 0.2s, transform 0.2s;
    }
    .gallery-thumb:hover { transform: translateY(-2px); border-color: #94a3b8; }
    .gallery-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .gallery-thumb.is-active { border-color: #166534; }
    .gallery-thumb.is-active::after {
        content: ''; position: absolute; inset: 0;
        box-shadow: inset 0 0 0 2px rgba(22, 101, 52, 0.25);
    }
    .gallery-count {
        position: absolute; bottom: 8px; right: 8px;
        font-size: 11px; font-weight: 700;
        background: rgba(15, 23, 42, 0.72); color: #fff;
        padding: 3px 8px; border-radius: 6px;
    }
    .gallery-expand {
        position: absolute; top: 8px; right: 8px;
        width: 28px; height: 28px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(15, 23, 42, 0.6); color: #fff;
        border: none; border-radius: 6px; cursor: pointer;
    }
    .gallery-expand:hover { background: rgba(15, 23, 42, 0.85); }
    .gallery-expand svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .gallery-nav {
        position: absolute; top: 50%; transform: translateY(-50%);
        z-index: 2;
        width: 34px; height: 34px;
        display: flex; align-items: center; justify-content: center;
        background: rgba(15, 23, 42, 0.55); color: #fff;
        border: none; border-radius: 50%; cursor: pointer;
        opacity: 0; transition: opacity 0.2s, background 0.2s;
    }
    .gallery-nav-prev { left: 10px; }
    .gallery-nav-next { right: 10px; }
    .gallery-main:hover .gallery-nav,
    .gallery-nav:focus-visible { opacity: 1; }
    .gallery-nav:hover:not(:disabled) { background: rgba(15, 23, 42, 0.82); }
    .gallery-nav:disabled { opacity: 0 !important; cursor: default; }
    .gallery-nav svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
    @media (hover: none) { .gallery-nav { opacity: 1; } }

    /* Lightbox */
    .gallery-lightbox {
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(15, 23, 42, 0.9);
        display: flex; align-items: center; justify-content: center;
        padding: 32px;
    }
    .gallery-lightbox[hidden] { display: none; }
    .gallery-lightbox img { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px; }
    .gallery-lightbox-close,
    .gallery-lightbox-prev,
    .gallery-lightbox-next {
        position: absolute;
        background: rgba(255, 255, 255, 0.14);
        color: #fff; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
    }
    .gallery-lightbox-close:hover,
    .gallery-lightbox-prev:hover,
    .gallery-lightbox-next:hover { background: rgba(255, 255, 255, 0.3); }
    .gallery-lightbox svg { stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .gallery-lightbox-close { top: 20px; right: 20px; width: 40px; height: 40px; }
    .gallery-lightbox-prev { left: 20px; top: 50%; transform: translateY(-50%); width: 44px; height: 44px; }
    .gallery-lightbox-next { right: 20px; top: 50%; transform: translateY(-50%); width: 44px; height: 44px; }
    .gallery-lightbox-close svg { width: 20px; height: 20px; }
    .gallery-lightbox-prev svg, .gallery-lightbox-next svg { width: 24px; height: 24px; }
    .gallery-lightbox-caption {
        position: absolute; bottom: 20px; left: 0; right: 0;
        text-align: center; color: #e2e8f0; font-size: 13px;
    }
</style>

<div class="product-detail-container">
    <!-- HERO: Image (left) + Main Details (right) -->
    <div class="detail-hero">
        <!-- LEFT: Product Image Gallery -->
        @php
            $gallery = $product->gallery;
            $gallerySources = $gallery->map(fn ($image) => $image->display_url)->values()->all();
        @endphp
        <div class="gallery" data-gallery data-gallery-sources="{{ json_encode($gallerySources) }}" data-gallery-name="{{ $product->display_name }}">
            <div class="gallery-main" data-gallery-main>
                @if($gallery->isNotEmpty())
                    <div class="gallery-track" data-gallery-track tabindex="0" role="group" aria-label="Product images — scroll left and right to browse">
                        @foreach($gallery as $index => $image)
                            <img class="gallery-slide"
                                 src="{{ $image->display_url }}"
                                 alt="{{ $product->display_name }}{{ $gallery->count() > 1 ? ' — image ' . ($index + 1) : '' }}"
                                 data-gallery-image
                                 data-gallery-index="{{ $index }}"
                                 draggable="false">
                        @endforeach
                    </div>
                    @if($gallery->count() > 1)
                        <span class="gallery-count" data-gallery-counter>1 / {{ $gallery->count() }}</span>
                        <button type="button" class="gallery-expand" data-gallery-zoom aria-label="View larger image">
                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                        </button>
                        <button type="button" class="gallery-nav gallery-nav-prev" data-gallery-step="-1" aria-label="Previous image">
                            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <button type="button" class="gallery-nav gallery-nav-next" data-gallery-step="1" aria-label="Next image">
                            <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    @endif
                @else
                    <div class="gallery-empty">
                        <div style="text-align: center;">
                            <svg viewBox="0 0 24 24" width="48" height="48" stroke="#cbd5e1" fill="none" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            <p style="margin-top: 8px; font-size: 12px;">Product Image</p>
                        </div>
                    </div>
                @endif
            </div>

            @if($gallery->count() > 1)
                <div class="gallery-thumbs" role="tablist" aria-label="Product images">
                    @foreach($gallery as $index => $image)
                        <button type="button"
                                class="gallery-thumb {{ $index === 0 ? 'is-active' : '' }}"
                                role="tab"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                data-gallery-thumb
                                data-gallery-index="{{ $index }}">
                            <img src="{{ $image->display_url }}" alt="{{ $product->display_name }} — image {{ $index + 1 }}" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif

            @if($gallery->isNotEmpty())
                <div class="gallery-lightbox" data-gallery-lightbox hidden role="dialog" aria-modal="true" aria-label="Product image viewer">
                    <button type="button" class="gallery-lightbox-close" data-gallery-close aria-label="Close viewer">
                        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                    @if($gallery->count() > 1)
                        <button type="button" class="gallery-lightbox-prev" data-gallery-prev aria-label="Previous image">
                            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                        </button>
                        <button type="button" class="gallery-lightbox-next" data-gallery-next aria-label="Next image">
                            <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                    @endif
                    <img alt="" data-gallery-lightbox-image>
                    <div class="gallery-lightbox-caption" data-gallery-lightbox-caption></div>
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
                    Generate Cost Quotation
                </a>
            </div>
            @endif

            <div class="desc-text" style="margin: 16px 0 14px; padding: 12px 14px; background: #f8faf9; border-radius: 10px; line-height: 1.6; color: #475569; font-size: 13px;">
                {{ $product->description ?: 'No description provided for this product.' }}
            </div>
            <div class="main-info-list">
                <div class="info-row">
                    <span class="info-label">Category</span>
                    <span class="info-value">{{ $product->category_display_name }}</span>
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
                        @if($product->recommended_season === 'wet')
                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="#2563eb" fill="none" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><path d="M20 17.58A5 5 0 0 0 18 8h-1.26A8 8 0 1 0 4 16.25"/><line x1="8" y1="16" x2="8" y2="20"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="16" y1="16" x2="16" y2="20"/></svg> Wet Season
                        @elseif($product->recommended_season === 'dry')
                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="#d97706" fill="none" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg> Dry Season
                        @else
                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="#87a992" fill="none" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg> All Seasons
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

    {{-- Category-specific specification sections --}}
    @php
        $topCategory = $product->top_category_group;
        $hasFormulas = $formulas->count() > 0;
        $fd = $product->fertilizerDetail;
    @endphp

    {{-- FERTILIZER: Composition + Application Rules (merged) --}}
    @if($topCategory === 'Fertilizer')
    <div class="spec-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            Fertilizer Composition
        </div>

        <div class="fertilizer-npk">
            @if($fd && $fd->nitrogen_percentage !== null)
            <div class="npk-pill npk-n">
                <div style="font-size: 11px; font-weight: 500; text-transform: uppercase;">N</div>
                {{ $fd->nitrogen_percentage }}%
            </div>
            @endif
            @if($fd && $fd->phosphorus_percentage !== null)
            <div class="npk-pill npk-p">
                <div style="font-size: 11px; font-weight: 500; text-transform: uppercase;">P</div>
                {{ $fd->phosphorus_percentage }}%
            </div>
            @endif
            @if($fd && $fd->potassium_percentage !== null)
            <div class="npk-pill npk-k">
                <div style="font-size: 11px; font-weight: 500; text-transform: uppercase;">K</div>
                {{ $fd->potassium_percentage }}%
            </div>
            @endif
        </div>

        <div class="spec-grid">
            <div class="info-row">
                <span class="info-label">Form Type</span>
                <span class="info-value">{{ $fd->form_type ?? 'N/A' }}</span>
            </div>
            @if($fd && $fd->application_rate_min)
            <div class="info-row">
                <span class="info-label">Application Rate (General)</span>
                <span class="info-value">
                    {{ $fd->application_rate_min }}@if($fd->application_rate_max) – {{ $fd->application_rate_max }}@endif
                    {{ $fd->application_rate_unit ?? '' }}
                </span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Application Method</span>
                <span class="info-value">{{ $fd->application_method ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Timing</span>
                <span class="info-value">{{ $fd->application_timing ?? 'N/A' }}</span>
            </div>
        </div>

        {{-- Application Rules (DecisionFormula) - crop/season/stage specific --}}
        <div class="spec-divider" style="margin: 20px 0 16px;"></div>
        <div class="section-title" style="margin-bottom: 12px;">
            <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Application Rules (per Crop, Season & Stage)
            @if($isStoreOwner)
                <a href="{{ route('store.products.edit', ['product' => $product, 'tab' => 'rules']) }}"
                   style="margin-left:auto;font-size:12px;font-weight:600;color:#14532d;text-transform:none;letter-spacing:0;text-decoration:underline;">
                    Edit rules
                </a>
            @endif
        </div>
        <div class="spec-grid">
            @forelse($formulas as $formula)
                <div class="info-row">
                    <span class="info-label">Crop & Season</span>
                    <span class="info-value">{{ $formula->crop_type }} · {{ ucfirst($formula->season) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Application Stage</span>
                    <span class="info-value">{{ $formula->stageLabel() }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Rate per Hectare</span>
                    <span class="info-value">
                        {{ rtrim(rtrim(number_format($formula->effectiveRate(), 2), '0'), '.') }} {{ $formula->unit }}
                        @if($formula->rateNeedsClamp())
                            <span style="color:#b45309;font-weight:500;font-size:12px;">
                                (configured {{ rtrim(rtrim(number_format((float) $formula->rate_per_hectare, 2), '0'), '.') }})
                            </span>
                        @endif
                    </span>
                </div>
                @if($formula->min_rate_per_hectare !== null || $formula->max_rate_per_hectare !== null)
                <div class="info-row">
                    <span class="info-label">Safety Bounds</span>
                    <span class="info-value">
                        {{ $formula->min_rate_per_hectare !== null ? rtrim(rtrim(number_format((float) $formula->min_rate_per_hectare, 2), '0'), '.') : '—' }}
                        –
                        {{ $formula->max_rate_per_hectare !== null ? rtrim(rtrim(number_format((float) $formula->max_rate_per_hectare, 2), '0'), '.') : '—' }}
                        {{ $formula->unit }}
                    </span>
                </div>
                @endif
                @if($formula->notes)
                <div class="info-row">
                    <span class="info-label">Notes</span>
                    <span class="info-value" style="font-size:12.5px;font-weight:500;">{{ $formula->notes }}</span>
                </div>
                @endif
            @empty
                <div class="info-row">
                    <span class="info-label">Crop & Season</span>
                    <span class="info-value">N/A</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Application Stage</span>
                    <span class="info-value">N/A</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Rate per Hectare</span>
                    <span class="info-value">N/A</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Safety Bounds</span>
                    <span class="info-value">N/A</span>
                </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- SEEDS: Seed-specific specifications --}}
    @if($topCategory === 'Seeds and Planting Materials' || $topCategory === 'Seeds')
    <div class="spec-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Seed Specifications
        </div>
        <div class="spec-grid">
            <div class="info-row">
                <span class="info-label">Seed Type</span>
                <span class="info-value">{{ $product->category_display_name }}</span>
            </div>
            @if($product->package_size)
            <div class="info-row">
                <span class="info-label">Package Size</span>
                <span class="info-value">{{ $product->package_size }} {{ $product->package_unit ?? 'kg' }}</span>
            </div>
            @endif
            @if($product->fertilizerDetail && $product->fertilizerDetail->application_rate_min)
            <div class="info-row">
                <span class="info-label">Planting Rate</span>
                <span class="info-value">
                    {{ $product->fertilizerDetail->application_rate_min }}@if($product->fertilizerDetail->application_rate_max) – {{ $product->fertilizerDetail->application_rate_max }}@endif
                    {{ $product->fertilizerDetail->application_rate_unit ?? 'kg/ha' }}
                </span>
            </div>
            @endif
            @if($hasFormulas)
            <div class="info-row">
                <span class="info-label">Planting Rules (per Crop/Season)</span>
                <span class="info-value">
                    @foreach($formulas as $formula)
                        {{ $formula->crop_type }} · {{ ucfirst($formula->season) }} · {{ $formula->stageLabel() }}: {{ rtrim(rtrim(number_format($formula->effectiveRate(), 2), '0'), '.') }} {{ $formula->unit }}<br>
                    @endforeach
                </span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- CROP PROTECTION: Pesticide/Fungicide/Herbicide specs --}}
    @if($topCategory === 'Crop Protection')
    <div class="spec-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Crop Protection Details
        </div>
        <div class="spec-grid">
            <div class="info-row">
                <span class="info-label">Protection Type</span>
                <span class="info-value">{{ $product->category_display_name }}</span>
            </div>
            @if($product->fertilizerDetail)
            @if($product->fertilizerDetail->form_type)
            <div class="info-row">
                <span class="info-label">Formulation Type</span>
                <span class="info-value">{{ $product->fertilizerDetail->form_type }}</span>
            </div>
            @endif
            @if($product->fertilizerDetail->application_rate_min)
            <div class="info-row">
                <span class="info-label">Application Rate</span>
                <span class="info-value">
                    {{ $product->fertilizerDetail->application_rate_min }}@if($product->fertilizerDetail->application_rate_max) – {{ $product->fertilizerDetail->application_rate_max }}@endif
                    {{ $product->fertilizerDetail->application_rate_unit ?? '' }}
                </span>
            </div>
            @endif
            @if($product->fertilizerDetail->application_method)
            <div class="info-row">
                <span class="info-label">Application Method</span>
                <span class="info-value">{{ $product->fertilizerDetail->application_method }}</span>
            </div>
            @endif
            @if($product->fertilizerDetail->application_timing)
            <div class="info-row">
                <span class="info-label">Timing / PHI</span>
                <span class="info-value">{{ $product->fertilizerDetail->application_timing }}</span>
            </div>
            @endif
            @endif
            @if($hasFormulas)
            <div class="info-row">
                <span class="info-label">Application Rules (per Crop/Season)</span>
                <span class="info-value">
                    @foreach($formulas as $formula)
                        {{ $formula->crop_type }} · {{ ucfirst($formula->season) }} · {{ $formula->stageLabel() }}: {{ rtrim(rtrim(number_format($formula->effectiveRate(), 2), '0'), '.') }} {{ $formula->unit }}<br>
                    @endforeach
                </span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- GENERIC: Fallback for other categories --}}
    @if(!in_array($topCategory, ['Fertilizer', 'Seeds and Planting Materials', 'Seeds', 'Crop Protection']))
    <div class="spec-section">
        <div class="section-title">
            <svg viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            Product Details
        </div>
        <div class="spec-grid">
            @if($product->fertilizerDetail)
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
            @if($product->fertilizerDetail->form_type)
            <div class="info-row">
                <span class="info-label">Form Type</span>
                <span class="info-value">{{ $product->fertilizerDetail->form_type }}</span>
            </div>
            @endif
            @if($product->fertilizerDetail->application_rate_min)
            <div class="info-row">
                <span class="info-label">Application Rate</span>
                <span class="info-value">
                    {{ $product->fertilizerDetail->application_rate_min }}@if($product->fertilizerDetail->application_rate_max) – {{ $product->fertilizerDetail->application_rate_max }}@endif
                    {{ $product->fertilizerDetail->application_rate_unit ?? '' }}
                </span>
            </div>
            @endif
            @if($product->fertilizerDetail->application_method)
            <div class="info-row">
                <span class="info-label">Application Method</span>
                <span class="info-value">{{ $product->fertilizerDetail->application_method }}</span>
            </div>
            @endif
            @if($product->fertilizerDetail->application_timing)
            <div class="info-row">
                <span class="info-label">Timing</span>
                <span class="info-value">{{ $product->fertilizerDetail->application_timing }}</span>
            </div>
            @endif
            @endif
            @if($hasFormulas)
            <div class="spec-divider" style="margin: 16px 0 12px;"></div>
            <div class="section-title" style="margin-bottom: 12px;">
                <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Application Rules
                @if($isStoreOwner)
                    <a href="{{ route('store.products.edit', ['product' => $product, 'tab' => 'rules']) }}"
                       style="margin-left:auto;font-size:12px;font-weight:600;color:#14532d;text-transform:none;letter-spacing:0;text-decoration:underline;">
                        Edit rules
                    </a>
                @endif
            </div>
            <div class="spec-grid">
                @forelse($formulas as $formula)
                    <div class="info-row">
                        <span class="info-label">Crop & Season</span>
                        <span class="info-value">{{ $formula->crop_type }} · {{ ucfirst($formula->season) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Application Stage</span>
                        <span class="info-value">{{ $formula->stageLabel() }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Rate per Hectare</span>
                        <span class="info-value">
                            {{ rtrim(rtrim(number_format($formula->effectiveRate(), 2), '0'), '.') }} {{ $formula->unit }}
                            @if($formula->rateNeedsClamp())
                                <span style="color:#b45309;font-weight:500;font-size:12px;">
                                    (configured {{ rtrim(rtrim(number_format((float) $formula->rate_per_hectare, 2), '0'), '.') }})
                                </span>
                            @endif
                        </span>
                    </div>
                    @if($formula->min_rate_per_hectare !== null || $formula->max_rate_per_hectare !== null)
                    <div class="info-row">
                        <span class="info-label">Safety Bounds</span>
                        <span class="info-value">
                            {{ $formula->min_rate_per_hectare !== null ? rtrim(rtrim(number_format((float) $formula->min_rate_per_hectare, 2), '0'), '.') : '—' }}
                            –
                            {{ $formula->max_rate_per_hectare !== null ? rtrim(rtrim(number_format((float) $formula->max_rate_per_hectare, 2), '0'), '.') : '—' }}
                            {{ $formula->unit }}
                        </span>
                    </div>
                    @endif
                    @if($formula->notes)
                    <div class="info-row">
                        <span class="info-label">Notes</span>
                        <span class="info-value" style="font-size:12.5px;font-weight:500;">{{ $formula->notes }}</span>
                    </div>
                    @endif
                @empty
                    <div class="info-row">
                        <span class="info-label">Crop & Season</span>
                        <span class="info-value">N/A</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Application Stage</span>
                        <span class="info-value">N/A</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Rate per Hectare</span>
                        <span class="info-value">N/A</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Safety Bounds</span>
                        <span class="info-value">N/A</span>
                    </div>
                @endforelse
            </div>
            @endif
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

</div>

<!-- Recommended Products -->
@if($relatedProducts->count() > 0)
<div style="margin-top: 32px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin-bottom: 16px;">Recommended Products in {{ $product->category_display_name }}</h2>
    <div class="recommended-grid">
        @foreach($relatedProducts as $related)
        <x-product-card
            :product="$related"
            href="{{ route(auth()->user()->role === 'store_owner' ? 'store.products.show' : 'farmer.products.show', $related->id) }}"
            image-height="150px" />
        @endforeach
    </div>
</div>
@endif

<script>
(function() {
  /* ── Product image gallery ── */
  var gallery = document.querySelector('[data-gallery]');
  if (gallery) {
    var track = gallery.querySelector('[data-gallery-track]');
    var counter = gallery.querySelector('[data-gallery-counter]');
    var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('[data-gallery-thumb]'));
    var slides = Array.prototype.slice.call(gallery.querySelectorAll('[data-gallery-image]'));
    var lightbox = gallery.querySelector('[data-gallery-lightbox]');
    var lightboxImage = gallery.querySelector('[data-gallery-lightbox-image]');
    var lightboxCaption = gallery.querySelector('[data-gallery-lightbox-caption]');
    var productName = gallery.dataset.galleryName || '';

    var sources = [];
    try {
      sources = JSON.parse(gallery.dataset.gallerySources || '[]');
    } catch (e) {
      sources = [];
    }
    // Fall back to the rendered slides if the data attribute was mangled, so the
    // gallery keeps working even when the JSON payload cannot be read.
    if (!Array.isArray(sources) || sources.length !== slides.length) {
      sources = slides.map(function(slide) { return slide.getAttribute('src') || ''; });
    }

    var total = slides.length;
    var current = 0;
    var prevStep = gallery.querySelector('[data-gallery-step="-1"]');
    var nextStep = gallery.querySelector('[data-gallery-step="1"]');

    if (track && total > 1) track.classList.add('is-scrollable');

    function sourceAt(index) {
      return sources[index] || '';
    }

    function paintLightbox() {
      if (!lightboxImage) return;
      lightboxImage.src = sourceAt(current);
      lightboxImage.alt = productName + ' — image ' + (current + 1) + ' of ' + total;
      if (lightboxCaption) lightboxCaption.textContent = (current + 1) + ' of ' + total;
    }

    function openLightbox() {
      if (!lightbox) return;
      paintLightbox();
      lightbox.hidden = false;
    }

    function closeLightbox() {
      if (!lightbox) return;
      lightbox.hidden = true;
    }

    function paint(index) {
      if (total === 0) return;
      current = ((index % total) + total) % total;

      if (counter) counter.textContent = (current + 1) + ' / ' + total;

      thumbs.forEach(function(thumb, i) {
        var active = i === current;
        thumb.classList.toggle('is-active', active);
        thumb.setAttribute('aria-selected', active ? 'true' : 'false');
      });

      if (prevStep) prevStep.disabled = current === 0;
      if (nextStep) nextStep.disabled = current === total - 1;
    }

    // Scroll the big image track so the requested slide is snapped into view.
    function slideTo(index) {
      var slide = slides[index];
      if (!track || !slide) return;
      track.scrollTo({
        left: slide.offsetLeft - (track.clientWidth - slide.offsetWidth) / 2,
        behavior: 'smooth'
      });
    }

    function go(index, moveTrack) {
      if (total === 0) return;
      paint(index);
      if (moveTrack !== false) slideTo(current);
    }

    // Keep the counter + active thumbnail in sync while the track is scrolled
    // (mouse wheel, trackpad, drag, touch swipe or native scrollbar).
    var scrollFrame = null;
    if (track) {
      track.addEventListener('scroll', function() {
        if (scrollFrame) return;
        scrollFrame = requestAnimationFrame(function() {
          scrollFrame = null;
          var mid = track.scrollLeft + track.clientWidth / 2;
          var nearest = 0;
          var nearestDistance = Infinity;
          slides.forEach(function(slide, i) {
            var distance = Math.abs((slide.offsetLeft + slide.offsetWidth / 2) - mid);
            if (distance < nearestDistance) { nearestDistance = distance; nearest = i; }
          });
          if (nearest !== current) paint(nearest);
        });
      }, { passive: true });
    }

    thumbs.forEach(function(thumb, i) {
      thumb.addEventListener('click', function() { go(i); });
    });

    [prevStep, nextStep].forEach(function(btn) {
      if (!btn) return;
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var step = parseInt(btn.dataset.galleryStep, 10) || 0;
        go(current + step);
      });
    });

    var zoomBtn = gallery.querySelector('[data-gallery-zoom]');
    if (zoomBtn) zoomBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      openLightbox();
    });

    if (track) {
      var dragPointer = null;
      var dragStartX = 0;
      var dragStartScroll = 0;
      var dragged = false;

      track.addEventListener('pointerdown', function(e) {
        if (e.pointerType === 'touch' || e.button !== 0) return;
        dragged = false;
        dragPointer = e.pointerId;
        dragStartX = e.clientX;
        dragStartScroll = track.scrollLeft;
        if (track.setPointerCapture) {
          try { track.setPointerCapture(e.pointerId); } catch (err) {}
        }
      });

      track.addEventListener('pointermove', function(e) {
        if (dragPointer === null || e.pointerId !== dragPointer) return;
        var delta = e.clientX - dragStartX;
        if (!dragged && Math.abs(delta) > 4) {
          dragged = true;
          track.classList.add('is-dragging');
        }
        if (!dragged) return;
        track.scrollLeft = dragStartScroll - delta;
        if (e.cancelable) e.preventDefault();
      });

      function endDrag(e) {
        if (dragPointer === null || e.pointerId !== dragPointer) return;
        dragPointer = null;
        track.classList.remove('is-dragging');
        if (track.releasePointerCapture) {
          try { track.releasePointerCapture(e.pointerId); } catch (err) {}
        }
      }

      track.addEventListener('pointerup', endDrag);
      track.addEventListener('pointercancel', endDrag);

      // A plain click zooms; the click that ends a drag must not.
      track.addEventListener('click', function(e) {
        if (dragged) { dragged = false; return; }
        if (e.target.closest('[data-gallery-zoom], .gallery-nav')) return;
        openLightbox();
      });

      track.addEventListener('keydown', function(e) {
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        go(current + (e.key === 'ArrowLeft' ? -1 : 1));
      });
    }

    if (lightbox && lightboxImage) {
      paintLightbox();

      var closeBtn = gallery.querySelector('[data-gallery-close]');
      if (closeBtn) closeBtn.addEventListener('click', closeLightbox);

      var prevBtn = gallery.querySelector('[data-gallery-prev]');
      if (prevBtn) {
        prevBtn.addEventListener('click', function() { go(current - 1, false); paintLightbox(); });
      }

      var nextBtn = gallery.querySelector('[data-gallery-next]');
      if (nextBtn) {
        nextBtn.addEventListener('click', function() { go(current + 1, false); paintLightbox(); });
      }

      // Swipe left/right through the images while the viewer is open.
      var swipePointer = null;
      var swipeStartX = 0;
      var swiped = false;

      lightbox.addEventListener('click', function(e) {
        if (swiped) { swiped = false; return; }
        if (e.target === lightbox) closeLightbox();
      });

      lightbox.addEventListener('pointerdown', function(e) {
        if (e.target.closest('button')) return;
        swipePointer = e.pointerId;
        swipeStartX = e.clientX;
      });

      lightbox.addEventListener('pointerup', function(e) {
        if (swipePointer === null || e.pointerId !== swipePointer) return;
        var delta = e.clientX - swipeStartX;
        swipePointer = null;
        if (Math.abs(delta) > 50) {
          swiped = true;
          go(current + (delta < 0 ? 1 : -1), false);
          paintLightbox();
        }
      });

      lightbox.addEventListener('pointercancel', function() { swipePointer = null; });

      document.addEventListener('keydown', function(e) {
        if (lightbox.hidden) return;
        if (e.key === 'Escape') { closeLightbox(); return; }
        if (e.key === 'ArrowLeft') { go(current - 1, false); paintLightbox(); }
        if (e.key === 'ArrowRight') { go(current + 1, false); paintLightbox(); }
      });
    }

    // Arrow-key navigation on the thumbnail row.
    gallery.addEventListener('keydown', function(e) {
      if (lightbox && !lightbox.hidden) return;
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      if (!document.activeElement || !document.activeElement.classList.contains('gallery-thumb')) return;
      e.preventDefault();
      go(current + (e.key === 'ArrowLeft' ? -1 : 1));
    });

    paint(0);
  }
})();
</script>
@endsection

