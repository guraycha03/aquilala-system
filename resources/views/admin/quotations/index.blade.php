@extends('layouts.sidebar')

@section('title', 'Cost Quotation Analytics')

@section('content')
<style>
  .stat-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
  }
  .stat-value { font-size: 28px; font-weight: 800; color: #143d23; line-height: 1; }
  .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 500; }

  .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th { background: #fafbfa; padding: 14px 16px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
  td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }

  .badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
  }
  .badge-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
  .avatar {
    width: 28px; height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 10px;
    flex-shrink: 0;
  }
  .demand-bar {
    height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; margin-top: 4px;
  }
  .demand-bar-fill {
    height: 100%; background: #143d23; border-radius: 3px; transition: width 0.3s;
  }
</style>

<div class="header-panel" style="margin-bottom: 24px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Quotation Analytics</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">System-wide demand visibility — farmer cost quotations, store performance, and crop trends.</p>
</div>

<!-- System-wide metrics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px;">
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['total'] }}</div>
      <div class="stat-label">Total Estimates</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#143d23" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['computed'] }}</div>
      <div class="stat-label">Fresh Estimates</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#0284c7" stroke-width="1.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['viewed'] }}</div>
      <div class="stat-label">Viewed by Stores</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#7c3aed" stroke-width="1.5" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['expired'] }}</div>
      <div class="stat-label">Expired</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#94a3b8" stroke-width="1.5" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">₱{{ number_format($metrics['totalValue'], 0) }}</div>
      <div class="stat-label">Total Demand Value</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#16a34a" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="3" x2="8" y2="21"/><path d="M8 3h6.5a4.5 4.5 0 0 1 0 9H8"/><line x1="4.5" y1="6.5" x2="21" y2="6.5" stroke-width="1.5"/><line x1="4.5" y1="9.5" x2="21" y2="9.5" stroke-width="1.5"/></svg>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <!-- Store Performance -->
  <div class="data-table-container" style="margin-bottom: 0;">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Store Performance</h2>
      <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Which stores' products farmers are exploring most</p>
    </div>
    <div style="padding: 16px 20px;">
      @if(count($storePerformance) > 0)
        @php $maxQ = max(array_column($storePerformance, 'quotation_count')); @endphp
        @foreach($storePerformance as $store)
          <div style="{{ !$loop->last ? 'margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;' : '' }}">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
              <div style="display: flex; align-items: center; gap: 8px;">
                <div class="avatar" style="background: #fef3c7; color: #92400e; width: 28px; height: 28px;">
                  @php $w = explode(' ', trim($store['store_name'])); echo count($w) >= 2 ? mb_substr($w[0], 0, 1).mb_substr(end($w), 0, 1) : mb_substr($store['store_name'], 0, 2); @endphp
                </div>
                <span style="font-weight: 600; color: #1e2922; font-size: 14px;">{{ $store['store_name'] }}</span>
              </div>
              <span style="font-size: 13px; color: #64748b;">{{ $store['quotation_count'] }} quotation(s)</span>
            </div>
            <div class="demand-bar">
              <div class="demand-bar-fill" style="width: {{ ($store['quotation_count'] / $maxQ) * 100 }}%;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 4px; font-size: 12px; color: #94a3b8;">
              <span>{{ $store['product_count'] }} unique product(s)</span>
              <span>₱{{ number_format($store['total_value'], 0) }} value</span>
            </div>
          </div>
        @endforeach
      @else
        <div style="text-align: center; padding: 32px; color: #94a3b8;">
          <p style="margin: 0;">No store demand data yet.</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Crop Demand Breakdown -->
  <div class="data-table-container" style="margin-bottom: 0;">
    <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Crop Demand Breakdown</h2>
      <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Which crops farmers are planning for</p>
    </div>
    <div style="padding: 16px 20px;">
      @if(count($cropDemand) > 0)
        @php $maxCrop = max(array_column($cropDemand, 'count')); @endphp
        @foreach($cropDemand as $crop)
          <div style="{{ !$loop->last ? 'margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;' : '' }}">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
              <span style="font-weight: 600; color: #1e2922; font-size: 14px; text-transform: capitalize;">{{ $crop['crop'] ?? 'Unspecified' }}</span>
              <span style="font-size: 13px; color: #64748b;">{{ $crop['count'] }} estimate(s)</span>
            </div>
            <div class="demand-bar">
              <div class="demand-bar-fill" style="width: {{ ($crop['count'] / $maxCrop) * 100 }}%;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 4px; font-size: 12px; color: #94a3b8;">
              <span>{{ number_format($crop['total_area'], 1) }} ha total area</span>
              <span>₱{{ number_format($crop['total_value'], 0) }} value</span>
            </div>
          </div>
        @endforeach
      @else
        <div style="text-align: center; padding: 32px; color: #94a3b8;">
          <p style="margin: 0;">No crop demand data yet.</p>
        </div>
      @endif
    </div>
  </div>
</div>

<!-- Full Quotation Log -->
<div class="data-table-container">
  <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
    <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Global Quotation Log</h2>
  </div>
  <div data-paginated>
  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
    <div class="app-scroll">
      <table>
      <thead>
        <tr>
          <th>Reference</th>
          <th>Farmer</th>
          <th>Crop</th>
          <th>Area</th>
          <th>Est. Value</th>
          <th>Status</th>
          <th>Submitted</th>
        </tr>
      </thead>
      <tbody>
        @forelse($quotations as $q)
          @php $badgeStyle = 'background:' . $q->status_bg . ';color:' . $q->status_color . ';'; @endphp
          <tr>
            <td style="font-weight: 600; color: #143d23; font-family: monospace; font-size: 13px;">{{ $q->quotation_number }}</td>
            <td>
              <div style="display: flex; align-items: center; gap: 8px;">
                <div class="avatar" style="background: #dbeafe; color: #1e40af;">
                  @php $w = explode(' ', trim($q->farmer?->name ?? '?')); echo count($w) >= 2 ? mb_substr($w[0], 0, 1).mb_substr(end($w), 0, 1) : '?'; @endphp
                </div>
                <span>{{ $q->farmer?->name ?? 'Unknown' }}</span>
              </div>
            </td>
            <td style="text-transform: capitalize;">{{ $q->crop_type }} — {{ $q->season }}</td>
            <td>{{ number_format($q->land_area, 1) }} ha</td>
            <td style="font-weight: 700;">
              @if($q->estimated_total_cost > 0)
                ₱{{ number_format($q->estimated_total_cost, 2) }}
              @else
                <span style="color: #94a3b8;">—</span>
              @endif
            </td>
            <td>
              <span class="badge" data-inline-style="{{ $badgeStyle }}">
                <span class="badge-dot" style="background: currentColor;"></span>
                {{ $q->status_label }}
              </span>
            </td>
            <td style="font-size: 13px; color: #64748b;">{{ $q->created_at->format('M d, Y') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; padding: 48px 20px; color: #64748b;">
              <svg viewBox="0 0 24 24" width="36" height="36" stroke="#94a3b8" stroke-width="1.5" fill="none" style="margin-bottom: 8px;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
              </svg>
              <p style="font-size: 15px; font-weight: 600; margin: 0;">No Quotations in System</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
    </div>
    <div class="app-scrollbar">
      <div class="app-scrollbar-track"></div>
      <div class="app-scrollbar-thumb"></div>
    </div>
  </div>
  @if($quotations->hasPages())
    {{ $quotations->links('partials.pagination') }}
  @endif
  </div>
</div>
@endsection

@push('scripts')
  @include('partials.ajax-pagination')
@endpush
