@props([
    'product' => null,
    'href' => null,
    'actions' => false,
    'sku' => true,
    'imageHeight' => '190px',
])

@once
<style>
  /* ── Shared product card (catalog style) ─────────────────────────── */
  .product-card {
    position: relative;
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
  .product-card-link {
    position: absolute; inset: 0; z-index: 1;
    border-radius: inherit;
  }
  .product-image-container {
    background: #f8fafc;
    height: 190px;
    width: 100%;
    display: flex; align-items: center; justify-content: center;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
  }
  .product-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
  }
  .product-card:hover .product-card-img { transform: scale(1.03); }
  .product-details { padding: 16px; flex-grow: 1; display: flex; flex-direction: column; }
  .category-chip {
    position: absolute; top: 12px; left: 12px; z-index: 2;
    font-size: 11px; color: #0f766e; background: rgba(204, 251, 241, 0.92);
    padding: 4px 10px; border-radius: 6px;
    text-transform: uppercase; font-weight: 700; letter-spacing: 0.03em;
    max-width: calc(100% - 24px); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
  }
  .sku-code {
    display: inline-block; margin-bottom: 6px;
    font-size: 11px; color: #94a3b8;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 500; white-space: nowrap;
  }
  .product-title { font-size: 16px; color: #0f172a; font-weight: 700; margin: 10px 0 6px 0; line-height: 1.3; }
  .badge-group {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; margin-bottom: 14px; flex-wrap: nowrap;
  }
  .season-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700; line-height: 1;
    white-space: nowrap; flex-shrink: 0;
    padding: 5px 10px; border-radius: 6px;
  }
  .season-badge svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .season-badge.season-wet { background: #e0f2fe; color: #0369a1; }
  .season-badge.season-dry { background: #ffedd5; color: #c2410c; }
  .season-badge.season-all { background: #f0fdf4; color: #166534; }
  .stock-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 6px;
    font-size: 11px; font-weight: 600;
    white-space: nowrap; flex-shrink: 0; margin-left: auto;
  }
  .stock-badge svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .stock-badge.stock-low { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
  .stock-badge.stock-ok { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
  .pc-body { display: flex; flex-direction: column; gap: 8px; margin-bottom: 14px; }

  .card-footer { margin-top: auto; padding-top: 14px; border-top: 1px dashed #e2e8f0; background: transparent; display: block; }
  .price-row { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
  .price-tag { font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.01em; white-space: nowrap; }

  .action-group { display: flex; gap: 8px; position: relative; z-index: 2; }
  .btn-action {
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    background: #f8fafc; color: #334155; border: 1px solid #cbd5e1;
    padding: 8px 12px; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    text-decoration: none; transition: all 0.2s;
  }
  .btn-action svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .btn-action:hover { background: #f1f5f9; color: #0f172a; }
  .btn-delete { background: #fff5f5; color: #e11d48; border-color: #fecdd3; }
  .btn-delete:hover { background: #ffe4e6; color: #be123c; }

  .product-card.hidden, .product-card.card-hide { display: none; }
</style>
@endonce

@php
  $qty = (int) ($product->stock_quantity ?? 0);
  $threshold = (int) ($product->reorder_threshold ?? 5);
  $lowStock = $qty <= $threshold;
  $season = $product->recommended_season ?? '';
  $seasonClass = $season === 'wet' ? 'season-wet' : ($season === 'dry' ? 'season-dry' : 'season-all');
  $seasonLabel = $season === 'wet' ? 'Wet Season' : ($season === 'dry' ? 'Dry Season' : 'All Seasons');
@endphp

<div {{ $attributes->merge(['class' => 'product-card']) }}>
  @if($href)
    <a href="{{ $href }}" class="product-card-link" aria-label="View {{ $product->name }}"></a>
  @endif

  <div class="product-image-container" style="height: {{ $imageHeight }};">
    <span class="category-chip">{{ $product->category->name ?? $product->category ?? 'Uncategorized' }}</span>
    @if($product->mainImage && !empty($product->mainImage->file_path))
      <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" class="product-card-img" loading="lazy">
    @elseif($product->images && $product->images->first())
      <img src="{{ asset('storage/' . $product->images->first()->file_path) }}" alt="{{ $product->name }}" class="product-card-img" loading="lazy">
    @else
      <svg viewBox="0 0 24 24" width="44" height="44" stroke="#cbd5e1" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
      </svg>
    @endif
  </div>

  <div class="product-details">
    @if($sku && !empty($product->sku))
      <span class="sku-code">SKU: {{ $product->sku }}</span>
    @endif

    <h3 class="product-title">{{ $product->name }}</h3>

    <div class="badge-group">
      <span class="season-badge {{ $seasonClass }}">
        @if($season === 'wet')
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M16 14v6"/><path d="M8 14v6"/><path d="M12 16v6"/></svg>
        @elseif($season === 'dry')
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
        @else
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
        @endif
        {{ $seasonLabel }}
      </span>

      <span class="stock-badge {{ $lowStock ? 'stock-low' : 'stock-ok' }}">
        @if($lowStock)
          <svg viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
          Low Stock
        @else
          <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          In Stock
        @endif
      </span>
    </div>

    @if(isset($body))
      <div class="pc-body">{{ $body }}</div>
    @endif

    <div class="card-footer">
      <div class="price-row">
        <div class="price-tag">₱{{ number_format($product->price, 2) }}</div>
        @if(isset($footer)){{ $footer }}@endif
      </div>
      @if($actions)
        <div class="action-group">
          <a href="{{ route('store.products.edit', $product->id) }}" class="btn-action" style="flex:1;">
            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit
          </a>
          <form method="POST" action="{{ route('store.products.destroy', $product->id) }}" data-aqui-confirm="Are you sure you want to delete this product?" data-aqui-danger data-aqui-confirm-label="Delete" style="margin:0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-action btn-delete" title="Delete Product">
              <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
          </form>
        </div>
      @endif
    </div>
  </div>
</div>