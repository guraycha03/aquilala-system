@extends('layouts.sidebar')

@section('title', $product->display_name)

@section('content')
  @include('partials.admin-tabs', [
    'tabs' => [
      ['key' => 'browse',  'label' => 'Browse Products',    'url' => route('farmer.products.browse')],
      ['key' => 'reviews', 'label' => 'Top Rated Products', 'url' => route('farmer.reviews.index')],
      ['key' => 'stores',  'label' => 'Stores Directory',   'url' => route('farmer.stores.index')],
    ],
    'activeTab' => 'browse',
  ])
<x-breadcrumb :items="[
  ['label' => 'Browse Products', 'url' => route('farmer.products.browse')],
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

    /* ── Ratings & Reviews ─────────────────────────────────── */
    .rating-hero { display: flex; align-items: center; gap: 10px; margin-top: 14px; flex-wrap: wrap; }
    .rating-hero .stars { display: inline-flex; gap: 2px; }
    .rating-hero .stars svg { width: 18px; height: 18px; fill: currentColor; stroke: none; }
    .rating-hero .stars .star-empty { fill: #e2e8f0; }
    .rating-hero .stars .star-filled { fill: #f59e0b; }
    .rating-hero .avg { font-size: 18px; font-weight: 800; color: #143d23; }
    .rating-hero .count { font-size: 13px; color: #64748b; }
    .rating-hero .no-rating { font-size: 13px; color: #94a3b8; }

    .reviews-section { margin-top: 32px; }
    .reviews-heading { font-size: 16px; font-weight: 700; color: #1e2922; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .reviews-heading svg { width: 18px; height: 18px; stroke: #f59e0b; fill: #f59e0b; }

    .reviews-container {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        padding: 24px;
    }

    /* Summary box */
    .review-summary { display: grid; grid-template-columns: auto 1fr; gap: 24px; padding-bottom: 20px; border-bottom: 1px solid #eef2f6; margin-bottom: 20px; align-items: center; }
    .summary-score { text-align: center; }
    .summary-score .big { font-size: 40px; font-weight: 800; color: #143d23; line-height: 1; }
    .summary-score .stars { display: inline-flex; gap: 2px; margin: 6px 0; }
    .summary-score .stars svg { width: 18px; height: 18px; fill: currentColor; stroke: none; }
    .summary-score .stars .star-empty { fill: #e2e8f0; }
    .summary-score .stars .star-filled { fill: #f59e0b; }
    .summary-score .meta { font-size: 12px; color: #64748b; }
    .summary-bars { display: flex; flex-direction: column; gap: 6px; max-width: 380px; }
    .bar-row { display: flex; align-items: center; gap: 10px; font-size: 12px; color: #64748b; }
    .bar-row .bar-label { width: 30px; text-align: right; font-weight: 600; flex-shrink: 0; }
    .bar-row .bar-track { flex: 1; height: 8px; background: #eef2f6; border-radius: 99px; overflow: hidden; }
    .bar-row .bar-fill { height: 100%; background: #f59e0b; border-radius: 99px; }

    /* Review form */
    .review-form { background: #fbfcfd; border: 1px solid #eef2f6; border-radius: 10px; padding: 18px; margin-bottom: 22px; }
    .review-form .form-title { font-size: 14px; font-weight: 700; color: #1e2922; margin-bottom: 12px; }
    .star-picker { display: inline-flex; gap: 4px; margin-bottom: 12px; }
    .star-picker .sp-btn {
        background: none; border: none; cursor: pointer; padding: 2px;
        color: #d1d5db; transition: color 0.15s; line-height: 0;
    }
    .star-picker .sp-btn svg { width: 26px; height: 26px; fill: currentColor; stroke: none; }
    .star-picker .sp-btn.active { color: #f59e0b; }
    .star-picker .sp-btn:hover { color: #fbbf24; }
    .star-picker .sp-btn:focus-visible { outline: 2px solid #16a34a; outline-offset: 2px; border-radius: 4px; }
    .star-hint { font-size: 12px; color: #64748b; margin-bottom: 12px; display: block; }
    .review-form textarea {
        width: 100%; min-height: 80px; resize: vertical;
        border: 1px solid #cbd5e1; border-radius: 10px;
        padding: 10px 12px; font-size: 14px; font-family: inherit; color: #1e2922;
        outline: none; transition: border-color 0.2s, box-shadow 0.2s; background: #ffffff;
    }
    .review-form textarea:focus { border-color: #143d23; box-shadow: 0 0 0 3px rgba(20,61,35,0.1); }
    .review-form textarea::placeholder { color: #94a3b8; }
    .review-submit {
        display: inline-flex; align-items: center; gap: 8px;
        background: #143d23; color: #fff;
        border: none; border-radius: 8px;
        padding: 10px 22px; font-size: 14px; font-weight: 700; cursor: pointer;
        transition: background 0.2s; margin-top: 10px;
    }
    .review-submit:hover { background: #1a5a30; }
    .review-submit:disabled { opacity: 0.5; cursor: not-allowed; }
    .form-error { color: #dc2626; font-size: 12px; margin-top: 8px; display: none; }
    .form-error.visible { display: block; }

    /* My review card */
    .my-review { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px 16px; margin-bottom: 22px; }
    .my-review .mr-title { font-size: 13px; font-weight: 700; color: #166534; display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
    .mr-status { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; padding: 3px 8px; border-radius: 6px; }
    .mr-status-pending { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .mr-status-rejected { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .my-review .stars { display: inline-flex; gap: 2px; vertical-align: middle; }
    .my-review .stars svg { width: 15px; height: 15px; fill: #f59e0b; stroke: none; }
    .btn-ghost-danger {
        display: inline-flex; align-items: center; gap: 6px;
        background: transparent; color: #b0403a;
        border: 1px solid #e4c6c3; border-radius: 8px;
        padding: 7px 12px; font-size: 12px; font-weight: 700; cursor: pointer;
        transition: background 0.2s;
    }
    .btn-ghost-danger:hover { background: #faf2f1; }
    .btn-ghost-danger svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    /* Individual reviews */
    .review-list { display: flex; flex-direction: column; }
    .review-item { padding: 16px 0; border-bottom: 1px solid #f1f5f4; }
    .review-item:last-child { border-bottom: none; padding-bottom: 6px; }
    .review-item.hidden-item { display: none; }
    .review-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; flex-wrap: wrap; }
    .review-author { display: flex; align-items: center; gap: 10px; }
    .avatar {
        width: 36px; height: 36px; border-radius: 50%;
        background: #143d23; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 14px; flex-shrink: 0;
    }
    .review-name { font-size: 14px; font-weight: 700; color: #1e2922; }
    .review-date { font-size: 12px; color: #94a3b8; margin-top: 1px; }
    .review-stars { display: inline-flex; gap: 2px; }
    .review-stars svg { width: 14px; height: 14px; fill: currentColor; stroke: none; }
    .review-stars .star-empty { fill: #e2e8f0; }
    .review-stars .star-filled { fill: #f59e0b; }
    .review-comment { font-size: 14px; color: #3d4a41; line-height: 1.7; }
    .review-comment.truncated { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .toggle-comment {
        background: none; border: none; color: #16a34a;
        font-size: 12px; font-weight: 700; cursor: pointer; padding: 4px 0; margin-top: 4px;
    }
    .toggle-comment:hover { text-decoration: underline; }

    .no-reviews { text-align: center; color: #64748b; font-size: 14px; padding: 24px 0 8px; }

    .see-more-btn {
        display: block; width: 100%;
        margin-top: 16px; padding: 11px;
        background: #f8fafc; color: #143d23;
        border: 1px solid #e2e8f0; border-radius: 10px;
        font-size: 14px; font-weight: 700; cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
    }
    .see-more-btn:hover { background: #f0fdf4; border-color: #bbf7d0; }

    @media (max-width: 640px) {
        .reviews-container { padding: 16px; }
        .review-summary { grid-template-columns: 1fr; gap: 14px; text-align: center; }
        .summary-bars { max-width: 100%; }
    }
</style>

<div class="product-detail-container">
    <!-- HERO: Image (left) + Main Details (right) -->
    <div class="detail-hero">
        <!-- LEFT: Product Image Gallery -->
        @php
            $gallery = $product->gallery;
            $gallerySources = $gallery->map(fn ($image) => asset('storage/' . $image->file_path))->values()->all();
        @endphp
        <div class="gallery" data-gallery data-gallery-sources="{{ json_encode($gallerySources) }}" data-gallery-name="{{ $product->display_name }}">
            <div class="gallery-main" data-gallery-main>
                @if($gallery->isNotEmpty())
                    <div class="gallery-track" data-gallery-track tabindex="0" role="group" aria-label="Product images — scroll left and right to browse">
                        @foreach($gallery as $index => $image)
                            <img class="gallery-slide"
                                 src="{{ asset('storage/' . $image->file_path) }}"
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
                            <img src="{{ asset('storage/' . $image->file_path) }}" alt="{{ $product->display_name }} — image {{ $index + 1 }}" loading="lazy">
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

            <div class="rating-hero">
                @if($product->review_count > 0)
                    <span class="stars">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="{{ $i <= (int) round($product->average_rating) ? 'star-filled' : 'star-empty' }}" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        @endfor
                    </span>
                    <span class="avg">{{ number_format($product->average_rating, 1) }}</span>
                    <span class="count">{{ $product->review_count }} {{ $product->review_count === 1 ? 'farmer review' : 'farmer reviews' }}</span>
                @else
                    <span class="no-rating">No farmer reviews yet — be the first to review this product.</span>
                @endif
            </div>

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

<!-- Farmer Reviews -->
<div class="reviews-section" id="reviews">
    <h2 class="reviews-heading">
        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        Farmer Reviews
    </h2>

    <div class="reviews-container">
        @if($product->review_count > 0)
        <!-- Summary -->
        <div class="review-summary">
            <div class="summary-score">
                <div class="big">{{ number_format($product->average_rating, 1) }}</div>
                <div class="stars">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="{{ $i <= (int) round($product->average_rating) ? 'star-filled' : 'star-empty' }}" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <div class="meta">{{ $product->review_count }} {{ $product->review_count === 1 ? 'rating' : 'ratings' }}</div>
            </div>
            <div class="summary-bars">
                @foreach($product->rating_bars as $star => $count)
                <div class="bar-row">
                    <span class="bar-label">{{ $star }}★</span>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: {{ $product->review_count > 0 ? round(($count / $product->review_count) * 100) : 0 }}%;"></div>
                    </div>
                    <span style="min-width: 24px;">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(auth()->user()->role === 'farmer' && $myReview)
            <!-- My existing review -->
            <div class="my-review">
                <div>
                    <div class="mr-title">Your Review
                        @if($myReview->status === 'pending')
                            <span class="mr-status mr-status-pending">
                                <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                Pending approval
                            </span>
                        @elseif($myReview->status === 'rejected')
                            <span class="mr-status mr-status-rejected">
                                <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                Not approved — remove and resubmit
                            </span>
                        @endif
                    </div>
                    <div style="margin-top: 4px;">
                        <span class="stars">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="{{ $i <= $myReview->rating ? 'star-filled' : 'star-empty' }}" viewBox="0 0 24 24" style="fill: {{ $i <= $myReview->rating ? '#f59e0b' : '#e2e8f0' }};"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            @endfor
                        </span>
                        @if($myReview->comment)
                            <span style="font-size: 13px; color: #166534; margin-left: 8px;">{{ \Illuminate\Support\Str::limit($myReview->comment, 120) }}</span>
                        @endif
                    </div>
                </div>
                <form method="POST" action="{{ route('farmer.reviews.destroy', $myReview) }}" data-aqui-confirm="Remove your review for this product?" data-aqui-danger data-aqui-confirm-label="Remove">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-ghost-danger">
                        <svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Remove
                    </button>
                </form>
            </div>
        @endif

        <!-- Review list -->
        @if($product->review_count > 0)
            <div class="review-list" id="reviewList">
                @foreach($product->approvedReviews->sortByDesc('created_at') as $index => $review)
                <div class="review-item {{ $index >= 3 ? 'hidden-item' : '' }}">
                    <div class="review-header">
                        <div class="review-author">
                            <div class="avatar">
                                {{ strtoupper(substr($review->farmer?->name ?? 'F', 0, 1)) }}
                            </div>
                            <div>
                                <div class="review-name">{{ $review->farmer?->name ?? 'Anonymous Farmer' }}</div>
                                <div class="review-date">{{ $review->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>
                        <div class="review-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="{{ $i <= $review->rating ? 'star-filled' : 'star-empty' }}" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            @endfor
                        </div>
                    </div>
                    @if($review->comment)
                        <p class="review-comment" id="comment-{{ $review->id }}">{{ $review->comment }}</p>
                    @else
                        <p class="review-comment" style="color: #94a3b8; font-style: italic;">This farmer rated the product without leaving a comment.</p>
                    @endif
                </div>
                @endforeach
            </div>

            @if($product->review_count > 3)
            <button type="button" id="seeMoreBtn" class="see-more-btn" data-expanded="false">
                See more reviews ({{ $product->review_count - 3 }} more)
            </button>
            @endif
        @else
            <div class="no-reviews">No farmer reviews for this product yet.</div>
        @endif
    </div>
</div>

<!-- Review submission card (only for farmers who have not reviewed yet) -->
@if(auth()->user()->role === 'farmer' && !$myReview)
<div style="margin-top: 16px;">
    <div class="reviews-container" style="padding: 20px;">
        <div class="review-form" style="margin-bottom: 0;">
            <div class="form-title">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="#f59e0b" fill="#f59e0b" style="vertical-align: middle; margin-right: 4px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Rate this product
            </div>
            <form method="POST" action="{{ route('farmer.products.review.store', $product) }}" id="reviewForm">
                @csrf
                <input type="hidden" name="rating" id="ratingInput" value="">

                <div class="star-picker" id="starPicker">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" class="sp-btn" data-star="{{ $i }}" aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}">
                            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </button>
                    @endfor
                </div>
                <span class="star-hint" id="starHint">Tap a star to rate this product.</span>

                <textarea name="comment" id="reviewComment" placeholder="Optional: share your experience — quality, performance on your farm, value for money, and whether you would buy again."></textarea>

                <div style="font-size: 12px; color: #94a3b8; margin-bottom: 12px;">Your review is submitted for admin approval and will appear here once approved.</div>

                <div class="form-error" id="formError">Please select a star rating before submitting.</div>

                <button type="submit" class="review-submit">
                    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 2 11 13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Submit Review
                </button>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Recommended Products -->
@if($relatedProducts->count() > 0)
<div style="margin-top: 32px;">
        <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin-bottom: 16px;">Recommended Products in {{ $product->category_display_name }}</h2>
    <div class="recommended-grid">
        @foreach($relatedProducts as $related)
        <a href="{{ route(auth()->user()->role === 'store_owner' ? 'store.products.show' : 'farmer.products.show', $related->id) }}" style="text-decoration: none; color: inherit;">
            <div class="related-card">
                <h4>{{ $related->display_name }}</h4>
                <div class="related-price">₱{{ number_format($related->price, 2) }}</div>
                <div style="font-size: 12px; color: #87a992; margin-top: 4px;">
                    <span class="badge {{ $related->is_low_stock ? 'badge-red' : 'badge-green' }}" style="font-size: 10px;">
                        @if($related->is_low_stock)
                            <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" fill="none" stroke-width="2" style="vertical-align: middle;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Low Stock
                        @else
                            <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" fill="none" stroke-width="2" style="vertical-align: middle;"><polyline points="20 6 9 17 4 12"/></svg> In Stock
                        @endif
                    </span>
                    {{ $related->stock_quantity }} {{ $related->stock_unit ?? 'pieces' }}
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

<script>
(function() {
  /* ── Star picker for the review form ── */
  var picker = document.getElementById('starPicker');
  var ratingInput = document.getElementById('ratingInput');
  var starHint = document.getElementById('starHint');
  var formError = document.getElementById('formError');
  var reviewForm = document.getElementById('reviewForm');
  var selectedRating = 0;

  if (picker) {
    var hints = ['', 'Poor quality', 'Below average', 'Average', 'Good', 'Excellent'];

    function paintStars(active) {
      picker.querySelectorAll('.sp-btn').forEach(function(btn) {
        var star = parseInt(btn.dataset.star, 10);
        btn.classList.toggle('active', star <= active);
      });
    }

    picker.querySelectorAll('.sp-btn').forEach(function(btn) {
      btn.addEventListener('mouseenter', function() {
        if (!selectedRating) paintStars(parseInt(btn.dataset.star, 10));
      });
      btn.addEventListener('mouseleave', function() {
        if (!selectedRating) paintStars(0);
      });
      btn.addEventListener('click', function() {
        selectedRating = parseInt(btn.dataset.star, 10);
        ratingInput.value = selectedRating;
        paintStars(selectedRating);
        starHint.textContent = hints[selectedRating];
        if (formError) formError.classList.remove('visible');
      });
    });

    reviewForm.addEventListener('submit', function(e) {
      if (!selectedRating) {
        e.preventDefault();
        if (formError) formError.classList.add('visible');
      }
    });
  }

  /* ── "See more reviews" toggle ── */
  var seeMoreBtn = document.getElementById('seeMoreBtn');
  if (seeMoreBtn) {
    var collapsibleReviews = document.querySelectorAll('.review-item.hidden-item');
    var collapsedCount = collapsibleReviews.length;
    seeMoreBtn.addEventListener('click', function() {
      var expanded = seeMoreBtn.dataset.expanded === 'true';
      collapsibleReviews.forEach(function(item) {
        item.classList.toggle('hidden-item', expanded);
      });
      seeMoreBtn.dataset.expanded = String(!expanded);
      seeMoreBtn.textContent = expanded
        ? 'See more reviews (' + collapsedCount + ' more)'
        : 'See fewer reviews';
    });
  }

  /* ── "Read more" for long review comments ── */
  var MIN_COMMENT_CHARS = 160;
  document.querySelectorAll('.review-comment').forEach(function(el) {
    var text = (el.textContent || '').trim();
    if (text.length > MIN_COMMENT_CHARS && el.parentNode) {
      el.classList.add('truncated');
      var toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'toggle-comment';
      toggle.textContent = 'See more';
      toggle.addEventListener('click', function() {
        var expanded = el.classList.toggle('truncated');
        toggle.textContent = expanded ? 'See more' : 'See less';
      });
      el.after(toggle);
    }
  });

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

