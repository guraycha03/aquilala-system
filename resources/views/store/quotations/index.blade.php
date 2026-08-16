@extends('layouts.sidebar')

@section('title', 'Cost Quotations')

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
  .stat-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  }
  .stat-value { font-size: 28px; font-weight: 800; color: #143d23; line-height: 1; }
  .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 500; }

  .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
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
  .badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
  }
  .action-btn {
    background: #143d23;
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background 0.2s;
  }
  .action-btn:hover { background: #0d2917; }
  .action-btn-secondary {
    background: #475569;
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background 0.2s;
  }
  .action-btn-secondary:hover { background: #334155; }
  .claim-btn {
    background: #0369a1;
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s;
  }
  .claim-btn:hover { background: #075985; }
  .farmer-avatar {
    width: 32px; height: 32px;
    background: #dbeafe; color: #1e40af;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 12px;
    flex-shrink: 0;
  }
  .farmer-cell {
    display: flex;
    align-items: center;
    gap: 10px;
  }
</style>

<div class="header-panel" style="margin-bottom: 24px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Inbound Cost Quotations</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">Review and compute cost quotation requests submitted by farmers.</p>
</div>

<!-- Stats Row -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px;">
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $pendingCount }}</div>
      <div class="stat-label">Pending Requests</div>
    </div>
    <svg viewBox="0 0 24 24" width="28" height="28" stroke="#f59e0b" stroke-width="1.5" fill="none">
      <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
    </svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $computedCount }}</div>
      <div class="stat-label">Computed / Dispatched</div>
    </div>
    <svg viewBox="0 0 24 24" width="28" height="28" stroke="#0284c7" stroke-width="1.5" fill="none">
      <polyline points="20 6 9 17 4 12"/>
    </svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">₱{{ number_format($totalValue, 0) }}</div>
      <div class="stat-label">Total Estimated Value</div>
    </div>
    <svg viewBox="0 0 24 24" width="28" height="28" stroke="#16a34a" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
      <!-- Vertical Stem -->
      <line x1="8" y1="3" x2="8" y2="21"/>
      <!-- P-Loop (Taller and wider) -->
      <path d="M8 3h6.5a4.5 4.5 0 0 1 0 9H8"/>
      <!-- Top Horizontal Bar (Nudge right) -->
      <line x1="4.5" y1="6.5" x2="21" y2="6.5" stroke-width="1.5"/>
      <!-- Bottom Horizontal Bar (Nudge right) -->
      <line x1="4.5" y1="9.5" x2="21" y2="9.5" stroke-width="1.5"/>
    </svg>
  </div>
</div>

<!-- Quotations Table -->
<div class="data-table-container">
  <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
    <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Quotation Requests</h2>
    <span style="font-size: 13px; color: #64748b;">{{ $quotations->count() }} total</span>
  </div>
  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
    <div class="app-scroll">
      <table>
      <thead>
        <tr>
          <th>Reference</th>
          <th>Farmer</th>
          <th>Crop / Season</th>
          <th>Land Area</th>
          <th>Requested</th>
          <th>Status</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($quotations as $q)
          @php $badgeStyle = 'background:' . $q->status_bg . ';color:' . $q->status_color . ';'; @endphp
          <tr>
            <td style="font-weight: 600; color: #143d23; font-family: monospace; font-size: 13px;">{{ $q->quotation_number }}</td>
            <td>
              <div class="farmer-cell">
                <div class="farmer-avatar">
                  @php
                    $name = $q->farmer ? $q->farmer->name : 'Unknown';
                    $words = explode(' ', trim($name));
                    echo count($words) >= 2 ? mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1) : mb_substr($name, 0, 2);
                  @endphp
                </div>
                <span>{{ $q->farmer ? $q->farmer->name : 'Unknown Farmer' }}</span>
              </div>
            </td>
            <td style="text-transform: capitalize;">{{ $q->crop_type }} — {{ $q->season }}</td>
            <td>{{ number_format($q->land_area, 1) }} ha</td>
            <td>{{ $q->created_at->format('M d, Y') }}</td>
            <td>
              <span class="badge" data-inline-style="{{ $badgeStyle }}">
                <span class="badge-dot" style="background: currentColor;"></span>
                {{ $q->status_label }}
              </span>
            </td>
            <td style="text-align: right;">
              @if($q->status === 'pending' && $q->store_user_id === null)
                <form action="{{ route('store.quotations.claim', $q) }}" method="POST" style="display: inline;">
                  @csrf
                  <button type="submit" class="claim-btn">Claim & Compute</button>
                </form>
              @elseif(in_array($q->status, ['pending', 'computed']))
                <a href="{{ route('store.quotations.show', $q) }}" class="action-btn">
                  <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  {{ $q->status === 'computed' ? 'Review' : 'Compute' }}
                </a>
              @elseif($q->status === 'dispatched')
                <a href="{{ route('store.quotations.show', $q) }}" class="action-btn-secondary">
                  <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
                  View
                </a>
              @else
                <a href="{{ route('store.quotations.show', $q) }}" class="action-btn-secondary" style="background: #6b7280;">View</a>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align: center; padding: 48px 20px; color: #64748b;">
              <svg viewBox="0 0 24 24" width="36" height="36" stroke="#94a3b8" stroke-width="1.5" fill="none" style="margin-bottom: 8px;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
              </svg>
              <p style="font-size: 15px; font-weight: 600; margin: 0 0 2px 0;">No Quotation Requests Yet</p>
              <p style="font-size: 13px; margin: 0;">When farmers submit cost quotation requests, they'll appear here.</p>
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
</div>
@endsection

