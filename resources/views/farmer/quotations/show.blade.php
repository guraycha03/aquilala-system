@extends('layouts.sidebar')

@section('title', 'Cost Quotation ' . $quotation->quotation_number)

@section('content')
<style>
  .page-header-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f0fdf4 100%);
    border: 1px solid #bbf7d0;
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
  }
  .detail-card-body { padding: 24px; }

  .info-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.06em; color: #879a8c; margin-bottom: 4px;
  }
  .info-value { font-weight: 700; font-size: 15px; color: #1a2b1e; }

  .status-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 7px 16px; border-radius: 24px;
    font-size: 13px; font-weight: 700;
    letter-spacing: 0.01em;
  }
  .status-pill.computed { background: #eff6ff; color: #1d4ed8; }
  .status-pill.viewed   { background: #f5f3ff; color: #6d28d9; }
  .status-pill.expired  { background: #f5f5f5; color: #9ca3af; }
  .status-pill.pending  { background: #fffbeb; color: #b45309; }
  .status-pill.accepted { background: #f0fdf4; color: #15803d; }
  .status-pill .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

  .items-table { width: 100%; border-collapse: collapse; font-size: 14px; }
  .items-table th {
    padding: 13px 16px; font-weight: 600;
    color: #6b7c6e; border-bottom: 1px solid #f0f2f1; text-align: left;
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em;
    background: #fafdfb;
  }
  .items-table td { padding: 14px 16px; border-bottom: 1px solid #f5f7f6; color: #374138; }
  .items-table tbody tr { transition: background 0.15s; }
  .items-table tbody tr:hover td { background: #f8faf9; }

  .delete-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; background: #fef2f2; border: 1px solid #fecaca;
    border-radius: 10px; color: #dc2626; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all 0.15s;
  }
  .delete-btn:hover { background: #fee2e2; border-color: #fca5a5; transform: translateY(-1px); }
  .delete-btn:active { transform: scale(0.98); }

  .total-badge {
    display: inline-flex; align-items: baseline; gap: 6px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 10px 18px;
  }
  .total-badge .total-label { font-size: 12px; font-weight: 600; color: #15803d; text-transform: uppercase; letter-spacing: 0.04em; }
  .total-badge .total-value { font-size: 24px; font-weight: 800; color: #166534; letter-spacing: -0.02em; }

  .store-chip {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; border-radius: 10px;
    border: 1px solid #f0f2f1;
    transition: all 0.15s;
  }
  .store-chip:hover { background: #f8faf9; border-color: #e5e7eb; }

  .info-banner {
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border: 1px solid #bbf7d0;
    border-radius: 14px;
    padding: 18px 22px;
    display: flex; align-items: flex-start; gap: 14px;
    margin-top: 4px;
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'My Cost Quotations', 'url' => route('farmer.quotations.index')],
  ['label' => $quotation->quotation_number],
]"/>

<div class="page-header-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
    <div>
      <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 6px 0; letter-spacing: -0.02em;">
        Cost Quotation {{ $quotation->quotation_number }}
      </h1>
      <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">
        Generated on {{ $quotation->created_at->format('F d, Y \a\t g:i A') }}
      </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
      <span class="status-pill {{ $quotation->status }}">
        <span class="dot"></span>
        {{ $quotation->status_label }}
      </span>
      <form action="{{ route('farmer.quotations.destroy', $quotation) }}" method="POST"
            onsubmit="return confirm('Are you sure you want to delete this cost quotation? This cannot be undone.');"
            style="margin: 0;">
        @csrf
        @method('DELETE')
        <button type="submit" class="delete-btn">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          Delete
        </button>
      </form>
    </div>
  </div>
</div>

@if(session('success'))
  <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(22,163,74,0.06);">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(22,163,74,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#16a34a" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span style="font-size: 14px; color: #166534; font-weight: 600;">{{ session('success') }}</span>
  </div>
@endif

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <!-- Farming Plan Details -->
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Farming Plan Details</h2>
    </div>
    <div class="detail-card-body">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
          <div class="info-label">Crop Type</div>
          <div class="info-value" style="text-transform: capitalize;">{{ $quotation->crop_type }}</div>
        </div>
        <div>
          <div class="info-label">Season</div>
          <div class="info-value" style="display: flex; align-items: center; gap: 8px;">
            @if($quotation->season === 'wet')
              <span style="width: 10px; height: 10px; background: linear-gradient(135deg, #38bdf8, #0284c7); border-radius: 50%;"></span> Wet Season
            @else
              <span style="width: 10px; height: 10px; background: linear-gradient(135deg, #fbbf24, #d97706); border-radius: 50%;"></span> Dry Season
            @endif
          </div>
        </div>
        <div>
          <div class="info-label">Land Area</div>
          <div class="info-value">{{ number_format($quotation->land_area, 2) }} <span style="font-weight: 500; font-size: 13px; color: #879a8c;">hectares</span></div>
        </div>
        <div>
          <div class="info-label">Generated On</div>
          <div class="info-value" style="font-weight: 600;">{{ $quotation->created_at->format('M d, Y') }}</div>
        </div>
      </div>
      @if($quotation->notes)
        <div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #f0f2f1;">
          <div class="info-label">Notes</div>
          <p style="margin: 4px 0 0 0; font-size: 14px; color: #374138; line-height: 1.5;">{{ $quotation->notes }}</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Stores Involved -->
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Supply Stores</h2>
    </div>
    <div class="detail-card-body">
      @php
        $storeIds = $quotation->items->pluck('product.storeOwner')->filter()->unique('id');
      @endphp
      @if($storeIds->count() > 0)
        @foreach($storeIds as $store)
          <div class="store-chip" style="{{ !$loop->last ? 'margin-bottom: 10px;' : '' }}">
            <div style="width: 42px; height: 42px; background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; border: 1px solid #bfdbfe;">
              @php $w = explode(' ', trim($store->name)); echo count($w) >= 2 ? mb_substr($w[0],0,1).mb_substr(end($w),0,1) : mb_substr($store->name,0,2); @endphp
            </div>
            <div>
              <div style="font-weight: 700; color: #1a2b1e; font-size: 14px;">{{ $store->name }}</div>
              <div style="font-size: 12px; color: #879a8c;">{{ $quotation->items->where('product.user_id', $store->id)->count() }} product(s) from this store</div>
            </div>
          </div>
        @endforeach
      @else
        <p style="color: #94a3b8; font-size: 14px; margin: 0;">Products from the general catalog were used.</p>
      @endif
    </div>
  </div>
</div>

<!-- Cost Breakdown -->
<div class="detail-card">
  <div class="detail-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0;">Cost Breakdown</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Auto-calculated via Decision Support formulas</p>
    </div>
    @if($quotation->estimated_total_cost > 0)
      <div class="total-badge">
        <div class="total-label">Total</div>
        <div class="total-value">₱{{ number_format($quotation->estimated_total_cost, 2) }}</div>
      </div>
    @endif
  </div>
  <div style="padding: 0;">
    @if($quotation->items->count() > 0)
      <table class="items-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Store</th>
            <th>Price per Unit</th>
            <th>Quantity</th>
            <th style="text-align: right;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          @foreach($quotation->items as $item)
            <tr>
              <td>
                <div style="font-weight: 600; color: #1a2b1e;">{{ $item->product_name }}</div>
                @if($item->product)
                  <div style="font-size: 11px; color: #b0b8b3; font-family: 'SF Mono', ui-monospace, monospace;">SKU: {{ $item->product->sku }}</div>
                @endif
              </td>
              <td style="font-size: 13px; color: #6b7c6e;">
                @if($item->product && $item->product->storeOwner)
                  {{ $item->product->storeOwner->name }}
                @else
                  <span style="color: #d1d9d3;">—</span>
                @endif
              </td>
              <td>₱{{ number_format($item->price_at_generation, 2) }}</td>
              <td><span style="font-weight: 600;">{{ number_format($item->calculated_quantity, 2) }}</span></td>
              <td style="text-align: right; font-weight: 700; color: #1a2b1e;">₱{{ number_format($item->subtotal, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" style="text-align: right; padding: 16px; font-weight: 700; color: #6b7c6e; border-bottom: none; background: #fafdfb;">Estimated Total</td>
            <td style="text-align: right; padding: 16px; font-weight: 800; color: #143d23; font-size: 18px; border-bottom: none; background: #fafdfb;">
              ₱{{ number_format($quotation->estimated_total_cost, 2) }}
            </td>
          </tr>
        </tfoot>
      </table>
    @else
      <div style="text-align: center; padding: 48px 24px;">
        <svg viewBox="0 0 24 24" width="40" height="40" stroke="#d1d9d3" stroke-width="1.5" fill="none" style="margin-bottom: 12px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <p style="color: #879a8c; font-size: 14px; margin: 0;">No products matched the formulas for this crop and season.</p>
      </div>
    @endif
  </div>
  @if($quotation->valid_until)
    <div style="padding: 14px 24px; background: #fafdfb; border-top: 1px solid #f0f2f1; display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
      <span style="color: #6b7c6e; display: flex; align-items: center; gap: 6px;">
        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        Valid until {{ $quotation->valid_until->format('F d, Y') }}
        @if($quotation->is_expired)
          <span style="color: #dc2626; font-weight: 600; background: #fef2f2; padding: 2px 8px; border-radius: 6px; font-size: 11px;">Expired</span>
        @endif
      </span>
      <span style="color: #b0b8b3; font-size: 12px;">Based on current product prices</span>
    </div>
  @endif
</div>

<!-- Info Banner -->
<div class="info-banner">
  <div style="width: 32px; height: 32px; border-radius: 10px; background: rgba(22,163,74,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#16a34a" stroke-width="2" fill="none">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
  </div>
  <div>
    <div style="font-weight: 700; font-size: 14px; color: #166534; margin-bottom: 4px;">This is a cost quotation, not a purchase order</div>
    <p style="margin: 0; font-size: 13px; color: #15803d; line-height: 1.6;">
      This cost breakdown was auto-generated using current product prices and standard farming formulas.
      Store owners can see this as demand intelligence. Contact the store directly to finalize any purchase.
    </p>
  </div>
</div>
@endsection
