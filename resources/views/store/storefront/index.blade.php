@extends('layouts.sidebar')

@section('title', 'My Store')

@section('content')

<style>
    /* Global Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    
    .page-title {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px 0;
        letter-spacing: -0.02em;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }

    /* Action Buttons */
    .btn-primary {
        background-color: #14532d;
        color: #ffffff;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
        border: none;
    }

    .btn-primary:hover {
        background-color: #166534;
        box-shadow: 0 4px 12px rgba(20, 83, 45, 0.15);
        transform: translateY(-1px);
    }

    .btn-ghost {
        background-color: #ffffff;
        color: #14532d;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #cbd5c1;
        transition: all 0.2s ease;
    }

    .btn-ghost:hover {
        background-color: #f7faf5;
        border-color: #14532d;
    }

    .page-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    /* Alerts */
    .alert-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 24px;
        color: #15803d;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Tab Switcher Styling */
    .catalog-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 24px;
    }

    .tab-btn {
        background: none;
        border: none;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s ease;
    }

    .tab-btn:hover {
        color: #0f172a;
    }

    .tab-btn.active {
        color: #14532d;
        border-bottom-color: #14532d;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    /* Catalog Toolbar — search + styled filters (farm activity page style) */
    .catalog-toolbar {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 20px; flex-wrap: wrap;
    }
    .catalog-toolbar .catalog-search { flex: 1; min-width: 260px; margin-bottom: 0; }

    .catalog-search {
        position: relative;
    }

    .catalog-search > svg {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        width: 17px; height: 17px; stroke: #94a3b8;
    }

    .catalog-search input {
        width: 100%; height: 42px; padding: 0 16px 0 40px;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; color: #0f172a; outline: none; background: #f8fafc;
        font-weight: 500; font-family: inherit; box-sizing: border-box;
        transition: all .2s ease;
    }
    .catalog-search.has-count input { padding-right: 110px; }

    .catalog-search input:focus {
        border-color: #16a34a; background: #ffffff;
        box-shadow: 0 0 0 3px rgba(22,163,74,.08);
    }

    .catalog-search input::placeholder { color: #94a3b8; }

    .catalog-count {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        font-size: 12px; font-weight: 600; color: #64748b;
        background: #f1f5f9; padding: 4px 10px; border-radius: 20px;
        visibility: hidden;
    }
    .catalog-count.show { visibility: visible; }

    /* ncb-select dropdown styles come from the shared rules in
       layouts.sidebar (the filter choices live in the shared filter-panel). */

    /* Filter clear button */
    .filter-clear {
        display: inline-flex; align-items: center; gap: 6px;
        height: 42px; padding: 0 14px; border: 1.5px solid #16a34a; border-radius: 10px;
        background: #16a34a; color: #ffffff; font-size: 13px; font-weight: 700;
        cursor: pointer; font-family: inherit; transition: all 0.2s ease;
        box-shadow: 0 1px 2px rgba(22, 163, 74, 0.2);
    }
    .filter-clear .ic-clear { display: inline-flex; }
    .filter-clear .ic-search { display: none; }
    .filter-clear.is-search .ic-clear { display: none; }
    .filter-clear.is-search .ic-search { display: inline-flex; }
    .filter-clear:hover { border-color: #15803d; color: #ffffff; background: #15803d; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); }
    .filter-clear:active { transform: scale(0.98); }
    .filter-clear[hidden] { display: none; }

    .card-hide { display: none !important; }
    .search-no-results {
        display: none; text-align: center; padding: 54px 20px;
        background: #ffffff; border: 2px dashed #e2e8f0; border-radius: 16px;
        color: #64748b;
    }
    .search-no-results.show { display: block; }
    .search-no-results svg { width: 48px; height: 48px; stroke: #94a3b8; margin-bottom: 14px; }
    .search-no-results h3 { font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 6px 0; }
    .search-no-results p { font-size: 14px; margin: 0; line-height: 1.6; }

    /* Preview Banner */
    .preview-banner {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        border-radius: 10px;
        padding: 12px 18px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Product Grid & Cards */
    .product-grid { 
        display: grid; 
        grid-template-columns: repeat(4, 1fr); 
        gap: 16px; 
    }
    @media (max-width: 1200px) {
        .product-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 900px) {
        .product-grid { grid-template-columns: repeat(2, 1fr); }
    }

    .product-card { 
        background: #ffffff; 
        border: 1px solid #e2e8f0; 
        border-radius: 14px; 
        overflow: hidden; 
        display: flex; 
        flex-direction: column; 
        transition: all 0.25s ease;
    }

    .product-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
        transform: translateY(-3px);
    }

    .product-image-container {
        background: #f8fafc; 
        height: 190px; 
        width: 100%;
        display: flex; 
        align-items: center; 
        justify-content: center; 
        border-bottom: 1px solid #f1f5f9;
        position: relative;
        overflow: hidden;
    }

    .product-card-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .product-card:hover .product-card-img {
        transform: scale(1.03);
    }

    .product-details { 
        padding: 20px; 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
    }

    .category-chip {
        font-size: 11px; 
        color: #0f766e; 
        background: #ccfbf1;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase; 
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    .product-title {
        font-size: 16px; 
        color: #0f172a; 
        font-weight: 700; 
        margin: 10px 0 6px 0;
        line-height: 1.3;
    }

    .product-description {
        color: #64748b; 
        font-size: 13px; 
        margin: 0 0 16px 0; 
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Badges */
    .badge-group {
        display: flex; 
        align-items: center; 
        justify-content: space-between;
        gap: 8px; 
        margin-bottom: 16px;
        flex-wrap: nowrap;
    }

    /* The season and stock badges used to live here. The shared product card
       now draws the season itself (as an icon) and stock belongs on the
       product detail page, so only the layout wrapper is left. */

    /* Footer / Pricing Section */
    .card-footer {
        margin-top: auto;
        padding-top: 14px;
        border-top: 1px dashed #e2e8f0;
    }

    .price-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .price-tag { 
        font-size: 20px; 
        font-weight: 800; 
        color: #0f172a; 
        letter-spacing: -0.01em;
    }

    /* Actions */
    .action-group {
        display: flex; 
        gap: 8px; 
    }

    .btn-action { 
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        background: #f8fafc; 
        color: #334155; 
        border: 1px solid #cbd5e1; 
        padding: 8px 12px; 
        border-radius: 8px; 
        font-size: 13px; 
        font-weight: 600; 
        cursor: pointer; 
        text-decoration: none; 
        transition: all 0.2s; 
    }

    .btn-action:hover { 
        background: #f1f5f9; 
        color: #0f172a;
    }

    .btn-delete { 
        background: #fff5f5; 
        color: #e11d48; 
        border-color: #fecdd3; 
    }

    .btn-delete:hover { 
        background: #ffe4e6; 
        color: #be123c;
    }

/* Empty State */
    .empty-state { 
        text-align: center; 
        padding: 80px 20px; 
        background: #ffffff;
        border: 2px dashed #e2e8f0;
        border-radius: 16px;
    }

    .empty-state svg { 
        width: 56px; 
        height: 56px; 
        stroke: #94a3b8; 
        margin-bottom: 16px; 
    }

    .empty-state h3 { 
        font-size: 18px; 
        font-weight: 700;
        color: #1e293b; 
        margin: 0 0 6px 0; 
    }

    .empty-state p { 
        font-size: 14px; 
        color: #64748b;
        max-width: 440px;
        margin: 0 auto;
        line-height: 1.6; 
    }

    @media (max-width: 640px) {
        .product-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }
        .product-details { padding: 14px; }
        .product-title { font-size: 14px; }
        .product-description { font-size: 12px; }
        .card-footer { padding-top: 12px; }
    }
</style>

<!-- Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Retail Product Catalog</h1>
        <p class="page-subtitle">Manage items displayed in the public marketplace browse view for association farmers.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('store.products.trash') }}" class="btn-ghost" title="Archived products you can restore later">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="5" rx="1"></rect><path d="M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8"></path></svg>
            Archive
            @if(($archivedCount ?? 0) > 0)
                <span style="background:#fee2e2;color:#b91c1c;border-radius:999px;padding:1px 8px;font-size:11px;font-weight:700;">{{ $archivedCount }}</span>
            @endif
        </a>
        <a href="{{ route('store.products.import') }}" class="btn-ghost">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M17 8l-5-5-5 5"></path><path d="M12 3v12"></path></svg>
            Import from File
        </a>
        <a href="{{ route('store.products.create') }}" class="btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Product Profile
        </a>
    </div>
</div>

<!-- Success Message -->
@if (session('success'))
<div class="alert-success">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
    <span>{{ session('success') }}</span>
</div>
@endif

<!-- Search + shared filter-collapse (choices hide behind the funnel button) -->
<div class="catalog-toolbar" data-fc>
    <div class="catalog-search">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="catalogSearch" placeholder="Search by name, SKU, category, season, stock, or price..." autocomplete="off">
        <span class="catalog-count" id="catalogCount"></span>
    </div>

    <button type="button" class="btn-filter" data-fc-toggle aria-expanded="false"
            aria-label="Show filter options" title="Filters">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span class="btn-filter-dot" data-fc-dot hidden></span>
    </button>

    <div class="filter-panel" data-fc-panel hidden>

    <div class="ncb-group-inline" data-filter-key="cat">
        <button type="button" class="ncb-select-trigger placeholder" id="catFilterTrigger" data-default-label="Category" tabindex="0" aria-haspopup="listbox">
            <span class="ncb-select-label">Category</span>
            <svg class="ncb-select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ncb-select-menu" id="catFilterMenu" role="listbox">
            <div class="ncb-select-option selected" data-value="" data-label="All Categories">All Categories</div>
            @foreach($categories as $cat)
            <div class="ncb-select-option" data-value="{{ strtolower($cat) }}" data-label="{{ $cat }}">{{ $cat }}</div>
            @endforeach
        </div>
    </div>

    <div class="ncb-group-inline" data-filter-key="season">
        <button type="button" class="ncb-select-trigger placeholder" id="seasonFilterTrigger" data-default-label="Season" tabindex="0" aria-haspopup="listbox">
            <span class="ncb-select-label">Season</span>
            <svg class="ncb-select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ncb-select-menu" id="seasonFilterMenu" role="listbox">
            <div class="ncb-select-option selected" data-value="" data-label="All Seasons">All Seasons</div>
            <div class="ncb-select-option" data-value="wet" data-label="Wet Season">Wet Season</div>
            <div class="ncb-select-option" data-value="dry" data-label="Dry Season">Dry Season</div>
            <div class="ncb-select-option" data-value="all" data-label="Year-Round">Year-Round</div>
        </div>
    </div>

    <div class="ncb-group-inline" data-filter-key="stock">
        <button type="button" class="ncb-select-trigger placeholder" id="stockFilterTrigger" data-default-label="Stock Status" tabindex="0" aria-haspopup="listbox">
            <span class="ncb-select-label">Stock Status</span>
            <svg class="ncb-select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="ncb-select-menu" id="stockFilterMenu" role="listbox">
            <div class="ncb-select-option selected" data-value="" data-label="All Statuses">All Statuses</div>
            <div class="ncb-select-option" data-value="in" data-label="In Stock">In Stock</div>
            <div class="ncb-select-option" data-value="low" data-label="Low Stock">Low Stock</div>
            <div class="ncb-select-option" data-value="out" data-label="Out of Stock">Out of Stock</div>
        </div>
    </div>

    <button type="button" class="filter-clear is-search" id="filterClearBtn" aria-label="Search">
        <svg class="ic-clear" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        <svg class="ic-search" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span id="filterClearLabel">Search</span>
    </button>

    </div>
</div>

<div class="catalog-tabs">
    <button class="tab-btn active" onclick="switchCatalogTab('manage-tab', this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
        Manage Products
    </button>
    <button class="tab-btn" onclick="switchCatalogTab('preview-tab', this)">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
        Farmer's View (Preview)
    </button>
</div>

<!-- TAB 1: MANAGEMENT VIEW -->
<div id="manage-tab" class="tab-content active">
    @if(isset($products) && $products->count() > 0)
    <div class="product-grid">
        @foreach($products as $product)
        <x-product-card :product="$product" :actions="true" data-search="{{ strtolower(trim(($product->name ?? '') . ' ' . ($product->size_label ?? '') . ' ' . ($product->sku ?? '') . ' ' . $product->category_display_name . ' ' . (($product->recommended_season ?? '') === 'wet' ? 'wet season' : (($product->recommended_season ?? '') === 'dry' ? 'dry season' : 'all seasons')) . ' ' . ((int) ($product->stock_quantity ?? 0) <= 0 ? 'out of stock' : ((int) ($product->stock_quantity ?? 0) <= (int) ($product->reorder_threshold ?? 5) ? 'low stock' : 'in stock')) . ' ' . number_format($product->price, 2, '.', ''))) }}"
           data-cat="{{ strtolower(trim($product->category_display_name)) }}"
           data-season="{{ in_array($product->recommended_season ?? 'all', ['wet', 'dry'], true) ? $product->recommended_season : 'all' }}"
           data-stock="{{ (int) ($product->stock_quantity ?? 0) <= 0 ? 'out' : ((int) ($product->stock_quantity ?? 0) <= (int) ($product->reorder_threshold ?? 5) ? 'low' : 'in') }}" />
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h3>No Products Listed Yet</h3>
        <p>Your product catalog is currently empty. Click "Add Product Profile" above to create your first item, or "Import from File" to bring in a whole list from a CSV or Excel sheet.</p>
    </div>
    @endif

    <div class="search-no-results">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <h3>No Matches</h3>
        <p>Nothing in your catalog matches this search. Try a different keyword or clear the search.</p>
    </div>
</div>

<!-- TAB 2: FARMER PREVIEW VIEW -->
<div id="preview-tab" class="tab-content">
    <div class="preview-banner">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <span><strong>Preview Mode:</strong> This is how your store products appear to farmers in the marketplace. Action controls like Edit/Delete are hidden. Click any product to open the farmer-facing detail page.</span>
    </div>

    @if(isset($products) && $products->count() > 0)
    <div class="product-grid">
        @foreach($products as $product)
        {{-- farmer.products.show lives behind role:farmer, so a store owner
             following it from this preview gets a 403. store.products.show
             runs the same controller action under the store prefix. --}}
        <x-product-card :product="$product" href="{{ route('store.products.show', $product->id) }}"
           data-search="{{ strtolower(trim(($product->name ?? '') . ' ' . ($product->size_label ?? '') . ' ' . ($product->sku ?? '') . ' ' . $product->category_display_name . ' ' . (($product->recommended_season ?? '') === 'wet' ? 'wet season' : (($product->recommended_season ?? '') === 'dry' ? 'dry season' : 'all seasons')) . ' ' . ((int) ($product->stock_quantity ?? 0) <= 0 ? 'out of stock' : ((int) ($product->stock_quantity ?? 0) <= (int) ($product->reorder_threshold ?? 5) ? 'low stock' : 'in stock')) . ' ' . number_format($product->price, 2, '.', ''))) }}"
           data-cat="{{ strtolower(trim($product->category_display_name)) }}"
           data-season="{{ in_array($product->recommended_season ?? 'all', ['wet', 'dry'], true) ? $product->recommended_season : 'all' }}"
           data-stock="{{ (int) ($product->stock_quantity ?? 0) <= 0 ? 'out' : ((int) ($product->stock_quantity ?? 0) <= (int) ($product->reorder_threshold ?? 5) ? 'low' : 'in') }}">
        </x-product-card>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h3>No Items Displayed</h3>
        <p>No products are currently visible to buyers in the store marketplace.</p>
    </div>
    @endif

    <div class="search-no-results">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <h3>No Matches</h3>
        <p>Nothing in your catalog matches this search. Try a different keyword or clear the search.</p>
    </div>
</div>

<script>
    function switchCatalogTab(tabId, element) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

        document.getElementById(tabId).classList.add('active');
        element.classList.add('active');
    }

    // Active filter state (Category / Season / Stock status)
    const catalogFilters = { cat: '', season: '', stock: '' };

    // Whether a search term or a filter is currently applied
    function haveActiveFilters() {
        const input = document.getElementById('catalogSearch');
        return Object.values(catalogFilters).some(Boolean) || (input && input.value.trim() !== '');
    }

    // Smart live search + filter: tokenized AND match over name, SKU, category, season, stock, price
    function applyCatalogFilter() {
        const input = document.getElementById('catalogSearch');
        const countEl = document.getElementById('catalogCount');
        const raw = (input ? input.value : '').toLowerCase().trim();
        const tokens = raw.split(/\s+/).filter(Boolean);
        const f = catalogFilters;
        const filtering = tokens.length > 0 || f.cat || f.season || f.stock;

        let visible = 0;
        document.querySelectorAll('.product-card').forEach(function (card) {
            const hay = (card.dataset.search || '').toLowerCase();
            const tokenMatch = tokens.every(function (t) { return hay.includes(t); });
            const catMatch = !f.cat || (card.dataset.cat || '') === f.cat;
            const seasonMatch = !f.season || (card.dataset.season || 'all') === f.season;
            const stockMatch = !f.stock || (card.dataset.stock || '') === f.stock;

            const match = tokenMatch && catMatch && seasonMatch && stockMatch;
            card.classList.toggle('card-hide', !match);
            if (match) visible++;
        });

        document.querySelectorAll('.tab-content').forEach(function (tab) {
            const cards = tab.querySelectorAll(':scope > .product-grid .product-card');
            const grid = tab.querySelector(':scope > .product-grid');
            let emptyEl = tab.querySelector('.search-no-results');
            const gridHasCards = !grid || grid.querySelectorAll('.product-card').length > 0;
            const hasAny = Array.prototype.some.call(cards, function (c) {
                return !c.classList.contains('card-hide');
            });
            if (grid) grid.style.display = (gridHasCards && (!filtering || hasAny)) ? '' : 'none';
            if (emptyEl) emptyEl.classList.toggle('show', gridHasCards && filtering && !hasAny);
        });

        if (countEl) {
            countEl.textContent = visible + (visible === 1 ? ' match' : ' matches');
            countEl.classList.toggle('show', filtering);
            const wrapper = input ? input.closest('.catalog-search') : null;
            if (wrapper) wrapper.classList.toggle('has-count', filtering);
        }

        updateClearButton();
    }

    // Custom styled dropdown (ncb-select style, vanilla JS)
    function initCustomSelect(triggerId, menuId, key) {
        const trigger = document.getElementById(triggerId);
        const menu = document.getElementById(menuId);
        if (!trigger || !menu) return;

        const arrow = trigger.querySelector('.ncb-select-arrow');
        const label = trigger.querySelector('.ncb-select-label');

        function close() {
            menu.style.display = 'none';
            trigger.classList.remove('active');
            if (arrow) arrow.classList.remove('rotate');
        }

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const wasOpen = menu.style.display === 'block';

            const others = document.querySelectorAll('.ncb-select-menu');
            others.forEach(function (m) {
                if (m !== menu) {
                    m.style.display = 'none';
                    const t = m.previousElementSibling;
                    t.classList.remove('active');
                    const a = t.querySelector('.ncb-select-arrow');
                    if (a) a.classList.remove('rotate');
                }
            });

            if (wasOpen) { close(); return; }

            menu.style.display = 'block';
            trigger.classList.add('active');
            if (arrow) arrow.classList.add('rotate');
        });

        menu.querySelectorAll('.ncb-select-option').forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.querySelectorAll('.ncb-select-option').forEach(function (o) { o.classList.remove('selected'); });
                opt.classList.add('selected');

                const value = opt.dataset.value || '';
                const labelText = opt.dataset.label || opt.textContent.trim();
                label.textContent = labelText;
                trigger.classList.toggle('placeholder', value === '');
                catalogFilters[key] = value;

                close();
                applyCatalogFilter();
            });
        });

        document.addEventListener('click', function (e) {
            if (!trigger.contains(e.target) && !menu.contains(e.target)) close();
        });
    }

    // Toggle the toolbar button between "Search" (no filters) and "Clear" (results shown)
    function updateClearButton() {
        const btn = document.getElementById('filterClearBtn');
        const label = document.getElementById('filterClearLabel');
        if (!btn || !label) return;
        const active = haveActiveFilters();
        btn.classList.toggle('is-search', !active);
        btn.setAttribute('aria-label', active ? 'Clear search and filters' : 'Search');
        label.textContent = active ? 'Clear' : 'Search';
    }

    function resetCatalogFilters() {
        document.getElementById('catalogSearch').value = '';
        Object.keys(catalogFilters).forEach(function (k) { catalogFilters[k] = ''; });

        document.querySelectorAll('.ncb-group-inline[data-filter-key]').forEach(function (group) {
            const trigger = group.querySelector('.ncb-select-trigger');
            const menu = group.querySelector('.ncb-select-menu');
            trigger.querySelector('.ncb-select-label').textContent = trigger.dataset.defaultLabel;
            trigger.classList.add('placeholder');
            menu.querySelectorAll('.ncb-select-option').forEach(function (o) { o.classList.remove('selected'); });
            menu.querySelector('.ncb-select-option[data-value=""]').classList.add('selected');
        });

        applyCatalogFilter();
    }

    // Live search as the user types
    (function () {
        const input = document.getElementById('catalogSearch');
        if (!input) return;

        let timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(applyCatalogFilter, 120);
        });
    })();

    // Toolbar button: "Search" first, becomes "Clear" once results are shown
    document.getElementById('filterClearBtn').addEventListener('click', function () {
        if (haveActiveFilters()) {
            resetCatalogFilters();
        } else {
            document.getElementById('catalogSearch').focus();
        }
    });

    initCustomSelect('catFilterTrigger', 'catFilterMenu', 'cat');
    initCustomSelect('seasonFilterTrigger', 'seasonFilterMenu', 'season');
    initCustomSelect('stockFilterTrigger', 'stockFilterMenu', 'stock');
</script>

@endsection