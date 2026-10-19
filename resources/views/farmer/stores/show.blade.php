@extends('layouts.sidebar')

@section('title', $store->store_name)

@section('content')
<style>
  /* The contact rows and chips are the same ones the Stores Directory card
     uses, so a store looks like the same place before and after the click. */
  .contact-row {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 8px; font-size: 13px; color: #4b5c50;
  }
  .contact-row:last-of-type { margin-bottom: 0; }
  .contact-icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: #f5f7f6; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .meta-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 600; color: #6b7c6e;
  }
  .meta-chip svg { width: 14px; height: 14px; }

  /* ── Store panel ──────────────────────────────────────────────── */
  .store-hero {
    display: flex; gap: 0;
    background: #ffffff;
    border: 1px solid #f0f2f1;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    margin-bottom: 26px;
  }
  .store-hero-cover {
    width: 240px; flex-shrink: 0;
    background: #f5f7f6;
    position: relative;
    min-height: 180px;
  }
  .store-hero-cover img {
    width: 100%; height: 100%;
    object-fit: cover;
    position: absolute; inset: 0;
    display: block;
  }
  .store-hero-body {
    flex: 1; min-width: 0;
    padding: 22px 24px;
    display: flex; flex-direction: column; justify-content: center; gap: 14px;
  }
  .store-hero-title {
    font-size: 22px; font-weight: 800; color: #1a2b1e;
    letter-spacing: -0.02em; margin: 0;
  }
  /* keep-sub: the layout hides a plain <p> that follows an <h1>, and this
     subtitle is exactly that — the class opts it back in. */
  .store-hero-sub {
    font-size: 13px; font-weight: 600; color: #6b7c6e; margin: 3px 0 0;
  }
  .store-hero-contacts { display: flex; flex-direction: column; }

  /* Same section break as the directory card: the line is where the store
     stops and its stock begins. */
  .store-hero-stats {
    display: flex; flex-wrap: wrap; gap: 10px 14px; align-items: center;
    padding-top: 14px;
    border-top: 1px solid #dfe6e1;
  }

  .section-label {
    font-size: 11px; font-weight: 600; color: #879a8c;
    text-transform: uppercase; letter-spacing: 0.06em; margin: 0 0 12px;
  }

  /* ── Product grid: the same four-across shelf as Browse Supplies ── */
  .product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
  }
  @media (max-width: 1200px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
  @media (max-width: 900px)  { .product-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 640px)  { .product-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }

  .empty-state {
    grid-column: 1 / -1;
    text-align: center; padding: 60px 20px;
    background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 16px;
  }
  .empty-icon {
    width: 56px; height: 56px; background: #f1f5f9; color: #94a3b8;
    border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 16px;
  }
  .see-more-btn {
    display: inline-flex; align-items: center; justify-content: center;
    margin-top: 16px; padding: 11px 20px;
    background: #143d23; color: #ffffff;
    border: none; border-radius: 10px;
    font-size: 14px; font-weight: 700; font-family: inherit; text-decoration: none;
    cursor: pointer; transition: background 0.2s;
  }
  .see-more-btn:hover { background: #1a5230; }

  /* Stacked on phones: the cover becomes a banner instead of a side column. */
  @media (max-width: 700px) {
    .store-hero { flex-direction: column; }
    .store-hero-cover { width: 100%; min-height: 150px; height: 150px; }
    .store-hero-body { padding: 18px 20px; }
    .store-hero-title { font-size: 19px; }
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'Stores', 'url' => route('farmer.stores.index')],
  ['label' => $store->store_name],
]"/>

{{-- The store's own details. Nothing here is a control — it is a place a
     farmer reads before deciding whether to order from it. --}}
<div class="store-hero">
  <div class="store-hero-cover">
    <img
      src="{{ $store->cover_image ? asset('storage/' . $store->cover_image) : asset('images/store_isle.png') }}"
      alt="{{ $store->store_name }} cover"
    >
  </div>

  <div class="store-hero-body">
    <div>
      <h1 class="store-hero-title">{{ $store->store_name }}</h1>
      @if($store->name !== $store->store_name)
        <p class="store-hero-sub keep-sub">Managed by {{ $store->name }}</p>
      @endif
    </div>

    <div class="store-hero-contacts">
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

      <div class="contact-row">
        <div class="contact-icon">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="#6b7c6e" stroke-width="2" fill="none"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <span>{{ $store->email }}</span>
      </div>
    </div>

    <div class="store-hero-stats">
      <div class="meta-chip">
        <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
        {{ $productsCount }} product{{ $productsCount !== 1 ? 's' : '' }}
      </div>
      {{-- Kept in the template, switched off in the display — same as the
           directory card. Delete `display: none;` to bring it back. --}}
      <div class="meta-chip" style="display: none;">
        <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Joined {{ $store->created_at->format('M Y') }}
      </div>
    </div>
  </div>
</div>

<h2 class="section-label">Products from this store</h2>

<div class="product-grid">
  @forelse($products as $product)
    <x-product-card :product="$product" href="{{ route('farmer.products.show', $product->id) }}" />
  @empty
    <div class="empty-state">
      <div class="empty-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="16" y1="14" x2="8" y2="14"/></svg>
      </div>
      <h3 style="font-size: 17px; font-weight: 700; color: #1e2922; margin-bottom: 6px;">No Products Listed Yet</h3>
      <p style="font-size: 14px; color: #64748b; max-width: 420px; margin: 0 auto;">This store hasn't added any products yet. Check back later.</p>
      <a href="{{ route('farmer.products.browse') }}" class="see-more-btn">Browse All Supplies</a>
    </div>
  @endforelse
</div>
@endsection
