@extends('layouts.sidebar')

@section('title', 'Browse Farm Supplies')

@section('content')
<style>
  .page-header {
    margin-bottom: 24px;
  }
  .page-title {
    font-size: 26px;
    font-weight: 800;
    color: #143d23;
    letter-spacing: -0.02em;
    margin-bottom: 6px;
  }
  .page-subtitle {
    color: #526356;
    font-size: 15px;
    line-height: 1.5;
  }

  /* Search & Filter Bar */
  .search-bar {
    background: #ffffff;
    padding: 16px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 28px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  }
  .search-input-wrapper {
    position: relative;
    flex: 1;
  }
  .search-input-wrapper svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    stroke: #94a3b8;
  }
  .search-input {
    width: 100%;
    padding: 11px 14px 11px 42px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 14px;
    color: #1e2922;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .search-input:focus {
    border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.1);
  }
  .filter-select {
    padding: 11px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    background: #ffffff;
    font-size: 14px;
    color: #334155;
    font-weight: 500;
    outline: none;
    cursor: pointer;
  }
  .btn-search {
    background: #143d23;
    color: #ffffff;
    border: none;
    padding: 11px 24px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
  }
  .btn-search:hover {
    background: #1b4d2e;
  }

  /* Product Grid */
  .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
  }

  /* Product Cards */
  .product-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    overflow: hidden;
  }
  .product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -5px rgba(20, 61, 35, 0.08);
    border-color: #cbd5e1;
  }
  .card-body {
    padding: 20px;
  }
  .badge-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
  }
  .category-pill {
    font-size: 11px;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    padding: 4px 10px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }
  .season-pill {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
  .season-wet { background: #e0f2fe; color: #0369a1; }
  .season-dry { background: #ffedd5; color: #c2410c; }
  .season-all { background: #f0fdf4; color: #166534; }

  .product-title {
    font-size: 16px;
    font-weight: 700;
    color: #143d23;
    margin-bottom: 8px;
    line-height: 1.35;
  }
  .product-description {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 14px;
  }
  .meta-tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
  }
  .meta-tag {
    font-size: 11px;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 500;
  }

  .card-footer {
    padding: 14px 20px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .price-tag {
    font-size: 18px;
    font-weight: 800;
    color: #143d23;
  }
  .stock-status {
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 5px;
  }
  .stock-in { color: #16a34a; }
  .stock-low { color: #dc2626; }

  /* Empty State */
  .empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: #ffffff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
  }
  .empty-icon {
    width: 56px;
    height: 56px;
    background: #f1f5f9;
    color: #94a3b8;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
  }

  @media (max-width: 640px) {
    .search-bar {
      flex-direction: column;
      align-items: stretch;
    }
    .product-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
    .card-body { padding: 14px; }
    .card-footer { padding: 12px 14px; }
    .product-title { font-size: 14px; }
    .product-description { font-size: 12px; }
    .price-tag { font-size: 15px; }
  }
</style>

<div class="page-header">
  <h1 class="page-title">Farm Supplies Catalog</h1>
  <p class="page-subtitle">Find high-quality seeds, fertilizers, and equipment from verified local stores.</p>
</div>

<!-- Search & Filtering Section -->
<form method="GET" action="{{ route('farmer.products.browse') }}" class="search-bar">
  <div class="search-input-wrapper">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search supplies (e.g. Urea, Rice Seeds, Pesticide)..." class="search-input">
  </div>

  <select name="season" class="filter-select">
    <option value="">All Planting Seasons</option>
    <option value="wet" {{ request('season') == 'wet' ? 'selected' : '' }}>🌧️ Wet Season</option>
    <option value="dry" {{ request('season') == 'dry' ? 'selected' : '' }}>☀️ Dry Season</option>
  </select>

  <button type="submit" class="btn-search">Search</button>
</form>

<!-- Supplies Grid -->
<div class="product-grid">
  @forelse($products as $product)
    <a href="{{ route('farmer.products.show', $product->id) }}" class="product-card">
      <div style="height: 180px; background: #f8fafc; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #f1f5f9; overflow: hidden; flex-shrink: 0;">
        @if($product->mainImage && !empty($product->mainImage->file_path))
          <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy">
        @elseif($product->images && $product->images->first())
          <img src="{{ asset('storage/' . $product->images->first()->file_path) }}" alt="{{ $product->name }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy">
        @else
          <svg viewBox="0 0 24 24" width="48" height="48" stroke="#cbd5e1" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
            <circle cx="8.5" cy="8.5" r="1.5"/>
            <polyline points="21 15 16 10 5 21"/>
          </svg>
        @endif
      </div>
      <div class="card-body">
        <div class="badge-container">
          <span class="category-pill">{{ $product->category?->name ?? $product->getOriginal('category') ?? 'General Supply' }}</span>
          
          @if($product->recommended_season)
            <span class="season-pill {{ $product->recommended_season === 'wet' ? 'season-wet' : ($product->recommended_season === 'dry' ? 'season-dry' : 'season-all') }}">
              @if($product->recommended_season === 'wet') 🌧️ Wet
              @elseif($product->recommended_season === 'dry') ☀️ Dry
              @else 🔄 Year-round
              @endif
            </span>
          @endif
        </div>

        <h3 class="product-title">{{ $product->name }}</h3>
        <p class="product-description">{{ $product->description ? \Illuminate\Support\Str::limit($product->description, 90) : 'No additional details provided.' }}</p>

        <div class="meta-tags">
          @if($product->brand)
            <span class="meta-tag">Brand: {{ $product->brand }}</span>
          @endif
          @if($product->sku)
            <span class="meta-tag">Code: {{ $product->sku }}</span>
          @endif
        </div>
      </div>

      <div class="card-footer">
        <span class="price-tag">₱{{ number_format($product->price, 2) }}</span>
        <span class="stock-status {{ $product->is_low_stock ? 'stock-low' : 'stock-in' }}">
          <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
          {{ $product->stock_quantity }} {{ $product->stock_unit ?? 'pieces' }} left
        </span>
      </div>
    </a>
  @empty
    <div class="empty-state">
      <div class="empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <h3 style="font-size: 17px; font-weight: 700; color: #1e2922; margin-bottom: 6px;">No Supplies Found</h3>
      <p style="font-size: 14px; color: #64748b; max-width: 420px; margin: 0 auto;">There are no supplies matching your search. Try searching for broader terms or check back later.</p>
    </div>
  @endforelse
</div>
@endsection