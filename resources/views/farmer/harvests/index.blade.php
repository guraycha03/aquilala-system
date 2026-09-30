@extends('layouts.sidebar')

@section('title', 'My Harvest Reports')

@section('content')
  @include('partials.admin-tabs', [
    'tabs' => [
      ['key' => 'crops',    'label' => 'My Crops',       'url' => route('farmer.crops.index')],
      ['key' => 'harvests', 'label' => 'Harvest Reports', 'url' => route('farmer.harvests.index')],
    ],
    'activeTab' => 'harvests',
  ])
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
  .stat-card.green  { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0; }
  .stat-card.green::after { background: #16a34a; }
  .stat-card.blue   { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe; }
  .stat-card.blue::after  { background: #2563eb; }
  .stat-card.amber  { background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a; }
  .stat-card.amber::after { background: #d97706; }
  .stat-card.violet { background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border: 1px solid #ddd6fe; }
  .stat-card.violet::after { background: #7c3aed; }

  .stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
  }
  .stat-card.green .stat-icon  { background: rgba(22,163,74,0.12); color: #16a34a; }
  .stat-card.blue .stat-icon   { background: rgba(37,99,235,0.12); color: #2563eb; }
  .stat-card.amber .stat-icon  { background: rgba(217,119,6,0.12); color: #d97706; }
  .stat-card.violet .stat-icon { background: rgba(124,58,237,0.12); color: #7c3aed; }

  .stat-card .stat-label {
    font-size: 12px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.05em; margin-bottom: 4px;
  }
  .stat-card.green .stat-label  { color: #15803d; }
  .stat-card.blue .stat-label   { color: #1d4ed8; }
  .stat-card.amber .stat-label  { color: #b45309; }
  .stat-card.violet .stat-label { color: #6d28d9; }

  .stat-card .stat-value { font-size: 26px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; }
  .stat-card .stat-sub   { font-size: 12px; color: #64748b; margin-top: 3px; font-weight: 500; }
  .stat-card.green .stat-value  { color: #166534; }
  .stat-card.blue .stat-value   { color: #1e40af; }
  .stat-card.amber .stat-value  { color: #92400e; }
  .stat-card.violet .stat-value { color: #5b21b6; }

  /* Filter bar */
  .filter-bar {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 12px 16px;
    margin-bottom: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }
  .filter-search {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 14px;
    height: 42px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    min-width: 200px;
    transition: all 0.2s ease;
  }
  .filter-search:focus-within {
    border-color: #16a34a;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.08);
  }
  .filter-search svg { flex-shrink: 0; color: #94a3b8; }
  .filter-search input {
    border: none; outline: none; background: transparent;
    font-size: 13px; color: #0f172a; width: 100%; font-family: inherit; font-weight: 500;
  }
  .filter-search input::placeholder { color: #94a3b8; }
  .filter-select { min-width: 160px; height: 42px; }
  .btn-apply {
    appearance: none; -webkit-appearance: none; height: 42px; padding: 0 20px;
    font-size: 13px; font-weight: 700; border-radius: 10px; border: none;
    background: #16a34a; color: #ffffff; cursor: pointer;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
    transition: all 0.2s ease;
  }
  .btn-apply:hover { background: #15803d; transform: translateY(-1px); }
  .btn-clear {
    height: 42px; padding: 0 14px; border-radius: 10px;
    background: #f3faf4; border: 1.5px solid #bfe3c7; color: #166534;
    font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease;
  }
  .btn-clear:hover { background: #eaf6ee; border-color: #a3d4ad; }
  .result-count {
    font-size: 12px; color: #64748b; font-weight: 600; white-space: nowrap;
    background: #f1f5f9; padding: 4px 10px; border-radius: 20px;
  }

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
  .table-header h2 { font-size: 16px; font-weight: 700; color: #1e2922; margin: 0; }
  .table-header p { font-size: 13px; color: #64748b; margin: 4px 0 0 0; }

  .harvest-table { width: 100%; border-collapse: collapse; font-size: 14px; }
  .harvest-table th {
    padding: 13px 16px; font-weight: 600;
    color: #6b7c6e; border-bottom: 1px solid #f0f2f1; text-align: left;
    font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em;
    background: #fafdfb;
  }
  .harvest-table td { padding: 14px 16px; border-bottom: 1px solid #f5f7f6; color: #374138; }
  .harvest-table tbody tr { transition: background 0.15s; }
  .harvest-table tbody tr:hover td { background: #f8faf9; }
  .harvest-table tbody tr:last-child td { border-bottom: none; }

  .season-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; letter-spacing: 0.01em;
  }
  .season-badge.wet { background: #eff6ff; color: #1d4ed8; }
  .season-badge.dry { background: #fffbeb; color: #b45309; }
  .season-badge-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

  .yield-cell b { font-variant-numeric: tabular-nums; }
  .yield-tons { font-size: 11.5px; color: #94a3b8; font-weight: 600; }

  .delete-btn {
    background: none; border: 1px solid #e5e7eb; border-radius: 8px;
    padding: 6px 8px; cursor: pointer; color: #b0b8b3;
    transition: all 0.15s; display: inline-flex; align-items: center;
  }
  .delete-btn:hover { border-color: #e4c6c3; color: #b0403a; background: #faf2f1; }

  .btn-submit {
    appearance: none; -webkit-appearance: none; height: 44px; padding: 0 22px;
    border: none; border-radius: 11px; font-size: 13px; font-weight: 700;
    background: linear-gradient(135deg, #143d23 0%, #1a5230 100%); color: #fff; cursor: pointer;
    box-shadow: 0 4px 14px rgba(20,61,35,0.22);
    display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
    transition: all 0.2s ease;
  }
  .btn-submit:hover { background: #1a5230; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(20,61,35,0.28); }
  .btn-submit svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

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
  .empty-icon svg { width: 32px; height: 32px; color: #16a34a; }
</style>

<div class="header-panel" style="margin-bottom: 28px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">My Harvest Reports</h1>
      <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">
        Recorded harvests are instantly visible to the Aquilala administrator — yields, expenses, and planted areas stay in sync system-wide.
      </p>
    </div>
    <a href="{{ route('farmer.harvests.create') }}" class="btn-submit">
      <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Submit Harvest Report
    </a>
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

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card blue">
    <div class="stat-icon">
      <!-- Document / Report Icon -->
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
        <polyline points="14 2 14 8 20 8" />
        <line x1="16" y1="13" x2="8" y2="13" />
        <line x1="16" y1="17" x2="8" y2="17" />
        <line x1="10" y1="9" x2="8" y2="9" />
      </svg>
    </div>
    <div class="stat-label">Harvest Reports</div>
    <div class="stat-value">{{ $stats['reports'] }}</div>
    <div class="stat-sub">Recorded harvests</div>
  </div>

  <div class="stat-card green">
    <div class="stat-icon">
      <!-- Plant / Sprout Icon -->
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <path d="M7 20h10" />
        <path d="M12 20v-8" />
        <path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5" />
        <path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5" />
      </svg>
    </div>
    <div class="stat-label">Total Yield</div>
    <div class="stat-value">{{ $stats['yield'] }}</div>
    <div class="stat-sub">Combined harvest output</div>
  </div>

  <div class="stat-card violet">
    <div class="stat-icon">
      <!-- Peso Currency / Expense Icon -->
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <line x1="8" y1="3" x2="8" y2="21" />
        <path d="M8 3h6.5a4.5 4.5 0 0 1 0 9H8" />
      </svg>
    </div>
    <div class="stat-label">Total Expenses</div>
    <div class="stat-value">₱{{ number_format($stats['expenses'], 0) }}</div>
    <div class="stat-sub">Farming input &amp; labor costs</div>
  </div>

  <div class="stat-card amber">
    <div class="stat-icon">
      <!-- Map / Grid Area Icon -->
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2" />
        <path d="M3 9h18" />
        <path d="M9 21V9" />
      </svg>
    </div>
    <div class="stat-label">Total Area</div>
    <div class="stat-value">{{ number_format($stats['area'], 2) }} ha</div>
    <div class="stat-sub">Land area farmed</div>
  </div>
</div>

<!-- Filter bar -->
@php
  $uniqueCrops = $reports->pluck('crop_type')->filter()->unique()->sort()->values();
  $yearOptions = $yearOptions ?? [];
  $monthOptions = $monthOptions ?? [];
@endphp
<x-table-toolbar
    uid="harvests"
    work-table="harvestsTable"
    per-page="10"
    noun="harvests"
    search-placeholder="Search by crop or season…"
    :filters="[
        ['key' => 'crop', 'label' => 'All Crops', 'options' => $uniqueCrops->map(fn($c) => [strtolower($c), $c])->values()->all()],
        ['key' => 'season', 'label' => 'All Seasons', 'options' => [['wet', 'Wet Season'], ['dry', 'Dry Season']]],
        ['key' => 'year', 'label' => 'All Years', 'options' => $yearOptions],
        ['key' => 'month', 'label' => 'All Months', 'options' => $monthOptions],
    ]"
    :sorts="[
        ['key' => 'date', 'dir' => -1, 'type' => 'date', 'label' => 'Newest harvest'],
        ['key' => 'date', 'dir' => 1, 'type' => 'date', 'label' => 'Oldest harvest'],
        ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop (A–Z)'],
        ['key' => 'yield', 'dir' => -1, 'type' => 'num', 'label' => 'Yield (highest)'],
        ['key' => 'expenses', 'dir' => -1, 'type' => 'num', 'label' => 'Expenses (highest)'],
        ['key' => 'area', 'dir' => -1, 'type' => 'num', 'label' => 'Area (largest)'],
    ]"
/>

<!-- Table -->
<div class="table-card">
  <div class="table-header">
    <div>
      <h2>Harvest Records</h2>
      <p>Your actual harvest outputs, expenses, and planted areas.</p>
    </div>
  </div>
    <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
      <div class="app-scroll">
        <table class="harvest-table" id="harvestsTable">
          <thead>
            <tr>
              <th>Crop</th>
              <th>Season</th>
              <th>Area</th>
              <th>Yield</th>
              <th>Expenses</th>
              <th>Harvested</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($reports as $report)
              <tr data-crop="{{ strtolower($report->crop_type) }}"
                  data-season="{{ $report->season }}"
                  data-area="{{ (float) $report->land_area }}"
                  data-yield="{{ (float) $report->actual_yield_metric_tons }}"
                  data-expenses="{{ (float) $report->actual_total_expenses }}"
                  data-date="{{ $report->harvest_date->format('Y-m-d') }}"
                  data-search="{{ strtolower($report->crop_type . ' ' . $report->season) }}">
                <td style="font-weight: 600; color: #1e293b;">
                  <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 34px; height: 34px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                      <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2" fill="none"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
                    </div>
                    <span>{{ $report->crop_type }}</span>
                  </div>
                </td>
                <td>
                  <span class="season-badge {{ $report->season }}">
                    <span class="season-badge-dot"></span>
                    {{ ucfirst($report->season) }} Season
                  </span>
                </td>
                <td>{{ number_format((float) $report->land_area, 2) }} ha</td>
                <td>
                  <div class="yield-cell">
                    <b>{{ \App\Helpers\SettingsHelper::formatYield((float) $report->actual_yield_metric_tons) }}</b>
                    <div class="yield-tons">{{ number_format((float) $report->actual_yield_metric_tons, 2) }} metric tons</div>
                  </div>
                </td>
                <td style="font-weight: 700; font-variant-numeric: tabular-nums;">₱{{ number_format((float) $report->actual_total_expenses, 0) }}</td>
                <td style="font-size: 13px; color: #475569; font-weight: 600;">
                  <div style="display: flex; align-items: center; gap: 6px;">
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="#94a3b8" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    {{ $report->harvest_date->format('M d, Y') }}
                  </div>
                </td>
                <td style="text-align: right;">
                  <form method="POST" action="{{ route('farmer.harvests.destroy', $report) }}" data-aqui-confirm="Remove this harvest report? This also removes it from the admin view." data-aqui-danger data-aqui-confirm-label="Remove">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="delete-btn" title="Remove harvest report" aria-label="Remove harvest report">
                      <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7">
                  <div class="empty-state">
                    <div class="empty-icon">
                      <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="1.75" fill="none"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
                    </div>
                    <p style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">No Harvest Reports Found</p>
                    <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.5;">
                      Submit your first harvest report — it will sync to the admin instantly.
                    </p>
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