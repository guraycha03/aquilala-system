@extends('layouts.sidebar')

@section('title', 'Cost Quotation Analytics')

@section('content')
@include('partials.admin-tabs', [
  'tabs' => [
    ['key' => 'demand',   'label' => 'Demand Trends',       'url' => route('admin.quotations.index')],
    ['key' => 'harvests', 'label' => 'Harvest Performance',  'url' => route('admin.harvests.index')],
  ],
  'activeTab' => 'demand',
])
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

<div class="header-panel" style="margin-bottom: 10px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Demand Trends</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">System-wide demand visibility — which products and crops are in demand.</p>
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
</div>

<!-- Product & Crop Demand (de-identified — products/crops only) -->
<div class="data-table-container" style="margin-bottom: 24px;">
  <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
    <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Product Demand</h2>
    <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Most-quoted products across all estimates, with their crop types.</p>
  </div>
  <div style="padding: 16px 20px;">
    @if(count($productDemand) > 0)
      @php $maxP = max(array_column($productDemand, 'quotation_count')); @endphp
      @foreach($productDemand as $prod)
        <div style="{{ !$loop->last ? 'margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;' : '' }}">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
              <span style="width: 28px; height: 28px; border-radius: 8px; background: #f0fdf4; color: #16a34a; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              </span>
              <span style="font-weight: 600; color: #1e2922; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $prod['name'] }}</span>
              <span style="font-size: 11px; font-weight: 600; color: #0284c7; background: #e0f2fe; padding: 2px 8px; border-radius: 10px; white-space: nowrap;">{{ $prod['category'] }}</span>
            </div>
            <span style="font-size: 13px; color: #64748b; white-space: nowrap;">{{ $prod['quotation_count'] }} estimate(s)</span>
          </div>
          <div class="demand-bar">
            <div class="demand-bar-fill" style="width: {{ ($prod['quotation_count'] / max($maxP, 1)) * 100 }}%;"></div>
          </div>
          <div style="display: flex; justify-content: space-between; gap: 8px; margin-top: 4px; font-size: 12px; color: #94a3b8; flex-wrap: wrap;">
            <span>Crops: {{ $prod['crops'] ?: '—' }}</span>
            <span>{{ rtrim(rtrim(number_format($prod['total_qty'], 2), '0'), '.') }} {{ $prod['unit'] }} · ₱{{ number_format($prod['total_value'], 0) }}</span>
          </div>
        </div>
      @endforeach
    @else
      <div style="text-align: center; padding: 32px; color: #94a3b8;">
        <p style="margin: 0;">No product demand data yet.</p>
      </div>
    @endif
  </div>
</div>

<div class="data-table-container" style="margin-bottom: 24px;">
  <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
    <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Crop Demand</h2>
    <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Which crop types farmers are planning for.</p>
  </div>
  <div style="padding: 16px 20px;">
    @if(count($cropDemand) > 0)
      @php $maxCrop = max(array_column($cropDemand, 'count')); @endphp
      @foreach($cropDemand as $crop)
        <div style="{{ !$loop->last ? 'margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;' : '' }}">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
            <span style="font-weight: 600; color: #1e2922; font-size: 14px; text-transform: capitalize;">{{ $crop['crop'] }}</span>
            <span style="font-size: 13px; color: #64748b;">{{ $crop['count'] }} estimate(s)</span>
          </div>
          <div class="demand-bar">
            <div class="demand-bar-fill" style="width: {{ ($crop['count'] / max($maxCrop, 1)) * 100 }}%;"></div>
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
@endsection
