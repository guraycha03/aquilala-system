@extends('layouts.sidebar')

@section('title', 'Product Recommendations')

@section('content')
  <style>
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    @media (max-width: 640px) {
      .product-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    }
  </style>
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Smart Recommendations</h1>
    <p style="color: #606d64; font-size: 14px;">Rule-based recommendations on fertilizers, seeds, and supplies based on your crop, season, and land area.</p>
  </div>

  <div class="data-table-container" style="padding: 24px;">

    <!-- AI Assistance Placeholder (LLM integration coming soon) -->
    <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #a7f3d0; border-radius: 16px; padding: 24px; margin-bottom: 24px; display: flex; gap: 18px; align-items: flex-start;">
      <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #16a34a, #22c55e); border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 14px rgba(34,197,94,0.3);">
        <svg viewBox="0 0 24 24" width="26" height="26" stroke="white" stroke-width="2" fill="none">
          <path d="M12 2a4 4 0 0 1 4 4c0 1.1-.45 2.1-1.17 2.83A4 4 0 0 1 16 12a4 4 0 0 1-4 4c-1.1 0-2.1-.45-2.83-1.17A4 4 0 0 1 8 12a4 4 0 0 1 1.17-3.17A4 4 0 0 1 8 6a4 4 0 0 1 4-4z"/>
          <path d="M12 12v10"/>
          <circle cx="12" cy="6" r="1" fill="#fef08a"/>
          <path d="M4 20c1.5-1 3-1.5 4-1.5S9.5 19 11 20c1.5-1 3-1.5 4-1.5s2.5.5 4 1.5"/>
        </svg>
      </div>
      <div style="flex: 1; min-width: 0;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 6px;">
          <h2 style="font-size: 17px; font-weight: 700; color: #14532d; margin: 0;">AI Crop &amp; Product Assistant</h2>
          <span style="font-size: 11px; font-weight: 700; background: #dcfce7; color: #16a34a; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.04em;">Coming Soon</span>
        </div>
        <p style="font-size: 14px; color: #15803d; line-height: 1.6; margin: 0 0 14px 0;">
          Our intelligent assistant will help you find the most suitable crops and products based on your crop type, selected season, and land area. Just describe what you're growing or your farming goals.
        </p>
        <div style="background: white; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px 16px; display: flex; gap: 10px; align-items: center;">
          <svg viewBox="0 0 24 24" width="18" height="18" stroke="#22c55e" stroke-width="2" fill="none" style="flex-shrink: 0;"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          <span style="flex: 1; color: #86a892; font-size: 14px;">e.g. "What rice inputs do I need for 1 hectare in the wet season?"</span>
          <button disabled style="background: linear-gradient(135deg, #16a34a, #22c55e); color: white; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: not-allowed; opacity: 0.7;">Ask Assistant</button>
        </div>
      </div>
    </div>

    <!-- Available Products Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
      <div>
        <h2 style="font-size: 18px; font-weight: 700; color: #1e2922; margin: 0;">Available Products</h2>
        <p style="color: #606d64; font-size: 13px; margin: 4px 0 0 0;">All active listings from partner stores, recommended for your farming needs.</p>
      </div>
      <a href="{{ route('farmer.products.browse') }}" style="font-size: 13px; font-weight: 700; color: #143d23; text-decoration: none; padding: 8px 16px; border-radius: 8px; background: rgba(20,61,35,0.06); display: inline-flex; align-items: center; gap: 6px;">
        Browse All <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>

    <div class="product-grid">
      @forelse($products as $product)
      @php
        $seasonPillStyle = 'font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; white-space: nowrap; background: ' . ($product->recommended_season === 'wet' ? '#e0f2fe' : ($product->recommended_season === 'dry' ? '#ffedd5' : '#f0fdf4')) . '; color: ' . ($product->recommended_season === 'wet' ? '#0369a1' : ($product->recommended_season === 'dry' ? '#c2410c' : '#166534')) . ';';
        $stockTextStyle = 'font-size: 12px; font-weight: 600; color: ' . ($product->is_low_stock ? '#b91c1c' : '#16a34a') . ';';
      @endphp
      <a href="{{ route('farmer.products.show', $product->id) }}" style="text-decoration: none; color: inherit;">
<div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border: 1px solid #e2e8f0; background: white; border-radius: 12px; overflow: hidden; padding: 0; height: 100%; transition: box-shadow 0.2s, transform 0.2s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(20,61,35,0.12)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='none'; this.style.transform='none'">
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
          <div style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; gap: 8px;">
              <span style="font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">{{ $product->category?->name ?? $product->getOriginal('category') ?? 'Uncategorized' }}</span>
              @if($product->recommended_season)
              <span data-inline-style="{{ $seasonPillStyle }}">
                @if($product->recommended_season === 'wet')
                  <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg> Wet
                @elseif($product->recommended_season === 'dry')
                  <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><line x1="12" y1="2" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22"/><line x1="4.9" y1="4.9" x2="6.3" y2="6.3"/><line x1="17.7" y1="17.7" x2="19.1" y2="19.1"/><line x1="2" y1="12" x2="4" y2="12"/><line x1="20" y1="12" x2="22" y2="12"/><line x1="4.9" y1="19.1" x2="6.3" y2="17.7"/><line x1="17.7" y1="6.3" x2="19.1" y2="4.9"/></svg> Dry
                @else
                  <svg viewBox="0 0 24 24" width="11" height="11" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg> All
                @endif
              </span>
              @endif
            </div>
            <h3 style="font-size: 16px; font-weight: 700; color: #1e2922; margin: 0 0 6px 0; line-height: 1.4;">{{ $product->name }}</h3>
            <p style="font-size: 13px; color: #606d64; line-height: 1.5; margin: 0 0 12px 0;">{{ $product->description ? \Illuminate\Support\Str::limit($product->description, 100) : 'No description provided.' }}</p>
            @if($product->storeOwner)
            <div style="font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 5px; margin-bottom: 8px;">
              <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 9l1-5h16l1 5"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/></svg>
              {{ $product->storeOwner->store_name ?? $product->storeOwner->name }}
            </div>
            @endif
            @if($product->brand)
            <span style="font-size: 11px; color: #64748b; background: #f8fafc; padding: 2px 6px; border-radius: 4px;">{{ $product->brand }}</span>
            @endif
          </div>
          <div style="padding: 16px 20px; background: #fafbfa; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
            <span style="font-size: 18px; font-weight: 800; color: #143d23;">₱{{ number_format($product->price, 2) }}</span>
            <span data-inline-style="{{ $stockTextStyle }}">
              {{ $product->stock_quantity }} {{ $product->stock_unit ?? 'pieces' }}
            </span>
          </div>
        </div>
      </a>
      @empty
      <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #87a992; background: #fafbfa; border: 1px dashed #d1d9d3; border-radius: 12px;">
        <svg viewBox="0 0 24 24" width="64" height="64" stroke="#d1d9d3" fill="none" stroke-width="1.5" style="display: block; margin: 0 auto 16px;">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <h3 style="font-size: 18px; color: #4a574e; margin-bottom: 8px;">No Products Available</h3>
        <p style="font-size: 14px; line-height: 1.6;">There are currently no active product listings from network stores. Please check back later.</p>
      </div>
      @endforelse
    </div>
  </div>
@endsection
