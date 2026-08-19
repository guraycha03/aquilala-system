@extends('layouts.sidebar')

@section('title', 'Demand Intelligence')

@section('content')
<style>
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
  }
  @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } }

  .stat-card {
    position: relative;
    border-radius: 14px;
    padding: 22px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
  .stat-card::after {
    content: ''; position: absolute; top: 0; right: 0; width: 80px; height: 80px;
    border-radius: 50%; opacity: 0.07; transform: translate(20px, -20px);
  }
  .stat-card.blue   { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe; }
  .stat-card.blue::after  { background: #2563eb; }
  .stat-card.amber  { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a; }
  .stat-card.amber::after { background: #d97706; }
  .stat-card.green  { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; }
  .stat-card.green::after { background: #16a34a; }

  .stat-card .stat-value { font-size: 32px; font-weight: 800; letter-spacing: -0.02em; line-height: 1; }
  .stat-card.blue .stat-value  { color: #1e40af; }
  .stat-card.amber .stat-value { color: #92400e; }
  .stat-card.green .stat-value { color: #166534; }
  .stat-card .stat-label { font-size: 13px; color: #6b7c6e; margin-top: 4px; font-weight: 500; }

  .panel {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
  }
  .panel-header { padding: 20px 24px; border-bottom: 1px solid #f0f2f1; background: #fafdfb; }
  .panel-body { padding: 20px 24px; }
  .panel-body-flush { padding: 0; }

  .demand-bar { height: 8px; background: #f0f2f1; border-radius: 4px; overflow: hidden; }
  .demand-bar-fill {
    height: 100%; border-radius: 4px;
    background: linear-gradient(90deg, #16a34a, #22c55e);
    transition: width 0.6s ease;
  }

  .farmer-avatar {
    width: 36px; height: 36px;
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px; flex-shrink: 0;
  }
  .farmer-cell { display: flex; align-items: center; gap: 12px; }

  .table-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
  }

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

  .action-btn {
    background: linear-gradient(135deg, #143d23 0%, #1a5230 100%);
    color: white; border: none;
    padding: 7px 16px; border-radius: 8px;
    font-size: 12px; font-weight: 700;
    cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 5px;
    transition: all 0.2s; box-shadow: 0 2px 6px rgba(20,61,35,0.2);
  }
  .action-btn:hover { box-shadow: 0 4px 12px rgba(20,61,35,0.3); transform: translateY(-1px); }
  .action-btn:active { transform: scale(0.97); }

  .product-count-badge {
    background: #f0fdf4; color: #166534;
    padding: 3px 10px; border-radius: 8px;
    font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 4px;
    border: 1px solid #bbf7d0;
  }

  .empty-state {
    text-align: center; padding: 56px 24px;
    background: linear-gradient(180deg, #fafdfb 0%, #f5f7f6 100%);
  }
  .empty-icon {
    width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 16px;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border: 1px solid #bfdbfe;
    display: flex; align-items: center; justify-content: center;
  }
</style>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">Demand Intelligence</h1>
  <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">See which farmers are exploring your products. This is demand visibility — no action required.</p>
</div>

@if(session('success'))
  <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(22,163,74,0.06);">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(22,163,74,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#16a34a" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span style="font-size: 14px; color: #166534; font-weight: 600;">{{ session('success') }}</span>
  </div>
@endif

<div class="stats-grid">
  <div class="stat-card blue">
    <div>
      <div class="stat-value">{{ $totalQuotations }}</div>
      <div class="stat-label">Farmers Using Your Products</div>
    </div>
    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(37,99,235,0.1); display: flex; align-items: center; justify-content: center;">
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#2563eb" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </div>
  </div>
  <div class="stat-card amber">
    <div>
      <div class="stat-value">{{ $recentQuotations }}</div>
      <div class="stat-label">This Week</div>
    </div>
    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(217,119,6,0.1); display: flex; align-items: center; justify-content: center;">
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#d97706" stroke-width="2" fill="none"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    </div>
  </div>
  <div class="stat-card green">
    <div>
      <div class="stat-value">₱{{ number_format($totalDemandValue, 0) }}</div>
      <div class="stat-label">Total Demand Value</div>
    </div>
    <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(22,163,74,0.1); display: flex; align-items: center; justify-content: center;">
      <svg viewBox="0 0 24 24" width="24" height="24" stroke="#16a34a" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="3" x2="8" y2="21"/><path d="M8 3h6.5a4.5 4.5 0 0 1 0 9H8"/></svg>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
  <div class="panel">
    <div class="panel-header">
      <h2 style="font-size: 16px; color: #1a2b1e; font-weight: 700; margin: 0;">Your Products in Demand</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Which products farmers are exploring</p>
    </div>
    <div class="panel-body">
      @if(count($productDemand) > 0)
        @php $maxCount = max(array_column($productDemand, 'count')); @endphp
        @foreach($productDemand as $productName => $data)
          <div style="{{ !$loop->last ? 'margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f2f1;' : '' }}">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-weight: 700; color: #1a2b1e; font-size: 14px;">{{ $productName }}</span>
              <span style="font-size: 12px; color: #879a8c; font-weight: 600;">{{ $data['count'] }} quotation(s)</span>
            </div>
            <div class="demand-bar">
              <div class="demand-bar-fill" style="width: {{ ($data['count'] / $maxCount) * 100 }}%;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 6px; font-size: 12px; color: #b0b8b3;">
              <span>{{ number_format($data['total_qty'], 1) }} units total</span>
              <span style="font-weight: 600; color: #4b5c50;">₱{{ number_format($data['total_value'], 0) }} value</span>
            </div>
          </div>
        @endforeach
      @else
        <div style="text-align: center; padding: 40px 20px;">
          <div class="empty-icon" style="margin-bottom: 12px;">
            <svg viewBox="0 0 24 24" width="28" height="28" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
          </div>
          <p style="margin: 0; font-size: 14px; color: #879a8c; line-height: 1.5;">No demand data yet. As farmers generate quotations using your products, you'll see trends here.</p>
        </div>
      @endif
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h2 style="font-size: 16px; color: #1a2b1e; font-weight: 700; margin: 0;">Recent Farmer Activity</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Farmers who recently explored your products</p>
    </div>
    <div class="panel-body-flush">
      @if($quotations->count() > 0)
        @foreach($quotations->take(8) as $q)
          <div style="padding: 14px 20px; {{ !$loop->last ? 'border-bottom: 1px solid #f5f7f6;' : '' }} display: flex; align-items: center; gap: 12px; transition: background 0.15s;" onmouseover="this.style.background='#fafdfb'" onmouseout="this.style.background='transparent'">
            <div class="farmer-avatar" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; border: 1px solid #bfdbfe;">
              @php
                $name = $q->farmer ? $q->farmer->name : 'Unknown';
                $words = explode(' ', trim($name));
                echo count($words) >= 2 ? mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1) : mb_substr($name, 0, 2);
              @endphp
            </div>
            <div style="flex: 1; min-width: 0;">
              <div style="font-weight: 700; color: #1a2b1e; font-size: 14px;">{{ $q->farmer?->name ?? 'Unknown Farmer' }}</div>
              <div style="font-size: 12px; color: #879a8c; text-transform: capitalize;">{{ $q->crop_type }} — {{ $q->land_area }} ha — {{ $q->season }} season</div>
            </div>
            <div style="text-align: right;">
              <div style="font-weight: 700; color: #143d23; font-size: 14px;">₱{{ number_format($q->estimated_total_cost, 0) }}</div>
              <div style="font-size: 11px; color: #b0b8b3;">{{ $q->created_at->diffForHumans() }}</div>
            </div>
          </div>
        @endforeach
      @else
        <div style="text-align: center; padding: 48px 20px;">
          <div style="width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 16px; background: linear-gradient(135deg, #f5f5f5, #f0f0f0); border: 1px solid #e5e5e5; display: flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24" width="28" height="28" stroke="#9ca3af" stroke-width="1.5" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <p style="font-size: 15px; font-weight: 600; color: #6b7c6e; margin: 0 0 4px 0;">No Activity Yet</p>
          <p style="font-size: 13px; color: #879a8c; margin: 0;">When farmers generate quotations using your products, they'll appear here.</p>
        </div>
      @endif
    </div>
  </div>
</div>

<div class="table-card">
  <div style="padding: 20px 24px; border-bottom: 1px solid #f0f2f1; display: flex; justify-content: space-between; align-items: center; background: #fafdfb;">
    <div>
      <h2 style="font-size: 16px; color: #1a2b1e; font-weight: 700; margin: 0;">All Demand Signals</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 2px 0 0 0;">Every cost quotation that includes your products</p>
    </div>
    <span style="font-size: 13px; color: #879a8c; font-weight: 600; background: #f0f2f1; padding: 4px 12px; border-radius: 8px;">{{ $quotations->count() }} total</span>
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
          <th>Your Products</th>
          <th>Est. Value</th>
          <th>Date</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($quotations as $q)
          @php
            $storeItems = $q->items->filter(fn ($item) => $item->product && $item->product->user_id === Auth::id());
          @endphp
          <tr>
            <td><span style="font-weight: 700; color: #143d23; font-family: 'SF Mono', ui-monospace, monospace; font-size: 13px;">{{ $q->quotation_number }}</span></td>
            <td>
              <div class="farmer-cell">
                <div class="farmer-avatar" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; border: 1px solid #bfdbfe; width: 32px; height: 32px; border-radius: 8px; font-size: 12px;">
                  @php
                    $name = $q->farmer ? $q->farmer->name : 'Unknown';
                    $words = explode(' ', trim($name));
                    echo count($words) >= 2 ? mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1) : mb_substr($name, 0, 2);
                  @endphp
                </div>
                <span style="font-weight: 500;">{{ $q->farmer?->name ?? 'Unknown Farmer' }}</span>
              </div>
            </td>
            <td>
              <div style="text-transform: capitalize; font-weight: 500;">{{ $q->crop_type }}</div>
              <div style="font-size: 12px; color: #879a8c; text-transform: capitalize;">{{ $q->season }} season</div>
            </td>
            <td><span style="font-weight: 600;">{{ number_format($q->land_area, 1) }}</span> <span style="color: #879a8c; font-size: 12px;">ha</span></td>
            <td><span class="product-count-badge">{{ $storeItems->count() }} product(s)</span></td>
            <td>
              @if($q->estimated_total_cost > 0)
                <span style="font-weight: 700; color: #1a2b1e;">₱{{ number_format($q->estimated_total_cost, 2) }}</span>
              @else
                <span style="color: #d1d9d3;">—</span>
              @endif
            </td>
            <td style="font-size: 13px; color: #879a8c; white-space: nowrap;">{{ $q->created_at->format('M d, Y') }}</td>
            <td style="text-align: right;">
              <a href="{{ route('store.quotations.show', $q) }}" class="action-btn">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                View
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 24 24" width="28" height="28" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <p style="font-size: 16px; font-weight: 600; color: #6b7c6e; margin: 0 0 4px 0;">No Demand Signals Yet</p>
                <p style="font-size: 13px; color: #879a8c; margin: 0;">When farmers generate quotations using your products, they'll appear here.</p>
              </div>
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
