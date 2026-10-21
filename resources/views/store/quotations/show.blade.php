@extends('layouts.sidebar')

@section('title', 'Cost Quotation — ' . $quotation->quotation_number)

@section('content')
<style>
  .page-header-card {
    background: #F3F5F0;
    border: 1px solid #E0E5DB;
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
  }

  .detail-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
    margin-bottom: 20px;
  }
  .detail-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f0f2f1;
    background: #fafdfb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
  }
  .detail-card-body { padding: 24px; }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
  }
  .info-item label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: #879a8c; font-weight: 600; display: block; margin-bottom: 4px; }
  .info-item .value { font-size: 15px; font-weight: 700; color: #1a2b1e; }

  /* Table base style lives in the shared .data-table rules (layouts.sidebar). */
  tr.yours td { background: #F7FAF6; }
  tr.yours td:first-child { box-shadow: inset 3px 0 0 #A8C9B0; }

  /* The tag sits next to a product name, so it must never break onto a
     second line — nowrap keeps it one short chip however narrow the
     column gets. */
  .your-product-tag {
    display: inline-block;
    background: #EDF1E9; color: #4b5c50;
    border: 1px solid #C8D5C5;
    padding: 2px 8px; border-radius: 6px;
    font-size: 10px; font-weight: 700; margin-left: 8px;
    letter-spacing: 0.04em;
    white-space: nowrap;
  }

  /* The deferred marker wears the same shape but its own colour, so the
     two never read as the same thing. */
  .your-product-tag--deferred {
    background: #FAF6E8; color: #7A6A3A; border-color: #EDE4C8;
  }

  /* The table's total rows. A class (not an inline style) so the dark
     theme can repaint them. */
  .quotation-total-row { background: #fafdfb; }
  .quotation-total-row--deferred { background: #FAF8F0; }

  .total-badge {
    display: inline-flex; align-items: baseline; gap: 6px;
    background: #F3F5F0;
    border: 1px solid #E0E5DB;
    border-radius: 12px;
    padding: 10px 18px;
  }
  .total-badge .total-label { font-size: 12px; font-weight: 600; color: #647263; text-transform: uppercase; letter-spacing: 0.04em; }
  .total-badge .total-value { font-size: 22px; font-weight: 800; color: #25392b; letter-spacing: -0.02em; }

  /* The tables keep a floor width so a phone scrolls the TABLE sideways
     instead of crushing every column into a two-word stack. The shared
     .app-scroll wrapper provides the sideways scroll. */
  .quotation-table { min-width: 560px; }
  .quotation-table--wide { min-width: 680px; }

  /* ── Phone sizing ───────────────────────────────────────────── */
  @media (max-width: 640px) {
    .page-header-card { padding: 18px 16px; border-radius: 14px; }
    .page-header-card h1 { font-size: 21px; }
    .detail-card-header { padding: 15px 16px; }
    .detail-card-body { padding: 16px; }
    .info-grid { gap: 14px; }
    .total-badge { padding: 8px 14px; }
    .total-badge .total-value { font-size: 19px; }
  }

  /* ── Type colours ───────────────────────────────────────────────
     Every colour on this page lives in a class, never in an inline
     style, so the dark theme has a handle to repaint it. */
  .q-title { font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 6px 0; letter-spacing: -0.02em; }
  .q-subtitle { color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5; }
  .q-heading { font-size: 16px; font-weight: 700; color: #1a2b1e; margin: 0; }
  .q-note { font-size: 13px; color: #879a8c; margin: 3px 0 0 0; }
  .q-product-name { font-weight: 600; color: #1a2b1e; }
  .q-product-name--yours { color: #143d23; }
  .q-money { text-align: right; font-weight: 700; color: #143d23; }
  .q-money--big { font-size: 18px; font-weight: 800; }
  .q-total-label { font-weight: 700; color: #1a2b1e; }
  .q-total-label--right { text-align: right; font-weight: 700; color: #6b7c6e; }
  .q-muted { font-size: 13px; color: #6b7c6e; }
  .q-sku { font-size: 11px; color: #879a8c; font-family: 'SF Mono', ui-monospace, monospace; }
  .q-unit { font-weight: 500; font-size: 13px; color: #879a8c; }
  .q-notes { font-weight: 400; font-size: 14px; color: #4b5c50; line-height: 1.5; }
  .q-deferred-label { font-weight: 600; color: #7A6A3A; font-size: 13px; }
  .q-deferred-money { text-align: right; font-weight: 700; color: #7A6A3A; font-size: 14px; }
  .q-empty { text-align: center; padding: 40px; color: #879a8c; }
  .q-flash-text { font-size: 14px; color: #3f5c46; font-weight: 600; }
  .q-flash { background: #F4F8F4; border: 1px solid #D6E6D8; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
  .q-notice { border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px; }
  .q-notice-title { font-size: 13.5px; font-weight: 700; color: #6B5F2A; }
  .q-notice-text { font-size: 12.5px; color: #7A6E3F; line-height: 1.55; margin-top: 2px; }
  [data-theme="dark"] .q-notice { background: rgba(15, 23, 42, 0.72) !important; border-color: #334155 !important; }
  [data-theme="dark"] .q-notice-title { color: #e7dfbd; }
  [data-theme="dark"] .q-notice-text { color: #c9c2a4; }

  /* The status chip takes its colours from the model as variables, so the
     dark theme can repaint the whole chip in one place. */
  .q-status-badge { background: var(--badge-bg, #F3F5F0); color: var(--badge-fg, #4b5c50); }
</style>

<x-breadcrumb :items="[
  ['label' => 'Quotation Demand Trends', 'url' => route('store.quotations.index')],
  ['label' => $quotation->quotation_number],
]"/>

<div class="page-header-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
    <div>
      <h1 class="q-title">
        Cost Quotation {{ $quotation->quotation_number }}
      </h1>
      <p class="keep-sub q-subtitle">
        {{-- The farmer's name is masked here too: a store learns that the
             demand exists, not who created it. --}}
        Farmer: <strong>{{ $quotation->farmer?->maskedName() ?? 'Axxx' }}</strong> — includes products from your store
      </p>
    </div>
    @php $badgeStyle = '--badge-bg:' . $quotation->status_bg . ';--badge-fg:' . $quotation->status_color . ';'; @endphp
    <span class="badge q-status-badge" style="{{ $badgeStyle }} padding: 7px 16px; border-radius: 24px; font-size: 13px; font-weight: 700;">
      <span style="width:8px;height:8px;border-radius:50%;display:inline-block;background:currentColor;"></span>
      {{ $quotation->status_label }}
    </span>
  </div>
</div>

@if(session('success'))
  <div class="q-flash">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(100,130,104,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#648268" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span class="q-flash-text">{{ session('success') }}</span>
  </div>
@endif

{{-- An expired estimate is history, not a price list. The numbers below are
     the ones the farmer was given; the shelf may have moved since. --}}
@if($quotation->is_expired)
  <div class="q-notice" style="background: #FAF8F0; border: 1px solid #E8E2CE;">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(148,163,184,0.14); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#8A7C3E" stroke-width="2" fill="none"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
    </div>
    <div>
      <div class="q-notice-title">This estimate expired on {{ $quotation->valid_until->format('M d, Y') }}</div>
      <div class="q-notice-text">The prices below are the ones the farmer was given and planned around. They are kept as they were quoted &mdash; the shelf prices may have moved since.</div>
    </div>
  </div>
@endif

<!-- Farmer Quotation Details -->
<div class="detail-card">
  <div class="detail-card-header">
    <h2 class="q-heading">Farmer Quotation Details</h2>
  </div>
  <div class="detail-card-body">
    <div class="info-grid">
      <div class="info-item">
        <label>Crop Type</label>
        <div class="value" style="text-transform: capitalize;">{{ $quotation->crop_type }}</div>
      </div>
      <div class="info-item">
        <label>Season</label>
        <div class="value" style="text-transform: capitalize; display: flex; align-items: center; gap: 8px;">
          @if($quotation->season === 'wet')
            <span style="width: 10px; height: 10px; background: #7f9dae; border-radius: 50%; flex-shrink: 0;"></span> Wet Season
          @else
            <span style="width: 10px; height: 10px; background: #bfa262; border-radius: 50%; flex-shrink: 0;"></span> Dry Season
          @endif
        </div>
      </div>
      <div class="info-item">
        <label>Land Area</label>
        <div class="value">{{ number_format($quotation->land_area, 2) }} <span class="q-unit">hectares</span></div>
      </div>
      <div class="info-item">
        <label>Generated On</label>
        <div class="value">{{ $quotation->created_at->format('M d, Y \a\t h:i A') }}</div>
      </div>
      @if($quotation->notes)
      <div class="info-item" style="grid-column: 1 / -1;">
        <label>Farmer Notes</label>
        <div class="value q-notes">{{ $quotation->notes }}</div>
      </div>
      @endif
    </div>
  </div>
</div>

<!-- Your Products in This Quotation -->
<x-table-toolbar
    uid="storeShowMine"
    work-table="storeShowMineTable"
    per-page="10"
    noun="products"
    search-placeholder="Search your products…"
  />
<div class="detail-card">
  <div class="detail-card-header">
    <div>
      <h2 class="q-heading">Your Products in This Quotation</h2>
      @php
        $sFitted = $storeItems->where('is_deferred', false);
      @endphp
      <p class="q-note">
        {{ $sFitted->count() }} of your product(s) were included
        @if($storeItems->count() !== $sFitted->count())
          &middot; {{ $storeItems->count() - $sFitted->count() }} deferred to fit the farmer&rsquo;s budget
        @endif
      </p>
    </div>
  </div>
  <div style="padding: 0;">
    @if($storeItems->count() > 0)
      <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
        <div class="app-scroll">
          <table id="storeShowMineTable" class="data-table quotation-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Your Price</th>
              <th>Quantity Needed</th>
              <th style="text-align: right;">Est. Value</th>
            </tr>
          </thead>
          <tbody>
            @foreach($storeItems as $item)
              <tr @if($item->is_deferred) style="opacity: 0.62;" @endif>
                <td>
                  <div class="q-product-name">
                    {{ $item->product_name }}
                    @if($item->is_deferred)
                      <span class="your-product-tag your-product-tag--deferred" style="margin-left: 7px;">DEFERRED</span>
                    @endif
                  </div>
                  @if($item->product)
                    <div class="q-sku">SKU: {{ $item->product->sku }}</div>
                  @endif
                </td>
                <td>₱{{ number_format($item->price_at_generation, 2) }}</td>
                <td><span style="font-weight: 600;">{{ number_format($item->calculated_quantity, 2) }}</span></td>
                <td class="q-money">
                  ₱{{ number_format($item->subtotal, 2) }}
                </td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="quotation-total-row">
              <td colspan="3" class="q-total-label">Total from Your Store</td>
              <td class="q-money q-money--big">
                ₱{{ number_format((float) $sFitted->sum('subtotal'), 2) }}
              </td>
            </tr>
            @php
              $sDeferred = $storeItems->where('is_deferred', true);
            @endphp
            @if($sDeferred->isNotEmpty())
              <tr class="quotation-total-row quotation-total-row--deferred">
                <td colspan="3" class="q-deferred-label">Deferred to fit the farmer&rsquo;s budget &mdash; kept as demand signal, not in the current plan</td>
                <td class="q-deferred-money">
                  ₱{{ number_format((float) $sDeferred->sum('subtotal'), 2) }}
                </td>
              </tr>
            @endif
          </tfoot>
          </table>
        </div>
        <div class="app-scrollbar">
          <div class="app-scrollbar-track"></div>
          <div class="app-scrollbar-thumb"></div>
        </div>
      </div>
    @else
      <div class="q-empty">
        <p style="margin: 0;">No items from your store in this quotation.</p>
      </div>
    @endif
  </div>
</div>

<!-- Full Quotation Breakdown -->
<x-table-toolbar
    uid="storeShowAll"
    work-table="storeShowAllTable"
    per-page="10"
    noun="items"
    search-placeholder="Search product or store…"
  />
<div class="detail-card">
  <div class="detail-card-header">
    <h2 class="q-heading">Full Cost Breakdown</h2>
    <div class="total-badge">
      <div class="total-label">Farmer's Total</div>
      <div class="total-value">₱{{ number_format($quotation->estimated_total_cost, 2) }}</div>
    </div>
  </div>
  <div style="padding: 0;">
    @if($quotation->items->count() > 0)
      <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
        <div class="app-scroll">
          <table id="storeShowAllTable" class="data-table quotation-table quotation-table--wide">
          <thead>
            <tr>
              <th>Product</th>
              <th>Store</th>
              <th>Price</th>
              <th>Quantity</th>
              <th style="text-align: right;">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @foreach($quotation->items as $item)
              @php $isYours = $item->product && $item->product->user_id === Auth::id(); @endphp
              <tr class="{{ $isYours ? 'yours' : '' }}" @if($item->is_deferred) style="opacity: 0.62;" @endif>
                <td>
                  <div class="q-product-name{{ $isYours ? ' q-product-name--yours' : '' }}">
                    {{ $item->product_name }}
                    @if($isYours)
                      <span class="your-product-tag">YOUR PRODUCT</span>
                    @endif
                    @if($item->is_deferred)
                      <span class="your-product-tag your-product-tag--deferred">DEFERRED</span>
                    @endif
                  </div>
                </td>
                <td class="q-muted">
                  {{ $item->product?->storeOwner?->name ?? '—' }}
                </td>
                <td>₱{{ number_format($item->price_at_generation, 2) }}</td>
                <td><span style="font-weight: 600;">{{ number_format($item->calculated_quantity, 2) }}</span></td>
                <td style="text-align: right; font-weight: 600;">₱{{ number_format($item->subtotal, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="quotation-total-row">
              <td colspan="4" class="q-total-label--right">Grand Total</td>
              <td class="q-money q-money--big">
                ₱{{ number_format($quotation->estimated_total_cost, 2) }}
              </td>
            </tr>
          </tfoot>
          </table>
        </div>
        <div class="app-scrollbar">
          <div class="app-scrollbar-track"></div>
          <div class="app-scrollbar-thumb"></div>
        </div>
      </div>
    @else
      <div class="q-empty">
        <p style="margin: 0;">No computed items in this quotation.</p>
      </div>
    @endif
  </div>
</div>

{{-- The quoted cost is fixed: it is the number the farmer was given and
     planned around, so it never changes. When the shelf price has moved
     since, the reader is told - at the bottom, where the totals are. --}}
@php $priceChanges = $quotation->priceChanges(); @endphp
@if($priceChanges !== [])
  <div class="detail-card" style="margin-top: 20px;">
    <div class="detail-card-header">
      <h2 class="q-heading">A price has changed since this estimate</h2>
    </div>
    <div class="detail-card-body">
      <p class="q-muted" style="margin: 0 0 12px 0; line-height: 1.6;">
        The estimate keeps the price it was made with &mdash; that is the cost the farmer planned around. Your
        current price for {{ count($priceChanges) === 1 ? 'that product is' : 'those products are' }} different:
      </p>
      <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
        <div class="app-scroll">
          <table class="data-table quotation-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Quoted price</th>
                <th style="text-align: right;">Your price now</th>
              </tr>
            </thead>
            <tbody>
              @foreach($priceChanges as $change)
                <tr>
                  <td>
                    <div class="q-product-name">{{ $change['name'] }}</div>
                  </td>
                  <td class="q-muted">₱{{ number_format($change['was'], 2) }}</td>
                  <td class="q-money" style="text-align: right;">₱{{ number_format($change['now'], 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="app-scrollbar">
          <div class="app-scrollbar-track"></div>
          <div class="app-scrollbar-thumb"></div>
        </div>
      </div>
    </div>
  </div>
@endif
@endsection
