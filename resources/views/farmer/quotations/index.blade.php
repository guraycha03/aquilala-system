@extends('layouts.sidebar')

@section('title', 'My Cost Quotations')

@section('content')
<style>
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
  }
  @media (max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 520px) { .stats-grid { grid-template-columns: 1fr; } }

  .stat-card {
    position: relative;
    border-radius: 14px;
    padding: 20px 22px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
  .stat-card::after {
    content: '';
    position: absolute; top: 0; right: 0; width: 80px; height: 80px;
    border-radius: 50%; opacity: 0.07;
    transform: translate(20px, -20px);
  }
  /* One look for every card: the same soft tile, the same icon colour.
     A wall of tinted cards says nothing once there are more than two. */
  .stat-card {
    background: #ffffff;
    border: 1px solid #E0E5DB;
  }
  .stat-card::after { display: none; }

  .stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
    background: #EDF1E9; color: #4A7A54;
  }

  .stat-card .stat-label {
    font-size: 12px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.05em; margin-bottom: 4px;
    color: #647263;
  }

  .stat-card .stat-value { font-size: 28px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; color: #1a2b1e; }
  [data-theme="dark"] .stat-card { background: #111827; border-color: #334155; }
  [data-theme="dark"] .stat-icon { background: #16301f; color: #a7f3d0; }
  [data-theme="dark"] .stat-card .stat-label { color: #9fb3a6; }
  [data-theme="dark"] .stat-card .stat-value { color: #d6f5e3; }

  /* Table card */
  .table-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
  }
  .table-header {
    padding: 20px 24px;
    border-bottom: 1px solid #f0f2f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
  }

  /* Table base style lives in the shared .data-table rules (layouts.sidebar). */

  /* Status badges */
  .status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    letter-spacing: 0.01em;
  }
  .status-badge.active   { background: #edf1e9; color: #648268; }
  .status-badge.computed { background: #edf1e9; color: #648268; }
  .status-badge.viewed   { background: #edf1e9; color: #648268; }
  .status-badge.expired  { background: #f5f5f5; color: #9ca3af; }
  .status-badge.pending  { background: #edf1e9; color: #648268; }
  .status-badge.accepted { background: #f0fdf4; color: #15803d; }
  .status-badge-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: currentColor;
  }

  .item-count {
    background: #f5f7f6; padding: 4px 10px; border-radius: 8px;
    font-size: 12px; color: #4b5c50; font-weight: 600;
    display: inline-flex; align-items: center; gap: 4px;
  }

  /* The archive: where the closed estimates live, so the working page
     holds only the ones that can still be planned with. */
  .archive-link-row {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;
  }
  .archive-link {
    display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
    background: #ffffff; border: 1px solid #E0E5DB; color: #344B38;
    font-size: 12.5px; font-weight: 700; padding: 9px 14px; border-radius: 10px;
    transition: background 0.15s ease, border-color 0.15s ease;
  }
  .archive-link:hover { background: #F3F5F0; border-color: #C8D5C5; }
  .archive-link svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .archive-count {
    font-size: 11px; font-weight: 700; color: #4b5c50; background: #F0F2F1;
    border-radius: 999px; padding: 2px 8px;
  }
  .archive-hint { font-size: 12px; color: #94a3b8; }
  [data-theme="dark"] .archive-link { background: #1f2937; border-color: #334155; color: #d6f5e3; }
  [data-theme="dark"] .archive-link:hover { background: #16301f; border-color: #4ade80; }
  [data-theme="dark"] .archive-count { background: #263244; color: #a7f3d0; }

  /* Primary CTA */
  .cta-primary {
    background: #3F6B4D;
    color: #fff; text-decoration: none;
    padding: 10px 20px; border-radius: 10px;
    font-size: 13px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 7px;
    box-shadow: 0 2px 8px rgba(63,107,77,0.22);
    transition: all 0.2s ease;
    border: none; cursor: pointer;
  }
  .cta-primary:hover {
    box-shadow: 0 4px 16px rgba(20,61,35,0.3);
    transform: translateY(-1px);
  }
  .cta-primary:active { transform: scale(0.98); }

  .action-view {
    color: #143d23; font-weight: 600; font-size: 13px;
    text-decoration: none; display: inline-flex; align-items: center; gap: 4px;
    padding: 6px 12px; border-radius: 8px; transition: all 0.15s;
  }
  .action-view:hover { background: #f0fdf4; }

  .delete-btn {
    background: none; border: 1px solid #e5e7eb; border-radius: 8px;
    padding: 6px 8px; cursor: pointer; color: #b0b8b3;
    transition: all 0.15s; display: inline-flex; align-items: center;
  }
  .delete-btn:hover { border-color: #e4c6c3; color: #b0403a; background: #faf2f1; }

  /* Empty state */
  .empty-state {
    text-align: center; padding: 72px 24px;
    background: linear-gradient(180deg, #fafdfb 0%, #f5f7f6 100%);
  }
  .empty-icon {
    width: 72px; height: 72px; border-radius: 20px; margin: 0 auto 20px;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0;
    display: flex; align-items: center; justify-content: center;
  }
  .empty-icon svg { width: 32px; height: 32px; color: #4A7A54; }

  .link-animated {
    --c: #143d23;
    position: relative; display: inline-flex; align-items: center;
    gap: 8px; padding: 4px 0; text-decoration: none;
    font-weight: 700; font-size: 14px; color: var(--c);
    transition: color 0.2s;
  }
  .link-animated::after {
    content: ''; position: absolute; bottom: 0; left: 0;
    width: 0; height: 2px; background: var(--c);
    transition: width 0.3s ease;
  }
  .link-animated:hover::after { width: 100%; }
  .link-animated:hover { color: #166534; }
  .link-animated:hover svg { transform: translateX(3px); }
  .link-animated svg { transition: transform 0.2s ease; width: 16px; height: 16px; }
</style>

<div class="header-panel" style="margin-bottom: 28px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">My Cost Quotations</h1>
      <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">
        Instantly generated farming cost quotations — no approval needed. Browse and create new quotations anytime.
      </p>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; width: 100%;">
      <a href="{{ route('farmer.quotations.archive') }}" class="archive-link" title="Expired or closed estimates">
        <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>
        <span>Expired Quotations @if($archivedCount > 0)({{ $archivedCount }})@endif</span>
      </a>
      <a href="{{ route('farmer.quotations.request') }}" class="cta-primary">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Cost Quotation
      </a>
    </div>
  </div>
</div>

@if(session('success'))
  <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(74,122,84,0.06);">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(74,122,84,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#4A7A54" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span style="font-size: 14px; color: #166534; font-weight: 600;">{{ session('success') }}</span>
  </div>
@endif

@php
  $totalQuotes = $totalQuotes ?? $quotations->count();
  $computedCount = $computedCount ?? $quotations->where('status', 'computed')->count();
  $totalValue = $totalValue ?? $quotations->sum('estimated_total_cost');
@endphp

<!-- Stats -->
<div class="stats-grid kpi-strip">
  <div class="stat-card">
    <div class="stat-icon">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div class="stat-label">Total Quotations</div>
    <div class="stat-value">{{ $totalQuotes }}</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
    </div>
    <div class="stat-label">Ready to View</div>
    <div class="stat-value">{{ $computedCount }}</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">
      <x-peso-icon />
    </div>
    <div class="stat-label">Total Estimated Value</div>
    <div class="stat-value">₱{{ number_format($totalValue, 0) }}</div>
  </div>
</div>

<!-- Quotations List -->
@if($archivedCount > 0)
  <div class="archive-link-row">
    <a href="{{ route('farmer.quotations.archive') }}" class="archive-link">
      <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="5" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>
      <span>Expired / Archived Estimates</span>
      <span class="archive-count">{{ $archivedCount }}</span>
    </a>
    <span class="archive-hint">Estimates whose validity window has closed are kept here, out of the way.</span>
  </div>
@endif

@if($quotations->count() > 0)
  <x-table-toolbar
      uid="quotes"
      work-table="quotesTable"
      per-page="10"
      noun="quotations"
      search-placeholder="Search by reference, crop, or season…"
      :filters="[
          ['key' => 'crop', 'label' => 'All crops', 'options' => $cropOptions],
          ['key' => 'season', 'label' => 'All seasons', 'options' => collect($quotations->pluck('season')->unique()->values())->map(fn($s) => [strtolower($s), ucfirst($s)])->all()],
      ]"
      :sorts="[
          ['key' => 'date', 'dir' => -1, 'type' => 'date', 'label' => 'Date (newest)'],
          ['key' => 'date', 'dir' => 1, 'type' => 'date', 'label' => 'Date (oldest)'],
          ['key' => 'cost', 'dir' => -1, 'type' => 'num', 'label' => 'Estimated cost (highest)'],
          ['key' => 'cost', 'dir' => 1, 'type' => 'num', 'label' => 'Estimated cost (lowest)'],
          ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop (A–Z)'],
      ]"
    />
  @endif

<div class="table-card">
  @if($quotations->count() > 0)
    <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
      <div class="app-scroll">
        <table id="quotesTable" class="quote-table data-table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Crop / Season</th>
            <th>Land Area</th>
            <th>Items</th>
            <th>Estimated Cost</th>
            <th>Date</th>
            <th>Status</th>
            <th style="text-align: right;">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($quotations as $quote)
            @php
              // Two states the farmer can act on: the estimate is still live,
              // or it has expired. Whether a store has opened it is not one
              // of them, and the filter agrees with the table.
              $rowStatus = $quote->effective_status === 'expired' ? 'expired' : 'active';
            @endphp
            <tr data-search="{{ strtolower(($quote->quotation_number ?? '') . ' ' . ($quote->crop_type ?? '') . ' ' . ($quote->season ?? '') . ' ' . $rowStatus) }}"
                data-status="{{ $rowStatus }}"
                data-crop="{{ strtolower($quote->crop_type ?? '') }}"
                data-season="{{ strtolower($quote->season ?? '') }}"
                data-cost="{{ (float) ($quote->estimated_total_cost ?? 0) }}"
                data-date="{{ $quote->created_at?->format('Y-m-d') ?? '' }}">
              <td>
                <span style="font-weight: 700; color: #143d23; font-family: 'SF Mono', ui-monospace, monospace; font-size: 13px; letter-spacing: 0.02em;">{{ $quote->quotation_number }}</span>
              </td>
              <td>
                <div style="text-transform: capitalize; font-weight: 500;">{{ $quote->crop_type }}</div>
                <div style="font-size: 12px; color: #94a3b8; text-transform: capitalize;">{{ $quote->season }} season</div>
              </td>
              <td><span style="font-weight: 600;">{{ number_format($quote->land_area, 2) }}</span> <span style="color: #94a3b8; font-size: 12px;">ha</span></td>
              <td>
                <span class="item-count">
                  <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                  {{ $quote->items->count() }}
                </span>
              </td>
              <td>
                <span style="font-weight: 700; color: #1a2b1e;">₱{{ number_format($quote->estimated_total_cost, 2) }}</span>
              </td>
              <td style="color: #879a8c; font-size: 13px; white-space: nowrap;">{{ $quote->created_at->format('M d, Y') }}</td>
              <td>
                <span class="status-badge {{ $rowStatus }}">
                  <span class="status-badge-dot"></span>
                  {{ $quote->status_label }}
                </span>
              </td>
              <td style="text-align: right;">
                <div style="display: flex; gap: 6px; align-items: center; justify-content: flex-end;">
                  <a href="{{ route('farmer.quotations.show', $quote) }}" class="action-view">
                    View
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="9 18 15 12 9 6"/></svg>
                  </a>
                  <form action="{{ route('farmer.quotations.destroy', $quote) }}" method="POST"
                        data-aqui-confirm="Delete this cost quotation?"
                        data-aqui-danger data-aqui-confirm-label="Delete"
                        style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Delete quotation" class="delete-btn">
                      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                  </form>
                </div>
              </td>
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
  @else
    <div class="empty-state">
      <div class="empty-icon">
        <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <h3 style="font-size: 18px; color: #1a2b1e; font-weight: 700; margin: 0 0 6px 0;">No Cost Quotations Yet</h3>
      <p style="font-size: 14px; color: #6b7c6e; margin: 0 0 24px 0; max-width: 360px; margin-left: auto; margin-right: auto; line-height: 1.5;">
        Generate your first farming cost quotation instantly — no approval required.
      </p>
      <a href="{{ route('farmer.quotations.request') }}" class="link-animated">
        <span>Generate Cost Quotation</span>
        <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </a>
    </div>
  @endif
</div>

@endsection
