@extends('layouts.sidebar')

@section('title', 'Harvest Reports')

@section('content')
@include('partials.admin-tabs', [
  'tabs' => [
    ['key' => 'demand',   'label' => 'Demand Trends',       'url' => route('admin.quotations.index')],
    ['key' => 'harvests', 'label' => 'Harvest Performance',  'url' => route('admin.harvests.index')],
    ['key' => 'expenses', 'label' => 'Expenses',             'url' => route('admin.analytics.expenses')],
  ],
  'activeTab' => 'harvests',
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
  .stat-value { font-size: 26px; font-weight: 800; color: #143d23; line-height: 1; }
  .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; font-weight: 500; }

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

  .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th { background: #fafbfa; padding: 14px 16px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; }
  td { padding: 14px 16px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
  tbody tr:last-child td { border-bottom: none; }
  tbody tr:hover td { background: #fafbfa; }

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
  .season-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; letter-spacing: 0.01em;
  }
  .season-badge.wet { background: #eff6ff; color: #1d4ed8; }
  .season-badge.dry { background: #fffbeb; color: #b45309; }
  .season-badge-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

  .yield-cell { font-variant-numeric: tabular-nums; }
  .yield-tons { font-size: 11.5px; color: #94a3b8; font-weight: 600; }
</style>

<div class="header-panel" style="margin-bottom: 10px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Harvest Performance</h1>
</div>

<!-- System-wide metrics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 24px;">
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $stats['farmers'] }}</div>
      <div class="stat-label">Farmers Reporting</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#143d23" stroke-width="1.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $stats['reports'] }}</div>
      <div class="stat-label">Harvest Reports</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#16a34a" stroke-width="1.5" fill="none"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">{{ $stats['yield'] }}</div>
      <div class="stat-label">Total Yield</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#0284c7" stroke-width="1.5" fill="none"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
  </div>
  <div class="stat-card">
    <div>
      <div class="stat-value">₱{{ number_format($stats['expenses'], 0) }}</div>
      <div class="stat-label">Total Expenses</div>
    </div>
    <svg viewBox="0 0 24 24" width="24" height="24" stroke="#7c3aed" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="3" x2="8" y2="21"/><path d="M8 3h6.5a4.5 4.5 0 0 1 0 9H8"/></svg>
  </div>
</div>

<x-table-toolbar
    uid="admin-harvests"
    work-table="adminHarvestsTable"
    per-page="10"
    noun="reports"
    search-placeholder="Search by crop, farmer, or season…"
    :filters="[
        ['key' => 'farmer', 'label' => 'All Farmers', 'options' => collect($farmerOptions)->map(fn($n) => [strtolower($n), $n])->all()],
        ['key' => 'crop', 'label' => 'All Crops', 'options' => $allReports->pluck('crop_type')->filter()->unique()->sort()->values()->map(fn($c) => [strtolower($c), $c])->all()],
        ['key' => 'season', 'label' => 'All Seasons', 'options' => [['wet', 'Wet Season'], ['dry', 'Dry Season']]],
    ]"
    :sorts="[
        ['key' => 'date', 'dir' => -1, 'type' => 'date', 'label' => 'Harvested (newest)'],
        ['key' => 'date', 'dir' => 1, 'type' => 'date', 'label' => 'Harvested (oldest)'],
        ['key' => 'yield', 'dir' => -1, 'type' => 'num', 'label' => 'Yield (highest)'],
        ['key' => 'yield', 'dir' => 1, 'type' => 'num', 'label' => 'Yield (lowest)'],
        ['key' => 'expenses', 'dir' => -1, 'type' => 'num', 'label' => 'Expenses (highest)'],
        ['key' => 'area', 'dir' => -1, 'type' => 'num', 'label' => 'Area (largest)'],
        ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop (A–Z)'],
    ]"
  />

<!-- Table -->
<div class="data-table-container">
  <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
    <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">All Harvest Reports</h2>
    <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Recorded by farmers across the Aquilala network.</p>
  </div>
  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
      <div class="app-scroll">
        <table id="adminHarvestsTable">
          <thead>
            <tr>
              <th>Farmer</th>
              <th>Crop</th>
              <th>Season</th>
              <th>Area</th>
              <th>Yield</th>
              <th>Expenses</th>
              <th>Harvested</th>
            </tr>
          </thead>
          <tbody>
            @forelse($reports as $report)
              <tr data-search="{{ strtolower(($report->farmer?->name ?? '') . ' ' . ($report->crop_type ?? '') . ' ' . ($report->season ?? '')) }}"
                  data-farmer="{{ strtolower($report->farmer?->name ?? '') }}"
                  data-crop="{{ strtolower($report->crop_type ?? '') }}"
                  data-season="{{ strtolower($report->season ?? '') }}"
                  data-area="{{ (float) ($report->land_area ?? 0) }}"
                  data-yield="{{ (float) ($report->actual_yield_metric_tons ?? 0) }}"
                  data-expenses="{{ (float) ($report->actual_total_expenses ?? 0) }}"
                  data-date="{{ $report->harvest_date?->format('Y-m-d') ?? '' }}">
                <td>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <div class="avatar" style="background: #dbeafe; color: #1e40af;">
                      @php $w = explode(' ', trim($report->farmer?->name ?? '?')); echo count($w) >= 2 ? mb_substr($w[0], 0, 1).mb_substr(end($w), 0, 1) : '?'; @endphp
                    </div>
                    <span style="font-weight: 600; color: #1e293b;">{{ $report->farmer?->name ?? 'Unknown' }}</span>
                  </div>
                </td>
                <td style="font-weight: 600; color: #1e293b;">{{ $report->crop_type }}</td>
                <td>
                  <span class="season-badge {{ $report->season }}">
                    <span class="season-badge-dot"></span>
                    {{ ucfirst($report->season) }}
                  </span>
                </td>
                <td>{{ number_format((float) $report->land_area, 2) }} ha</td>
                <td>
                  <div class="yield-cell">
                    <b>{{ \App\Helpers\SettingsHelper::formatYield((float) $report->actual_yield_metric_tons) }}</b>
                    <div class="yield-tons">{{ number_format((float) $report->actual_yield_metric_tons, 2) }} tons</div>
                  </div>
                </td>
                <td style="font-weight: 700; font-variant-numeric: tabular-nums;">₱{{ number_format((float) $report->actual_total_expenses, 0) }}</td>
                <td style="font-size: 13px; color: #475569; font-weight: 600;">{{ $report->harvest_date->format('M d, Y') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" style="text-align: center; padding: 48px 20px; color: #64748b;">
                  <svg viewBox="0 0 24 24" width="36" height="36" stroke="#94a3b8" stroke-width="1.5" fill="none" style="margin-bottom: 8px;">
                    <path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/>
                  </svg>
                  <p style="font-size: 15px; font-weight: 600; margin: 0;">No Harvest Reports in System</p>
                  <p style="font-size: 13px; color: #94a3b8; margin: 4px 0 0 0;">Reports recorded by farmers will appear here automatically.</p>
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