@extends('layouts.sidebar')

@section('title', 'All Harvest Reports')

@section('content')
<style>
  /* Old .filter-bar styles removed — filtering now uses the shared table toolbar.
     Table base style lives in the shared .data-table rules (layouts.sidebar). */

  .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 20px; }
  .table-head { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }

  .season-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; letter-spacing: 0.01em;
  }
  .season-badge.wet { background: #edf1e9; color: #648268; }
  .season-badge.dry { background: #edf1e9; color: #648268; }
  .season-badge-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }

  .yield-cell { font-variant-numeric: tabular-nums; }
  .yield-tons { font-size: 11.5px; color: #94a3b8; font-weight: 600; }

  /* A notice about a harvest report opens this page on that report. */
  #adminHarvestsTable tbody tr:target > td { background: #F1F6F1; }
  #adminHarvestsTable tbody tr:target > td:first-child { box-shadow: inset 3px 0 0 #648268; }

  .btn-export {
    appearance: none; -webkit-appearance: none; height: 42px; padding: 0 20px;
    font-size: 13px; font-weight: 700; border-radius: 10px; border: none;
    background: #1e293b; color: #ffffff; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 12px rgba(30, 41, 59, 0.25);
    transition: all 0.2s ease;
  }
  .btn-export:hover { background: #0f172a; transform: translateY(-1px); }
  .btn-export svg { width: 18px; height: 18px; }
</style>

<x-breadcrumb :items="[
  ['label' => 'Harvest Performance', 'url' => route('admin.harvests.index')],
  ['label' => 'All Harvest Reports'],
]"/>

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
        ['key' => 'year', 'label' => 'All Years', 'options' => $yearOptions ?? []],
        ['key' => 'month', 'label' => 'All Months', 'options' => $monthOptions ?? []],
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
  <div class="table-head">
    <div>
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">All Harvest Reports</h2>
      <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Recorded by farmers across the Aquilala network.</p>
    </div>
    <button id="exportBtn" class="btn-export" title="Export filtered data as CSV">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      Export CSV
    </button>
  </div>
  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
      <div class="app-scroll">
        <table id="adminHarvestsTable" class="data-table">
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
              <tr id="report-{{ $report->id }}"
                  data-search="{{ strtolower(($report->farmer?->name ?? '') . ' ' . ($report->crop_type ?? '') . ' ' . ($report->season ?? '')) }}"
                  data-farmer="{{ strtolower($report->farmer?->name ?? '') }}"
                  data-crop="{{ strtolower($report->crop_type ?? '') }}"
                  data-season="{{ strtolower($report->season ?? '') }}"
                  data-area="{{ (float) ($report->land_area ?? 0) }}"
                  data-yield="{{ (float) ($report->actual_yield_metric_tons ?? 0) }}"
                  data-expenses="{{ (float) ($report->actual_total_expenses ?? 0) }}"
                  data-date="{{ $report->harvest_date?->format('Y-m-d') ?? '' }}"
                  data-year="{{ $report->harvest_date?->year }}"
                  data-month="{{ $report->harvest_date?->month }}">
                <td>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <x-user-avatar :user="$report->farmer" :size="32" />
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

<div id="harvestToast" style="position:fixed;bottom:24px;left:50%;transform:translate(-50%,16px);background:#143d23;color:#eaf7e3;padding:12px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(20,61,35,0.28);opacity:0;pointer-events:none;transition:all .25s ease;z-index:9999;"></div>

<script>
  (function () {
    var toast = document.getElementById('harvestToast');
    var toastTimer = null;
    function showToast(msg) {
      toast.textContent = msg;
      toast.style.opacity = '1';
      toast.style.transform = 'translate(-50%, 0)';
      clearTimeout(toastTimer);
      toastTimer = setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transform = 'translate(-50%, 16px)';
      }, 2600);
    }

    var rows = Array.prototype.slice.call(document.querySelectorAll('#adminHarvestsTable tbody tr'));

    document.getElementById('exportBtn').addEventListener('click', function () {
      var shown = rows.filter(function (r) { return r.style.display !== 'none'; });
      if (shown.length === 0) { showToast('No records to export.'); return; }

      var headers = ['Farmer', 'Crop', 'Season', 'Area (ha)', 'Yield (MT)', 'Expenses (₱)', 'Harvested'];
      var lines = [headers.join(',')];

      shown.forEach(function (row) {
        var cells = row.querySelectorAll('td');
        var csv = [];
        for (var i = 0; i < cells.length; i++) {
          var text = cells[i].innerText.trim().replace(/"/g, '""');
          csv.push('"' + text + '"');
        }
        lines.push(csv.join(','));
      });

      var blob = new Blob([lines.join('\n')], { type: 'text/csv' });
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      var now = new Date();
      var timestamp = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
      a.download = 'harvest-reports-' + timestamp + '.csv';
      a.click();
      URL.revokeObjectURL(url);
      showToast('Exported ' + shown.length + ' records to CSV');
    });
  })();
</script>
@endsection
