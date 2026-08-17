@extends('layouts.sidebar')

@section('title', 'Quotation ' . $quotation->quotation_number)

@section('content')
<style>
  .status-badge-lg {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 16px; border-radius: 24px;
    font-size: 14px; font-weight: 700;
  }
  .status-badge-lg.pending { background: #fef3c7; color: #d97706; }
  .status-badge-lg.computed { background: #e0f2fe; color: #0284c7; }
  .status-badge-lg.dispatched { background: #f3e8ff; color: #7c3aed; }
  .status-badge-lg.accepted { background: #f0fdf4; color: #16a34a; }
  .status-badge-lg.rejected { background: #fef2f2; color: #dc2626; }

  .detail-card {
    background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px;
    overflow: hidden;
  }
  .detail-card-header {
    padding: 18px 24px; border-bottom: 1px solid #e5e7eb;
  }
  .detail-card-body { padding: 24px; }

  .items-table { width: 100%; border-collapse: collapse; font-size: 14px; }
  .items-table th {
    background: #fafbfa; padding: 12px 14px; font-weight: 600;
    color: #4a574e; border-bottom: 1px solid #e2e8f0; text-align: left;
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;
  }
  .items-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; color: #3d4a41; }

  .btn-action {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 20px; border-radius: 8px;
    font-size: 14px; font-weight: 700; border: none; cursor: pointer;
    transition: all 0.2s; text-decoration: none;
  }
  .btn-accept { background: #16a34a; color: #fff; }
  .btn-accept:hover { background: #15803d; }
  .btn-reject { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
  .btn-reject:hover { background: #fee2e2; }
</style>

<div class="header-panel" style="margin-bottom: 24px;">
  <nav style="font-size: 13px; color: #87a992; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
    <a href="{{ route('farmer.quotations.index') }}" style="color: #87a992; text-decoration: none;">My Quotations</a>
    <span>›</span>
    <span style="color: #4a574e;">{{ $quotation->quotation_number }}</span>
  </nav>
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">
        Quotation {{ $quotation->quotation_number }}
      </h1>
      <p style="color: #606d64; font-size: 14px; margin: 0;">
        Requested on {{ $quotation->created_at->format('F d, Y') }}
      </p>
    </div>
    <span class="status-badge-lg {{ $quotation->status }}">
      <span style="width: 8px; height: 8px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
      {{ $quotation->status_label }}
    </span>
  </div>
</div>

@if(session('success'))
  <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#16a34a" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    <span style="font-size: 14px; color: #166534; font-weight: 500;">{{ session('success') }}</span>
  </div>
@endif

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <!-- Store Info -->
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 15px; font-weight: 700; color: #1e2922; margin: 0;">Supply Store</h2>
    </div>
    <div class="detail-card-body">
      @if($quotation->storeOwner)
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
          <div style="width: 44px; height: 44px; background: #dbeafe; color: #1e40af; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0;">
            @php $w = explode(' ', trim($quotation->storeOwner->name)); echo count($w) >= 2 ? mb_substr($w[0],0,1).mb_substr(end($w),0,1) : mb_substr($quotation->storeOwner->name,0,2); @endphp
          </div>
          <div>
            <div style="font-weight: 700; color: #1e2922;">{{ $quotation->storeOwner->name }}</div>
            <div style="font-size: 13px; color: #64748b;">{{ $quotation->storeOwner->email }}</div>
          </div>
        </div>
      @else
        <p style="color: #94a3b8; font-size: 14px; margin: 0;">No store has been assigned yet. Your request is in the pool.</p>
      @endif
    </div>
  </div>

  <!-- Farming Plan Details -->
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 15px; font-weight: 700; color: #1e2922; margin: 0;">Farming Plan Details</h2>
    </div>
    <div class="detail-card-body">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div>
          <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px;">Crop Type</div>
          <div style="font-weight: 700; font-size: 15px; color: #1e2922;">{{ $quotation->crop_type }}</div>
        </div>
        <div>
          <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px;">Season</div>
          <div style="font-weight: 600; font-size: 14px; color: #1e2922; display: flex; align-items: center; gap: 6px;">
            @if($quotation->season === 'wet')
              <span style="display: inline-block; width: 8px; height: 8px; background: #0284c7; border-radius: 50%;"></span> Wet Season
            @else
              <span style="display: inline-block; width: 8px; height: 8px; background: #d97706; border-radius: 50%;"></span> Dry Season
            @endif
          </div>
        </div>
        <div>
          <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px;">Land Area</div>
          <div style="font-weight: 700; font-size: 15px; color: #1e2922;">{{ number_format($quotation->land_area, 2) }} Hectares</div>
        </div>
        <div>
          <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px;">Request Date</div>
          <div style="font-weight: 600; font-size: 14px; color: #1e2922;">{{ $quotation->created_at->format('M d, Y') }}</div>
        </div>
      </div>
      @if($quotation->notes)
        <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
          <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 4px;">Farmer's Notes</div>
          <p style="margin: 0; font-size: 14px; color: #1e2922;">{{ $quotation->notes }}</p>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Items Table -->
<div class="detail-card" style="margin-bottom: 24px;">
  <div class="detail-card-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
      <h2 style="font-size: 15px; font-weight: 700; color: #1e2922; margin: 0;">Products Requested</h2>
      <p style="font-size: 13px; color: #64748b; margin: 2px 0 0 0;">{{ $quotation->items->count() }} product(s) in this quotation</p>
    </div>
    @if($quotation->estimated_total_cost > 0)
      <div style="text-align: right;">
        <div style="font-size: 12px; color: #64748b; font-weight: 600;">Total Estimated Cost</div>
        <div style="font-size: 24px; font-weight: 800; color: #143d23;">₱{{ number_format($quotation->estimated_total_cost, 2) }}</div>
      </div>
    @endif
  </div>
  <div class="detail-card-body" style="padding: 0;">
    @if($quotation->items->count() > 0)
      <table class="items-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th style="text-align: right;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          @foreach($quotation->items as $item)
            <tr>
              <td>
                <div style="font-weight: 600; color: #1e2922;">{{ $item->product_name }}</div>
                @if($item->product)
                  <div style="font-size: 11px; color: #94a3b8;">SKU: {{ $item->product->sku }}</div>
                @endif
              </td>
              <td>₱{{ number_format($item->price_at_generation, 2) }}</td>
              <td>{{ $item->calculated_quantity }}</td>
              <td style="text-align: right; font-weight: 600;">₱{{ number_format($item->subtotal, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3" style="text-align: right; padding: 14px; font-weight: 700; color: #1e2922; border-bottom: none;">Grand Total</td>
            <td style="text-align: right; padding: 14px; font-weight: 800; color: #143d23; font-size: 18px; border-bottom: none;">
              ₱{{ number_format($quotation->estimated_total_cost, 2) }}
            </td>
          </tr>
        </tfoot>
      </table>
    @else
      <div style="text-align: center; padding: 40px;">
        <p style="color: #94a3b8; font-size: 14px; margin: 0;">No items have been added to this quotation yet.</p>
      </div>
    @endif
  </div>
  @if($quotation->valid_until)
    <div style="padding: 12px 24px; background: #fafbfa; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
      <span style="color: #64748b;">
        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" style="display: inline; vertical-align: middle; margin-right: 4px;">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        Valid until {{ $quotation->valid_until->format('F d, Y') }}
        @if($quotation->is_expired)
          <span style="color: #dc2626; font-weight: 600;"> (Expired)</span>
        @endif
      </span>
    </div>
  @endif
</div>

<!-- Actions -->
@if($quotation->status === 'dispatched')
  <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
    <div>
      <h3 style="font-size: 15px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Respond to Quotation</h3>
      <p style="font-size: 13px; color: #64748b; margin: 0;">Accept to proceed with purchase, or reject if the pricing doesn't work for you.</p>
    </div>
    <div style="display: flex; gap: 12px;">
      <form action="{{ route('farmer.quotations.reject', $quotation) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this quotation?')">
        @csrf
        <button type="submit" class="btn-action btn-reject">
          <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          Reject
        </button>
      </form>
      <form action="{{ route('farmer.quotations.accept', $quotation) }}" method="POST">
        @csrf
        <button type="submit" class="btn-action btn-accept">
          <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
          Accept Quotation
        </button>
      </form>
    </div>
  </div>
@endif
@endsection

