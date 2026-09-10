@extends('layouts.sidebar')

@section('title', 'Demand Intel — ' . $quotation->quotation_number)

@section('content')
<style>
  .page-header-card {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 50%, #eff6ff 100%);
    border: 1px solid #bfdbfe;
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

  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th {
    padding: 13px 16px; font-weight: 600; color: #6b7c6e;
    border-bottom: 1px solid #f0f2f1; text-align: left;
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em;
    background: #fafdfb;
  }
  td { padding: 14px 16px; border-bottom: 1px solid #f5f7f6; color: #374138; }
  tbody tr { transition: background 0.15s; }
  tbody tr:hover td { background: #f8faf9; }
  tr.yours td { background: #f0fdf4; }
  tr.yours td:first-child { box-shadow: inset 3px 0 0 #16a34a; }

  .your-product-tag {
    background: linear-gradient(135deg, #143d23, #1a5230);
    color: white; padding: 2px 8px; border-radius: 6px;
    font-size: 10px; font-weight: 700; margin-left: 8px;
    letter-spacing: 0.04em;
  }

  .total-badge {
    display: inline-flex; align-items: baseline; gap: 6px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 10px 18px;
  }
  .total-badge .total-label { font-size: 12px; font-weight: 600; color: #15803d; text-transform: uppercase; letter-spacing: 0.04em; }
  .total-badge .total-value { font-size: 22px; font-weight: 800; color: #166534; letter-spacing: -0.02em; }

  .info-banner {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border: 1px solid #bfdbfe;
    border-radius: 14px;
    padding: 18px 22px;
    display: flex; align-items: flex-start; gap: 14px;
    margin-top: 4px;
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'Demand Intelligence', 'url' => route('store.quotations.index')],
  ['label' => $quotation->quotation_number],
]"/>

<div class="page-header-card">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px;">
    <div>
      <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 6px 0; letter-spacing: -0.02em;">
        Cost Quotation {{ $quotation->quotation_number }}
      </h1>
      <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">
        Farmer: <strong>{{ $quotation->farmer?->name ?? 'Unknown' }}</strong> — Demand signal for your products
      </p>
    </div>
    @php $badgeStyle = 'background:' . $quotation->status_bg . ';color:' . $quotation->status_color . ';'; @endphp
    <span class="badge" style="{{ $badgeStyle }} padding: 7px 16px; border-radius: 24px; font-size: 13px; font-weight: 700;">
      <span style="width:8px;height:8px;border-radius:50%;display:inline-block;background:currentColor;"></span>
      {{ $quotation->status_label }}
    </span>
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

<!-- Farmer Quotation Details -->
<div class="detail-card">
  <div class="detail-card-header">
    <h2 style="font-size: 16px; font-weight: 700; color: #1a2b1e; margin: 0;">Farmer Quotation Details</h2>
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
            <span style="width: 10px; height: 10px; background: linear-gradient(135deg, #38bdf8, #0284c7); border-radius: 50%;"></span> Wet Season
          @else
            <span style="width: 10px; height: 10px; background: linear-gradient(135deg, #fbbf24, #d97706); border-radius: 50%;"></span> Dry Season
          @endif
        </div>
      </div>
      <div class="info-item">
        <label>Land Area</label>
        <div class="value">{{ number_format($quotation->land_area, 2) }} <span style="font-weight: 500; font-size: 13px; color: #879a8c;">hectares</span></div>
      </div>
      <div class="info-item">
        <label>Generated On</label>
        <div class="value">{{ $quotation->created_at->format('M d, Y \a\t h:i A') }}</div>
      </div>
      @if($quotation->notes)
      <div class="info-item" style="grid-column: 1 / -1;">
        <label>Farmer Notes</label>
        <div class="value" style="font-weight: 400; font-size: 14px; color: #4b5c50; line-height: 1.5;">{{ $quotation->notes }}</div>
      </div>
      @endif
    </div>
  </div>
</div>

<!-- Your Products in This Quotation -->
<div class="detail-card">
  <div class="detail-card-header">
    <div>
      <h2 style="font-size: 16px; font-weight: 700; color: #1a2b1e; margin: 0;">Your Products in This Quotation</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">{{ $storeItems->count() }} of your product(s) were included</p>
    </div>
  </div>
  <div style="padding: 0;">
    @if($storeItems->count() > 0)
      <table>
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
            <tr>
              <td>
                <div style="font-weight: 600; color: #1a2b1e;">{{ $item->product_name }}</div>
                @if($item->product)
                  <div style="font-size: 11px; color: #b0b8b3; font-family: 'SF Mono', ui-monospace, monospace;">SKU: {{ $item->product->sku }}</div>
                @endif
              </td>
              <td>₱{{ number_format($item->price_at_generation, 2) }}</td>
              <td><span style="font-weight: 600;">{{ number_format($item->calculated_quantity, 2) }}</span></td>
              <td style="text-align: right; font-weight: 700; color: #143d23;">
                ₱{{ number_format($item->subtotal, 2) }}
              </td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr style="background: #fafdfb;">
            <td colspan="3" style="font-weight: 700; color: #1a2b1e;">Total from Your Store</td>
            <td style="text-align: right; font-weight: 800; color: #143d23; font-size: 18px;">
              ₱{{ number_format($storeItems->sum('subtotal'), 2) }}
            </td>
          </tr>
        </tfoot>
      </table>
    @else
      <div style="text-align: center; padding: 40px; color: #879a8c;">
        <p style="margin: 0;">No items from your store in this quotation.</p>
      </div>
    @endif
  </div>
</div>

<!-- Full Quotation Breakdown -->
<div class="detail-card">
  <div class="detail-card-header">
    <h2 style="font-size: 16px; font-weight: 700; color: #1a2b1e; margin: 0;">Full Cost Breakdown</h2>
    <div class="total-badge">
      <div class="total-label">Farmer's Total</div>
      <div class="total-value">₱{{ number_format($quotation->estimated_total_cost, 2) }}</div>
    </div>
  </div>
  <div style="padding: 0;">
    @if($quotation->items->count() > 0)
      <table>
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
            <tr class="{{ $isYours ? 'yours' : '' }}">
              <td>
                <div style="font-weight: 600; {{ $isYours ? 'color: #143d23;' : '' }}">
                  {{ $item->product_name }}
                  @if($isYours)
                    <span class="your-product-tag">YOUR PRODUCT</span>
                  @endif
                </div>
              </td>
              <td style="font-size: 13px; color: #6b7c6e;">
                {{ $item->product?->storeOwner?->name ?? '—' }}
              </td>
              <td>₱{{ number_format($item->price_at_generation, 2) }}</td>
              <td><span style="font-weight: 600;">{{ number_format($item->calculated_quantity, 2) }}</span></td>
              <td style="text-align: right; font-weight: 600;">₱{{ number_format($item->subtotal, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr style="background: #fafdfb;">
            <td colspan="4" style="text-align: right; font-weight: 700; color: #6b7c6e;">Grand Total</td>
            <td style="text-align: right; font-weight: 800; color: #143d23; font-size: 18px;">
              ₱{{ number_format($quotation->estimated_total_cost, 2) }}
            </td>
          </tr>
        </tfoot>
      </table>
    @else
      <div style="text-align: center; padding: 40px; color: #879a8c;">
        <p style="margin: 0;">No computed items in this quotation.</p>
      </div>
    @endif
  </div>
</div>

<!-- Info Banner -->
<div class="info-banner">
  <div style="width: 32px; height: 32px; border-radius: 10px; background: rgba(37,99,235,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 2px;">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#2563eb" stroke-width="2" fill="none">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
  </div>
  <div>
    <div style="font-weight: 700; font-size: 14px; color: #1e40af; margin-bottom: 4px;">Demand Intelligence — Read Only</div>
    <p style="margin: 0; font-size: 13px; color: #1e40af; line-height: 1.6;">
      This quotation was auto-generated by the farmer. It shows what products and quantities farmers in your area are exploring.
      Use this data to understand demand patterns and plan your inventory accordingly. No action is required on your part.
    </p>
  </div>
</div>
@endsection
