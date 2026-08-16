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
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); 
        gap: 24px; 
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
        padding: 18px; 
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

    .sku-code {
        font-size: 11px; 
        color: #94a3b8; 
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 500;
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
        gap: 8px; 
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .season-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px; 
        font-weight: 600; 
        color: #475569;
        background: #f1f5f9;
        padding: 3px 8px;
        border-radius: 6px;
    }

    .stock-badge { 
        display: inline-flex; 
        align-items: center; 
        gap: 4px; 
        padding: 3px 8px; 
        border-radius: 6px; 
        font-size: 11px; 
        font-weight: 600; 
    }

    .stock-low { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
    .stock-ok { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }

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

    .units-count {
        font-size: 12px; 
        color: #64748b; 
        font-weight: 500;
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
    <a href="{{ route('store.products.create') }}" class="btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add Product Profile
    </a>
</div>

<!-- Success Message -->
@if (session('success'))
<div class="alert-success">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
    <span>{{ session('success') }}</span>
</div>
@endif

<!-- Tab Navigation -->
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
        <div class="product-card">
            <div class="product-image-container">
                @if($product->mainImage && !empty($product->mainImage->file_path))
                    <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" class="product-card-img">
                @elseif($product->images && $product->images->first())
                    <img src="{{ asset('storage/' . $product->images->first()->file_path) }}" alt="{{ $product->name }}" class="product-card-img">
                @else
                    <svg viewBox="0 0 24 24" width="44" height="44" stroke="#cbd5e1" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                @endif
            </div>

            <div class="product-details">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="category-chip">{{ $product->category->name ?? $product->category ?? 'Uncategorized' }}</span>
                    <span class="sku-code">SKU: {{ $product->sku }}</span>
                </div>

                <h3 class="product-title">{{ $product->name }}</h3>
                <p class="product-description">{{ $product->description ?: 'No detailed description provided for this product.' }}</p>

                <div class="badge-group">
                    <span class="season-badge">
                        @if($product->recommended_season === 'wet')
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M16 14v6"/><path d="M8 14v6"/><path d="M12 16v6"/></svg>
                            Wet Season
                        @elseif($product->recommended_season === 'dry')
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                            Dry Season
                        @else
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                            All Seasons
                        @endif
                    </span>

                    @php $qty = $product->stock_quantity ?? 0; @endphp
                    <span class="stock-badge {{ $qty <= ($product->reorder_threshold ?? 5) ? 'stock-low' : 'stock-ok' }}">
                        @if($qty <= ($product->reorder_threshold ?? 5))
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            Low Stock
                        @else
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                            In Stock
                        @endif
                    </span>
                </div>

                <div class="card-footer">
                    <div class="price-row">
                        <div class="price-tag">₱{{ number_format($product->price, 2) }}</div>
                        <span class="units-count">{{ $qty }} available</span>
                    </div>

                    <div class="action-group">
                        <a href="{{ route('store.products.edit', $product->id) }}" class="btn-action" style="flex: 1;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            Edit
                        </a>
                        <form method="POST" action="{{ route('store.products.destroy', $product->id) }}" onsubmit="return confirm('Are you sure you want to delete this product?');" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-action btn-delete" title="Delete Product">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h3>No Products Listed Yet</h3>
        <p>Your product catalog is currently empty. Click "Add Product Profile" above to create your first item.</p>
    </div>
    @endif
</div>

<!-- TAB 2: FARMER PREVIEW VIEW -->
<div id="preview-tab" class="tab-content">
    <div class="preview-banner">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <span><strong>Preview Mode:</strong> This is how your store products appear to farmers in the marketplace. Action controls like Edit/Delete are hidden.</span>
    </div>

    @if(isset($products) && $products->count() > 0)
    <div class="product-grid">
        @foreach($products as $product)
        <div class="product-card">
            <div class="product-image-container">
                @if($product->mainImage && !empty($product->mainImage->file_path))
                    <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" class="product-card-img">
                @elseif($product->images && $product->images->first())
                    <img src="{{ asset('storage/' . $product->images->first()->file_path) }}" alt="{{ $product->name }}" class="product-card-img">
                @else
                    <svg viewBox="0 0 24 24" width="44" height="44" stroke="#cbd5e1" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                @endif
            </div>

            <div class="product-details">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="category-chip">{{ $product->category->name ?? $product->category ?? 'Uncategorized' }}</span>
                </div>

                <h3 class="product-title">{{ $product->name }}</h3>
                <p class="product-description">{{ $product->description ?: 'No detailed description provided for this product.' }}</p>

                <div class="badge-group">
                    <span class="season-badge">
                        @if($product->recommended_season === 'wet')
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M16 14v6"/><path d="M8 14v6"/><path d="M12 16v6"/></svg>
                            Wet Season
                        @elseif($product->recommended_season === 'dry')
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                            Dry Season
                        @else
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
                            All Seasons
                        @endif
                    </span>
                </div>

                <div class="card-footer">
                    <div class="price-row">
                        <div class="price-tag">₱{{ number_format($product->price, 2) }}</div>
                        <span class="units-count" style="color: #16a34a; font-weight: 600;">In Stock</span>
                    </div>
                </div>
            </div>
        </div>
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
</div>

<script>
    function switchCatalogTab(tabId, element) {
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

        document.getElementById(tabId).classList.add('active');
        element.classList.add('active');
    }
</script>

@endsection