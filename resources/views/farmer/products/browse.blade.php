@extends('layouts.sidebar')

@section('title', 'Browse Products')

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">B2B Supply Directory</h1>
    <p style="color: #606d64; font-size: 14px;">Explore available industrial stock listings from verified network stores.</p>
  </div>

  <div style="margin-bottom: 24px; display: flex; gap: 12px; align-items: center;">
    <input type="text" placeholder="Search parameters (e.g. Fertilizer, Seeds...)" style="flex: 1; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
    <select style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 14px; color: #4a574e;">
      <option value="">All Seasons</option>
      <option value="wet">Wet Season</option>
      <option value="dry">Dry Season</option>
    </select>
    <button style="background: #143d23; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;">Search</button>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">

    @forelse($products as $product)
    <a href="{{ route('farmer.products.show', $product->id) }}" style="text-decoration: none; color: inherit;">
      <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border: 1px solid #e2e8f0; background: white; border-radius: 12px; overflow: hidden; padding: 0; transition: box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'" onmouseout="this.style.boxShadow='none'">
        <div style="padding: 20px;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <span style="font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">{{ $product->category?->name ?? $product->getOriginal('category') ?? 'Uncategorized' }}</span>
            @if($product->recommended_season)
            <span style="font-size: 11px; font-weight: 700; background: {{ $product->recommended_season === 'wet' ? '#e0f2fe' : ($product->recommended_season === 'dry' ? '#ffedd5' : '#f0fdf4') }}; color: {{ $product->recommended_season === 'wet' ? '#0369a1' : ($product->recommended_season === 'dry' ? '#c2410c' : '#166534') }}; padding: 4px 8px; border-radius: 4px;">
              @if($product->recommended_season === 'wet') 🌧️ Wet Season
              @elseif($product->recommended_season === 'dry') ☀️ Dry Season
              @else 🔄 All Seasons
              @endif
            </span>
            @endif
          </div>
          <h3 style="font-size: 16px; font-weight: 700; color: #1e2922; margin-bottom: 6px; line-height: 1.4;">{{ $product->name }}</h3>
          <p style="font-size: 13px; color: #606d64; line-height: 1.5; margin-bottom: 12px;">{{ $product->description ? \Illuminate\Support\Str::limit($product->description, 100) : 'No description provided.' }}</p>
          @if($product->brand)
          <span style="font-size: 11px; color: #64748b; background: #f8fafc; padding: 2px 6px; border-radius: 4px;">{{ $product->brand }}</span>
          @endif
          <span style="font-size: 11px; font-family: monospace; color: #94a3b8; background: #f8fafc; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">SKU: {{ $product->sku }}</span>
        </div>
        <div style="padding: 16px 20px; background: #fafbfa; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
          <span style="font-size: 18px; font-weight: 800; color: #143d23;">₱{{ number_format($product->price, 2) }}</span>
          <span style="font-size: 12px; font-weight: 600; color: {{ $product->is_low_stock ? '#b91c1c' : '#16a34a' }};">
            {{ $product->stock_quantity }} {{ $product->stock_unit ?? 'in stock' }}
          </span>
        </div>
    </a>
    @empty
    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #87a992;">
      <svg viewBox="0 0 24 24" width="64" height="64" stroke="#d1d9d3" fill="none" stroke-width="1.5" style="display: block; margin: 0 auto 16px;">
        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
      </svg>
      <h3 style="font-size: 18px; color: #4a574e; margin-bottom: 8px;">No Products Available</h3>
      <p style="font-size: 14px; line-height: 1.6;">There are currently no active product listings from network stores. Please check back later.</p>
    </div>
    @endforelse

  </div>
@endsection
