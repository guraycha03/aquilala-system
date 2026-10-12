@extends('layouts.sidebar')

@section('title', 'Partner Stores Directory')

@section('content')
<style>
  .store-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 20px;
  }
  .store-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #f0f2f1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .store-card:hover {
    border-color: #d1d9d3;
    box-shadow: 0 12px 40px rgba(0,0,0,0.1);
    transform: translateY(-4px);
  }

  /* Cover image area */
  .store-cover {
    height: 160px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f5f7f6;
  }
  .store-cover-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    inset: 0;
  }

  .store-body { padding: 0 24px 22px; }

  /* Smooth fading glassmorphism header */
  .store-name-area {
    padding: 68px 24px 12px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-top: -80px;
    position: relative;
    z-index: 3;
    
    /* Soft white gradient for a natural color transition */
    background: linear-gradient(
      180deg, 
      rgba(255, 255, 255, 0.3) 0%, 
      rgba(255, 255, 255, 0.85) 40%, 
      #ffffff 100%
    );

    /* Backdrop blur to soften the image behind the text */
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);

    /* Mask to feather/fade the top edge into complete transparency */
    mask-image: linear-gradient(
      180deg,
      rgba(0, 0, 0, 0) 0%,
      rgba(0, 0, 0, 0.8) 20%,
      rgba(0, 0, 0, 1) 45%
    );
    -webkit-mask-image: linear-gradient(
      180deg,
      rgba(0, 0, 0, 0) 0%,
      rgba(0, 0, 0, 0.8) 20%,
      rgba(0, 0, 0, 1) 45%
    );

    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
  }

  /* Text styling optimized for readability over the blurred overlay */
  .store-title {
    font-size: 18px;
    font-weight: 800;
    color: #1a2b1e;
    margin: 0;
    letter-spacing: -0.01em;
    text-shadow: 0 1px 3px rgba(255, 255, 255, 0.8);
  }
  .store-subtitle {
    font-size: 12px;
    font-weight: 600;
    color: rgba(26, 43, 30, 0.7);
    margin: 2px 0 0 0;
    text-shadow: 0 1px 3px rgba(255, 255, 255, 0.8);
  }

  .partner-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    padding: 4px 10px; border-radius: 6px; letter-spacing: 0.06em;
    background: rgba(240, 253, 244, 0.9);
    color: #15803d; border: 1px solid rgba(187, 247, 208, 0.8);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
  }
  .partner-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: #16a34a; animation: pulse-dot 2s infinite; }

  @keyframes pulse-dot {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
  }

  .product-pill {
    font-size: 11px; font-weight: 600;
    background: #f8faf9; border: 1px solid #e8ecea;
    border-radius: 8px; padding: 5px 10px;
    display: inline-flex; align-items: center; gap: 5px;
    color: #4b5c50; transition: all 0.15s;
  }
  .product-pill:hover { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
  .product-pill.more {
    background: linear-gradient(135deg, #143d23, #1a5230);
    color: white; border-color: transparent;
    box-shadow: 0 2px 6px rgba(20,61,35,0.2);
  }

  .browse-link {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 700; color: #fff;
    text-decoration: none; padding: 10px 20px;
    border: none; border-radius: 10px;
    background: linear-gradient(135deg, #143d23, #1a5230);
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(20,61,35,0.25);
  }
  .browse-link:hover {
    box-shadow: 0 4px 16px rgba(20,61,35,0.35);
    transform: translateY(-1px);
  }
  .browse-link:active { transform: scale(0.98); }

  .contact-row {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 8px; font-size: 13px; color: #4b5c50;
  }
  .contact-icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: #f5f7f6; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }

  .store-meta-row {
    display: flex; gap: 12px; align-items: center;
    padding: 12px 24px;
    border-top: 1px solid #f5f7f6;
    background: #fafdfb;
    margin-top: 12px;
  }
  .meta-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 600; color: #6b7c6e;
  }
  .meta-chip svg { width: 14px; height: 14px; }

  .empty-state {
    background: #ffffff;
    border: 1px solid #f0f2f1;
    border-radius: 18px;
    padding: 64px 24px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
  }
</style>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">Partner Store Locations</h1>
  <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">
    {{ $totalStores }} registered agricultural supply stores with {{ $totalProducts }} active products available
  </p>
</div>

@if($stores->isNotEmpty())
  <div class="store-grid">
    @foreach($stores as $store)
      <div class="store-card">
        <!-- Cover Image -->
        <div class="store-cover">
          <img 
            src="{{ $store->cover_image ? asset('storage/' . $store->cover_image) : asset('images/store_isle.png') }}" 
            alt="{{ $store->store_name }} Cover" 
            class="store-cover-img"
          >
        </div>

        <!-- Upper Translucent Header & Name Area -->
        <div class="store-name-area">
          <div style="flex: 1; min-width: 0;">
            <h2 class="store-title">{{ $store->store_name }}</h2>
            @if($store->name !== $store->store_name)
              <p class="store-subtitle">Operated by {{ $store->name }}</p>
            @endif
          </div>
          <span class="partner-badge"><span class="dot"></span> Active</span>
        </div>

        <!-- Body -->
        <div class="store-body">
          @if($store->address || $store->barangay || $store->city || $store->province)
            <div class="contact-row">
              <div class="contact-icon">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="#6b7c6e" stroke-width="2" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              </div>
              <span style="line-height: 1.4;">
                @if($store->address){{ $store->address }}@endif
                @if($store->barangay), {{ $store->barangay }}@endif
                @if($store->city), {{ $store->city }}@endif
                @if($store->province), {{ $store->province }}@endif
              </span>
            </div>
          @endif

          @if($store->contact_number)
            <div class="contact-row">
              <div class="contact-icon">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="#6b7c6e" stroke-width="2" fill="none"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              </div>
              <span>{{ $store->contact_number }}</span>
            </div>
          @endif

          <div class="contact-row" style="margin-bottom: 0;">
            <div class="contact-icon">
              <svg viewBox="0 0 24 24" width="14" height="14" stroke="#6b7c6e" stroke-width="2" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <span>{{ $store->email }}</span>
          </div>

          <!-- Products Preview -->
          @if($store->products->isNotEmpty())
            <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid #f5f7f6;">
              <div style="font-size: 11px; font-weight: 600; color: #879a8c; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.06em;">Featured Products</div>
              <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach($store->products->take(4) as $product)
                  <span class="product-pill">
                    ₱{{ number_format($product->price, 0) }}
                    <span style="color: #d1d9d3;">·</span>
                    {{ Str::limit($product->display_name, 26) }}
                  </span>
                @endforeach
                @if($store->products_count > 4)
                  <span class="product-pill more">
                    +{{ $store->products_count - 4 }} more
                  </span>
                @endif
              </div>
            </div>
          @endif

          <!-- Action -->
          <div style="margin-top: 16px;">
            <a href="{{ route('farmer.products.browse') }}?store={{ $store->id }}" class="browse-link">
              Browse Products
              <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
          </div>
        </div>

        <!-- Meta Footer -->
        <div class="store-meta-row">
          <div class="meta-chip">
            <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
            {{ $store->products_count }} product{{ $store->products_count !== 1 ? 's' : '' }}
          </div>
          <div class="meta-chip">
            <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Joined {{ $store->created_at->format('M Y') }}
          </div>
        </div>
      </div>
    @endforeach
  </div>
@else
  <div class="empty-state">
    <div style="width: 80px; height: 80px; border-radius: 22px; margin: 0 auto 20px; background: linear-gradient(135deg, #f5f7f6, #e8ecea); display: flex; align-items: center; justify-content: center;">
      <svg viewBox="0 0 24 24" width="36px" height="36px" stroke="#879a8c" stroke-width="1.5" fill="none">
        <path d="M3 9l1-5h16l1 5"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/>
      </svg>
    </div>
    <h2 style="font-size: 18px; font-weight: 700; color: #1a2b1e; margin: 0 0 6px 0;">No Partner Stores Yet</h2>
    <p style="font-size: 14px; color: #879a8c; margin: 0 0 24px 0; line-height: 1.5;">
      Store owners haven't registered their stores yet. Check back later for available supply locations.
    </p>
    <a href="{{ route('farmer.products.browse') }}" class="browse-link">
      Browse Available Products
      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
  </div>
@endif
@endsection