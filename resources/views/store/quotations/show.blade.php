@extends('layouts.sidebar')

@section('title', 'Quotation Computation')

@section('content')
<style>
  .detail-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
  }
  .detail-card-header {
    padding: 18px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
  }
  .detail-card-body { padding: 20px; }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
  }
  .info-item label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; font-weight: 600; display: block; margin-bottom: 4px; }
  .info-item .value { font-size: 15px; font-weight: 700; color: #1e2922; }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
  }

  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th { background: #fafbfa; padding: 12px 14px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
  td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }

  .formula-chip {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .formula-chip strong { color: #143d23; }

  .override-input {
    width: 90px;
    padding: 6px 8px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 13px;
    text-align: right;
    transition: border-color 0.2s;
  }
  .override-input:focus {
    outline: none;
    border-color: #143d23;
    box-shadow: 0 0 0 2px rgba(20, 61, 35, 0.15);
  }

  .btn-primary {
    background: #143d23;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
  }
  .btn-primary:hover { background: #0d2917; }
  .btn-outline {
    background: transparent;
    color: #143d23;
    border: 1.5px solid #143d23;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
  }
  .btn-outline:hover { background: #f0fdf4; }
  .btn-success {
    background: #16a34a;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
  }
  .btn-success:hover { background: #15803d; }
  .btn-success:disabled { background: #86efac; cursor: not-allowed; }
</style>

<div class="header-panel" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">
      Quotation {{ $quotation->quotation_number }}
    </h1>
    <p style="color: #606d64; font-size: 14px; margin: 0;">
      Submitted by <strong>{{ $quotation->farmer?->name ?? 'Unknown' }}</strong>
    </p>
  </div>
  <div style="display: flex; gap: 8px; flex-wrap: wrap;">
    <span class="badge" style="background: {{ $quotation->status_bg }}; color: {{ $quotation->status_color }};">
      <span style="width:6px;height:6px;border-radius:50%;display:inline-block;background:{{ $quotation->status_color }};"></span>
      {{ $quotation->status_label }}
    </span>

    @if($quotation->status === 'computed' && $quotation->items->count() > 0)
      <form action="{{ route('store.quotations.dispatch', $quotation) }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn-success">
          <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="22 12 16 12 16 20 8 20 8 12 2 12 12 2 22 12"/></svg>
          Dispatch to Farmer
        </button>
      </form>
    @endif
  </div>
</div>

<!-- Quotation Info Card -->
<div class="detail-card">
  <div class="detail-card-header">
    <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin: 0;">Farmer Request Details</h2>
  </div>
  <div class="detail-card-body">
    <div class="info-grid">
      <div class="info-item">
        <label>Crop Type</label>
        <div class="value" style="text-transform: capitalize;">{{ $quotation->crop_type }}</div>
      </div>
      <div class="info-item">
        <label>Season</label>
        <div class="value" style="text-transform: capitalize;">{{ $quotation->season }}</div>
      </div>
      <div class="info-item">
        <label>Land Area</label>
        <div class="value">{{ number_format($quotation->land_area, 1) }} Hectares</div>
      </div>
      <div class="info-item">
        <label>Requested On</label>
        <div class="value">{{ $quotation->created_at->format('M d, Y \a\t h:i A') }}</div>
      </div>
      @if($quotation->notes)
      <div class="info-item" style="grid-column: 1 / -1;">
        <label>Farmer Notes</label>
        <div class="value" style="font-weight: 400; font-size: 14px; color: #475569;">{{ $quotation->notes }}</div>
      </div>
      @endif
    </div>
  </div>
</div>

@if($quotation->status === 'pending' && $quotation->store_user_id === null)
  <!-- Not yet claimed -->
  <div class="detail-card" style="text-align: center; padding: 48px 20px;">
    <svg viewBox="0 0 24 24" width="48" height="48" stroke="#f59e0b" stroke-width="1.5" fill="none" style="margin-bottom: 12px;">
      <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
    </svg>
    <h3 style="font-size: 18px; color: #1e2922; margin: 0 0 6px 0;">This quotation hasn't been claimed yet</h3>
    <p style="font-size: 14px; color: #64748b; margin: 0 0 20px 0;">Claim this request to compute pricing estimates for the farmer.</p>
    <form action="{{ route('store.quotations.claim', $quotation) }}" method="POST">
      @csrf
      <button type="submit" class="btn-primary">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        Claim This Quotation
      </button>
    </form>
  </div>

@elseif($quotation->status === 'pending' && $quotation->store_user_id === Auth::id())
  <!-- Claimed but not computed yet — Show formula selection -->
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin: 0;">Compute Cost Estimate</h2>
      <p style="font-size: 13px; color: #64748b; margin: 0;">Apply decision formulas based on {{ $quotation->crop_type }} — {{ $quotation->season }} season</p>
    </div>
    <div class="detail-card-body">
      @if($decisionFormulas->count() > 0)
        <form action="{{ route('store.quotations.compute', $quotation) }}" method="POST">
          @csrf

          <div style="margin-bottom: 16px;">
            <div class="info-grid">
              <div class="info-item">
                <label>Crop Type</label>
                <input type="text" name="crop_type" value="{{ $quotation->crop_type }}" class="override-input" style="width:100%;" readonly>
              </div>
              <div class="info-item">
                <label>Season</label>
                <select name="season" class="override-input" style="width:100%;">
                  <option value="wet" {{ $quotation->season === 'wet' ? 'selected' : '' }}>Wet Season</option>
                  <option value="dry" {{ $quotation->season === 'dry' ? 'selected' : '' }}>Dry Season</option>
                </select>
              </div>
              <div class="info-item">
                <label>Land Area (ha)</label>
                <input type="number" name="land_area" value="{{ $quotation->land_area }}" step="0.01" min="0.01" class="override-input" style="width:100%;">
              </div>
            </div>
          </div>

          <h3 style="font-size: 14px; font-weight: 700; color: #1e2922; margin: 0 0 12px 0;">Formula-Driven Items</h3>
          <p style="font-size: 12px; color: #64748b; margin: 0 0 16px 0;">
            These are auto-calculated from the Decision Support System formulas matched for <strong>{{ $quotation->crop_type }} — {{ $quotation->season }}</strong>.
            Review and adjust quantities as needed.
          </p>

          <div style="overflow-x: auto; margin-bottom: 20px;">
            <table>
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Rate / Hectare</th>
                  <th>Unit</th>
                  <th>Current Price</th>
                  <th>Calculated Quantity</th>
                  <th>Estimated Subtotal</th>
                </tr>
              </thead>
              <tbody>
                @php $totalEstimate = 0; @endphp
                @foreach($decisionFormulas as $formula)
                  @php
                    $calculatedQty = $formula->rate_per_hectare * $quotation->land_area;
                    $subtotal = $calculatedQty * ($formula->product->price ?? 0);
                    $totalEstimate += $subtotal;
                  @endphp
                  <tr>
                    <td>
                      <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 600;">{{ $formula->product->name ?? 'Unknown' }}</span>
                        @if($formula->notes)
                          <span style="font-size: 11px; color: #64748b; font-style: italic;">({{ $formula->notes }})</span>
                        @endif
                      </div>
                    </td>
                    <td>
                      <span class="formula-chip">
                        <strong>{{ number_format($formula->rate_per_hectare, 1) }}</strong>
                        <span style="color:#64748b;">{{ $formula->unit }}</span>
                      </span>
                    </td>
                    <td style="color: #64748b;">{{ $formula->unit }}</td>
                    <td>₱{{ number_format($formula->product->price ?? 0, 2) }}</td>
                    <td>
                      <input type="hidden" name="formula_items[{{ $loop->index }}][product_id]" value="{{ $formula->product_id }}">
                      <input type="number"
                             name="formula_items[{{ $loop->index }}][calculated_quantity]"
                             value="{{ number_format($calculatedQty, 2, '.', '') }}"
                             step="0.01"
                             min="0"
                             class="override-input"
                             oninput="updateSubtotal(this, {{ $loop->index }}, {{ $formula->product->price ?? 0 }})">
                    </td>
                    <td>
                      <span class="subtotal-display" id="subtotal-{{ $loop->index }}">
                        ₱{{ number_format($subtotal, 2) }}
                      </span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr style="background: #fafbfa;">
                  <td colspan="4"></td>
                  <td style="font-weight: 700; color: #1e2922;">Estimated Total</td>
                  <td style="font-weight: 800; color: #143d23; font-size: 16px;">
                    ₱{{ number_format($totalEstimate, 2) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <button type="submit" class="btn-primary">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
            Compute Cost Estimate
          </button>
        </form>
      @else
        <div style="text-align: center; padding: 32px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px;">
          <svg viewBox="0 0 24 24" width="36" height="36" stroke="#d97706" stroke-width="1.5" fill="none" style="margin-bottom: 8px;">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
          </svg>
          <h3 style="font-size: 15px; color: #92400e; margin: 0 0 4px 0;">No Decision Formulas Found</h3>
          <p style="font-size: 13px; color: #b45309; margin: 0;">
            No formulas defined for <strong>{{ $quotation->crop_type }}</strong> in <strong>{{ $quotation->season }}</strong> season.
            Please set up formulas in the Input Formulas section first.
          </p>
          <a href="{{ route('store.formulas.index') }}" class="btn-outline" style="margin-top: 16px; display: inline-flex;">
            Go to Formulas
          </a>
        </div>
      @endif
    </div>
  </div>

@elseif($quotation->status === 'computed' || $quotation->status === 'dispatched')
  <!-- Quotation items computed and ready -->
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin: 0;">Estimated Cost Breakdown</h2>
      @if($quotation->valid_until)
        <span style="font-size: 12px; color: #64748b;">Valid until {{ $quotation->valid_until->format('M d, Y') }}</span>
      @endif
    </div>
    <div class="detail-card-body" style="padding: 0;">
      @if($quotation->items->count() > 0)
        <div style="overflow-x: auto;">
          <table>
            <thead>
              <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Override Price</th>
                <th>Override Qty</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @foreach($quotation->items as $item)
                <tr>
                  <td style="font-weight: 600;">{{ $item->product_name }}</td>
                  <td>₱{{ number_format($item->price_at_generation, 2) }}</td>
                  <td>{{ number_format($item->calculated_quantity, 2) }}</td>
                  <td>
                    @if($quotation->status !== 'dispatched')
                      <form action="{{ route('store.quotations.update-item', $item) }}" method="POST" style="display: flex; align-items: center; gap: 4px;" class="override-form">
                        @csrf @method('PUT')
                        <input type="number" name="override_price" value="{{ $item->override_price ?? '' }}" step="0.01" min="0" placeholder="—" class="override-input">
                      </form>
                    @else
                      {{ $item->override_price ? '₱' . number_format($item->override_price, 2) : '—' }}
                    @endif
                  </td>
                  <td>
                    @if($quotation->status !== 'dispatched')
                      <form action="{{ route('store.quotations.update-item', $item) }}" method="POST" style="display: flex; align-items: center; gap: 4px;" class="override-form">
                        @csrf @method('PUT')
                        <input type="number" name="override_quantity" value="{{ $item->override_quantity ?? '' }}" step="0.01" min="0" placeholder="—" class="override-input">
                      </form>
                    @else
                      {{ $item->override_quantity ? number_format($item->override_quantity, 2) : '—' }}
                    @endif
                  </td>
                  <td style="font-weight: 700; color: #143d23;">
                    ₱{{ number_format($item->effective_subtotal, 2) }}
                  </td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr style="background: #fafbfa;">
                <td colspan="4"></td>
                <td style="font-weight: 700; color: #1e2922;">Total Estimate</td>
                <td style="font-weight: 800; color: #143d23; font-size: 18px;">
                  ₱{{ number_format($quotation->estimated_total_cost, 2) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        @if($quotation->status !== 'dispatched')
          <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 8px;">
            <form action="{{ route('store.quotations.dispatch', $quotation) }}" method="POST">
              @csrf
              <button type="submit" class="btn-success">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="22 12 16 12 16 20 8 20 8 12 2 12 12 2 22 12"/></svg>
                Dispatch Quotation
              </button>
            </form>
          </div>
        @endif
      @else
        <div style="text-align: center; padding: 48px 20px; color: #64748b;">
          <p>No computed items found. Recompute using formulas.</p>
        </div>
      @endif
    </div>
  </div>

  @if($quotation->store_notes)
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin: 0;">Store Notes</h2>
    </div>
    <div class="detail-card-body">
      <p style="font-size: 14px; color: #475569; margin: 0;">{{ $quotation->store_notes }}</p>
    </div>
  </div>
  @endif

@elseif(in_array($quotation->status, ['accepted', 'rejected']))
  <!-- Finalized state -->
  <div class="detail-card">
    <div class="detail-card-header">
      <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin: 0;">Final Cost Breakdown</h2>
    </div>
    <div class="detail-card-body">
      <div style="text-align: center; padding: 32px;">
        @if($quotation->status === 'accepted')
          <svg viewBox="0 0 24 24" width="48" height="48" stroke="#16a34a" stroke-width="1.5" fill="none" style="margin-bottom: 12px;">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
          <h3 style="font-size: 18px; color: #16a34a; margin: 0 0 4px 0;">Quotation Accepted</h3>
          <p style="font-size: 14px; color: #64748b;">The farmer has accepted this quotation.</p>
        @else
          <svg viewBox="0 0 24 24" width="48" height="48" stroke="#dc2626" stroke-width="1.5" fill="none" style="margin-bottom: 12px;">
            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
          <h3 style="font-size: 18px; color: #dc2626; margin: 0 0 4px 0;">Quotation Rejected</h3>
          <p style="font-size: 14px; color: #64748b;">The farmer has rejected this quotation.</p>
        @endif
      </div>
    </div>
  </div>
@endif

<script>
function updateSubtotal(input, index, unitPrice) {
  const qty = parseFloat(input.value) || 0;
  const subtotal = qty * unitPrice;
  const display = document.getElementById('subtotal-' + index);
  if (display) {
    display.textContent = '₱' + subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
}

// Auto-submit override forms on change (with debounce)
document.querySelectorAll('.override-form input').forEach(input => {
  let debounceTimer;
  input.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      this.closest('form').submit();
    }, 800);
  });
});
</script>
@endsection

