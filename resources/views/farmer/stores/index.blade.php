@extends('layouts.sidebar')

@section('title', 'Partner Stores Directory')

@section('content')
<style>
  .store-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
  }
  .store-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
  }
  .store-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transform: translateY(-2px);
  }
  .store-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid #f1f5f9;
  }
  .store-body {
    padding: 14px 20px 18px;
  }
  .product-mini-pill {
    font-size: 11px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 4px 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #475569;
  }
  .store-badge {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 4px;
    letter-spacing: 0.04em;
  }
  .badge-active { background: #dcfce7; color: #16a34a; }
  .badge-new { background: #dbeafe; color: #2563eb; }
</style>

<div class="header-panel" style="margin-bottom: 24px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Partner Store Locations</h1>
      <p style="color: #606d64; font-size: 14px; margin: 0;">
        {{ $totalStores }} registered agricultural supply stores with {{ $totalProducts }} active products available
      </p>
    </div>
  </div>
</div>

@if($stores->isNotEmpty())
  <div class="store-grid">
    @foreach($stores as $store)
      <div class="store-card">
        <div class="store-header">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span class="store-badge badge-active">ACTIVE PARTNER</span>
            <span style="font-size: 12px; font-weight: 700; color: #143d23;">
              {{ $store->products_count }} product{{ $store->products_count !== 1 ? 's' : '' }}
            </span>
          </div>
          <h2 style="font-size: 17px; font-weight: 700; color: #1e2922; margin: 0;">{{ $store->store_name }}</h2>
          @if($store->name !== $store->store_name)
            <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 0 0;">Operated by {{ $store->name }}</p>
          @endif
        </div>

        <div class="store-body">
          <!-- Address -->
          @if($store->address || $store->barangay || $store->city || $store->province)
            <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 10px;">
              <svg viewBox="0 0 24 24" width="16" height="16" stroke="#64748b" stroke-width="2" fill="none" style="flex-shrink: 0; margin-top: 2px;">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
              </svg>
              <div style="font-size: 13px; color: #475569; line-height: 1.4;">
                @if($store->address){{ $store->address }}@endif
                @if($store->barangay), {{ $store->barangay }}@endif
                @if($store->city), {{ $store->city }}@endif
                @if($store->province), {{ $store->province }}@endif
              </div>
            </div>
          @endif

          <!-- Contact -->
          @if($store->contact_number)
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
              <svg viewBox="0 0 24 24" width="16" height="16" stroke="#64748b" stroke-width="2" fill="none" style="flex-shrink: 0;">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
              <span style="font-size: 13px; color: #475569;">{{ $store->contact_number }}</span>
            </div>
          @endif

          <!-- Email -->
          <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="#64748b" stroke-width="2" fill="none" style="flex-shrink: 0;">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span style="font-size: 13px; color: #475569;">{{ $store->email }}</span>
          </div>

          <!-- Products Preview -->
          @if($store->products->isNotEmpty())
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
              <div style="font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px;">Featured Products:</div>
              <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach($store->products->take(4) as $product)
                  <span class="product-mini-pill">
                    ₱{{ number_format($product->price, 0) }}
                    <span style="color: #94a3b8;">&middot;</span>
                    {{ Str::limit($product->name, 18) }}
                  </span>
                @endforeach
                @if($store->products_count > 4)
                  <span class="product-mini-pill" style="background: #143d23; color: white; border-color: #143d23;">
                    +{{ $store->products_count - 4 }} more
                  </span>
                @endif
              </div>
            </div>
          @endif

          <!-- Action -->
          <div style="margin-top: 14px;">
            <a href="{{ route('farmer.products.browse') }}?store={{ $store->id }}"
               style="display: inline-flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: #14532d; text-decoration: none; padding: 6px 12px; border: 1px solid #bbf7d0; border-radius: 6px; transition: all 0.2s;">
              Browse Products
              <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@else
  <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 48px 24px; text-align: center;">
    <svg viewBox="0 0 24 24" width="48" height="48" stroke="#94a3b8" stroke-width="1.5" fill="none" style="margin-bottom: 12px;">
      <path d="M3 9l1-5h16l1 5"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/>
    </svg>
    <h2 style="font-size: 18px; font-weight: 700; color: #1e2922; margin: 0 0 6px 0;">No Partner Stores Yet</h2>
    <p style="font-size: 14px; color: #64748b; margin: 0 0 16px 0;">
      Store owners haven't registered their stores yet. Check back later for available supply locations.
    </p>
    <a href="{{ route('farmer.products.browse') }}" style="background: #143d23; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-block;">
      Browse Available Products
    </a>
  </div>
@endif
@endsection

