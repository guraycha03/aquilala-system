@extends('layouts.sidebar')

@section('title', 'Quotation Logs')

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

  .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
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
</style>

<div class="header-panel" style="margin-bottom: 24px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">System Cost Quotation Logs</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">Monitor financial interactions and procurement queries between farmers and merchants.</p>
</div>

<!-- System-wide metrics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px;">
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['total'] }}</div>
      <div class="stat-label">Total Quotations</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#143d23" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['pending'] }}</div>
      <div class="stat-label">Pending</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#d97706" stroke-width="1.5" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['computed'] }}</div>
      <div class="stat-label">Active Estimates</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#0284c7" stroke-width="1.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $metrics['completed'] }}</div>
      <div class="stat-label">Completed</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#16a34a" stroke-width="1.5" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">₱{{ number_format($metrics['totalValue'], 0) }}</div>
      <div class="stat-label">Total Value</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#16a34a" stroke-width="1.5" fill="none"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
  </div>
</div>

<div class="data-table-container">
  <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
    <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Global Transaction Log</h2>
  </div>
  <div style="overflow-x: auto;">
    <table>
      <thead>
        <tr>
          <th>Reference</th>
          <th>Farmer</th>
          <th>Merchant / Store</th>
          <th>Crop</th>
          <th>Area</th>
          <th>Total Estimate</th>
          <th>Status</th>
          <th>Submitted</th>
        </tr>
      </thead>
      <tbody>
        @forelse($quotations as $q)
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
            <td>
              @if($q->storeOwner)
                <div style="display: flex; align-items: center; gap: 8px;">
                  <div class="avatar" style="background: #fef3c7; color: #92400e;">
                    @php $w = explode(' ', trim($q->storeOwner->name)); echo count($w) >= 2 ? mb_substr($w[0], 0, 1).mb_substr(end($w), 0, 1) : '?'; @endphp
                  </div>
                  <span>{{ $q->storeOwner->name }}</span>
                </div>
              @else
                <span style="color: #94a3b8;">Unassigned</span>
              @endif
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
              <span class="badge" style="background: {{ $q->status_bg }}; color: {{ $q->status_color }};">
                <span class="badge-dot" style="background: {{ $q->status_color }};"></span>
                {{ $q->status_label }}
              </span>
            </td>
            <td style="font-size: 13px; color: #64748b;">{{ $q->created_at->format('M d, Y') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align: center; padding: 48px 20px; color: #64748b;">
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
</div>
@endsection

