@extends('layouts.sidebar')

@section('title', 'My Store')

@section('content')
@php
  $user = auth()->user();
  $storeName = $user->store_name ?? $user->name;
@endphp
<style>
  body { background: #f3f6f4; }

  /* Store Profile Header (Shopee-style) */
  .store-profile {
    background: linear-gradient(135deg, #16a34a, #14532d);
    border-radius: 18px;
    padding: 28px 30px;
    color: #ffffff;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(20,83,45,0.18);
  }
  .store-profile::after {
    content: "";
    position: absolute;
    top: -60px; right: -40px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
  }
  .profile-main {
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
    position: relative;
    z-index: 2;
  }
  .store-avatar {
    width: 74px; height: 74px;
    border-radius: 18px;
    background: rgba(255,255,255,0.18);
    border: 2px solid rgba(255,255,255,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 800;
    color: #ffffff;
    text-transform: uppercase;
    flex-shrink: 0;
    letter-spacing: 0.02em;
  }
  .profile-infos { flex: 1; min-width: 220px; }
  .store-name {
    font-size: 26px;
    font-weight: 800;
    margin: 0 0 4px 0;
    letter-spacing: -0.01em;
    line-height: 1.2;
  }
  .store-owner {
    font-size: 13px;
    color: rgba(255,255,255,0.85);
    margin: 0 0 10px 0;
  }
  .store-owner span { color: #ffffff; font-weight: 600; }
  .profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
  }
  .meta-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.14);
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    color: #ffffff;
    font-weight: 500;
  }
  .meta-item svg { width: 14px; height: 14px; stroke: rgba(255,255,255,0.9); flex-shrink: 0; }
  .profile-stat {
    text-align: center;
    padding: 10px 18px;
    border-radius: 12px;
    background: rgba(255,255,255,0.14);
    min-width: 86px;
    position: relative;
    z-index: 2;
  }
  .profile-stat .num { font-size: 24px; font-weight: 800; line-height: 1; }
  .profile-stat .label { font-size: 11px; color: rgba(255,255,255,0.85); margin-top: 4px; }

  /* Products Section */
  .products-section { margin-top: 4px; }
  .section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
    flex-wrap: wrap;
  }
  .section-heading h2 { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; }
  .section-heading p { font-size: 13px; color: #64748b; margin: 2px 0 0 0; }
  .btn-outline-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #14532d;
    color: #ffffff;
    padding: 9px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    transition: all 0.2s ease;
  }
  .btn-outline-primary:hover { background: #166534; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(20,83,45,0.2); }
  .btn-outline-primary svg { width: 15px; height: 15px; stroke: currentColor; }

  .dashboard-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
  }
  .dashboard-product-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
  }
  .dashboard-product-card:hover { border-color: #cbd5e1; box-shadow: 0 10px 24px rgba(0,0,0,0.08); transform: translateY(-3px); }
  .dash-edit-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #14532d;
    color: #ffffff;
    padding: 7px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
  }
  .dash-edit-btn:hover { background: #166534; box-shadow: 0 3px 10px rgba(20,83,45,0.25); }
  .dash-img-wrapper { height: 150px; background: #f8fafc; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid #f1f5f9; }
  .dash-img-wrapper img { width: 100%; height: 100%; object-fit: cover; }
  .cat-chip { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #0f766e; background: #ccfbf1; padding: 2px 6px; border-radius: 4px; letter-spacing: 0.03em; display: inline-block; }
  .stock-ok { background: #f0fdf4; color: #16a34a; }
  .stock-low { background: #fef2f2; color: #dc2626; }

  @media (max-width: 640px) {
    .store-profile { padding: 20px; }
    .store-name { font-size: 20px; }
    .dashboard-product-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .dash-img-wrapper { height: 110px; }
    .profile-stat { padding: 8px 12px; min-width: 70px; }
    .profile-stat .num { font-size: 19px; }
  }
</style>

<!-- 1. STORE PROFILE HEADER -->
<div class="store-profile">
  <div class="profile-main">
    <div class="store-avatar">{{ Str::upper(mb_substr($storeName, 0, 1)) }}</div>

    <div class="profile-infos">
      <h1 class="store-name">{{ $storeName }}</h1>
      @if($user->store_name && $user->store_name !== $user->name)
        <p class="store-owner">Owned by <span>{{ $user->name }}</span></p>
      @endif

      @if($user->address || $user->barangay || $user->city || $user->province || $user->contact_number || $user->email)
        <div class="profile-meta">
          @if($user->address || $user->barangay || $user->city || $user->province)
            <span class="meta-item">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              @if($user->address){{ $user->address }}@endif
              @if($user->barangay), {{ $user->barangay }}@endif
              @if($user->city), {{ $user->city }}@endif
              @if($user->province), {{ $user->province }}@endif
            </span>
          @endif
          @if($user->contact_number)
            <span class="meta-item">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              {{ $user->contact_number }}
            </span>
          @endif
          @if($user->email)
            <span class="meta-item">
              <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              {{ $user->email }}
            </span>
          @endif
        </div>
      @endif
    </div>

    <div class="profile-stat">
      <div class="num">{{ $productCount }}</div>
      <div class="label">Products</div>
    </div>
  </div>
</div>

<!-- 2. PRODUCTS SECTION -->
<div class="products-section">
  <div class="section-heading">
    <div>
      <h2>My Products</h2>
      <p>All active listings from your store, shown to farmers in the marketplace.</p>
    </div>
    <a href="{{ route('store.storefront.index') }}" class="btn-outline-primary">
      <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Manage Products
    </a>
  </div>

  @if($products->isNotEmpty())
    <div class="dashboard-product-grid">
      @foreach($products as $product)
        <div class="dashboard-product-card" onclick="window.location='{{ route('store.products.show', $product->id) }}'" role="link" tabindex="0" title="View details for {{ $product->name }}">
          <div class="dash-img-wrapper">
            @if($product->mainImage && !empty($product->mainImage->file_path))
              <img src="{{ asset('storage/' . $product->mainImage->file_path) }}" alt="{{ $product->name }}" loading="lazy">
            @elseif($product->images && $product->images->first())
              <img src="{{ asset('storage/' . $product->images->first()->file_path) }}" alt="{{ $product->name }}" loading="lazy">
            @else
              <svg viewBox="0 0 24 24" width="36" height="36" stroke="#94a3b8" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
              </svg>
            @endif
          </div>
          <div style="padding:14px; display:flex; flex-direction:column; gap:7px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:6px;">
              <span class="cat-chip">{{ $product->category->name ?? $product->category ?? 'General' }}</span>
              @php $qty = $product->stock_quantity ?? 0; @endphp
              <span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;" class="{{ $qty <= ($product->reorder_threshold ?? 5) ? 'stock-low' : 'stock-ok' }}">
                {{ $qty }} in stock
              </span>
            </div>
            <h3 style="font-size:14px;font-weight:700;color:#1e2922;margin:0;line-height:1.3;">{{ $product->name }}</h3>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
              <div style="font-size:16px;font-weight:800;color:#0f172a;">₱{{ number_format($product->price, 2) }}</div>
              <a href="{{ route('store.products.edit', $product->id) }}" class="dash-edit-btn" onclick="event.stopPropagation();" title="Update this product">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Edit
              </a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div style="background:#ffffff;border:1px dashed #cbd5e1;border-radius:14px;padding:48px 20px;text-align:center;">
      <svg viewBox="0 0 24 24" width="40" height="40" stroke="#94a3b8" stroke-width="1.5" fill="none" style="margin-bottom:12px;"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
      <h3 style="font-size:17px;font-weight:700;color:#1e2922;margin:0 0 6px 0;">No Products Listed Yet</h3>
      <p style="font-size:14px;color:#64748b;margin:0 0 16px 0;">Add your first product to start selling to farmers.</p>
      <a href="{{ route('store.products.create') }}" style="background:#14532d;color:#ffffff;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;display:inline-block;">Add Product</a>
    </div>
  @endif
</div>
@endsection

