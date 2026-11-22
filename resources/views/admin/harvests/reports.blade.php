@extends('layouts.sidebar')

@section('title', 'Harvest Reports')

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

  /* Dark sage instead of near-black — the export action should belong to
     the app's green family in light mode (dark mode keeps its slate pill,
     see dark-mode.css). */
  .btn-export {
    appearance: none; -webkit-appearance: none; height: 42px; padding: 0 20px;
    font-size: 13px; font-weight: 700; border-radius: 10px; border: none;
    background: #334c3e; color: #ffffff; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 12px rgba(51, 76, 62, 0.28);
    transition: all 0.2s ease;
  }
  .btn-export:hover { background: #294035; transform: translateY(-1px); }
  .btn-export svg { width: 18px; height: 18px; }

  /* One quiet entry point into the saved report builder. The table stays
     the working view; the generator is a separate page so it never clutters
     this one. */
  .btn-ai-report {
    appearance: none; -webkit-appearance: none; height: 42px; padding: 0 18px;
    font-size: 13px; font-weight: 700; border-radius: 10px;
    border: 1px solid #c7d8cb; background: #f3f8f4; color: #334c3e;
    text-decoration: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.2s ease;
  }
  .btn-ai-report:hover { background: #e7f0e9; border-color: #648268; transform: translateY(-1px); }
  .btn-ai-report svg { width: 18px; height: 18px; }
  [data-theme="dark"] .btn-ai-report { background: #1e293b; border-color: #475569; color: #e2e8f0; }

  /* Bulk-load the association's paper records instead of typing them one by one. */
  .btn-import {
    appearance: none; -webkit-appearance: none; height: 42px; padding: 0 20px;
    font-size: 13px; font-weight: 700; border-radius: 10px;
    border: 1px solid #bbf7d0; background: #f0fdf4; color: #15803d;
    text-decoration: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.2s ease;
  }
  .btn-import:hover { background: #dcfce7; border-color: #86efac; transform: translateY(-1px); }
  .btn-import svg { width: 18px; height: 18px; }

  /* The archive of files this importer was given — kept for the record. */
  .btn-uploads {
    appearance: none; -webkit-appearance: none; height: 42px; padding: 0 20px;
    font-size: 13px; font-weight: 700; border-radius: 10px;
    border: 1px solid #e2e8f0; background: #ffffff; color: #334155;
    text-decoration: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.2s ease;
  }
  .btn-uploads:hover { background: #f8fafc; border-color: #cbd5e1; transform: translateY(-1px); }
  .btn-uploads svg { width: 18px; height: 18px; }

  .page-actions { display: flex; justify-content: flex-end; margin: 0 0 16px; }
  .table-actions { display: flex; gap: 10px; flex-wrap: wrap; }

  /* Phones: the three header actions share one line with a steady, moderate
     gap (same 10px as desktop) — no partitioning the leftover width into
     oversized spaces. The extra-tight block below is for sub-380px phones
     where every pixel counts. */
  @media (max-width: 640px) {
    .table-head { padding: 16px 14px; gap: 12px; }
    .table-actions { gap: 10px; flex-wrap: nowrap; }
    .btn-import, .btn-uploads, .btn-export {
      height: 38px; padding: 0 8px; font-size: 11.5px; gap: 5px; white-space: nowrap;
    }
    .btn-import svg, .btn-uploads svg, .btn-export svg { width: 14px; height: 14px; }
  }
  @media (max-width: 380px) {
    .btn-import, .btn-uploads, .btn-export {
      padding: 0 7px; font-size: 11px; gap: 4px;
    }
    .btn-import svg, .btn-uploads svg, .btn-export svg { width: 13px; height: 13px; }
  }

  /* Flash messages — "Imported 718 harvest reports.", "2 records removed." */
  .page-flash { display: flex; align-items: center; gap: 9px; padding: 11px 16px; border-radius: 12px; font-size: 13.5px; font-weight: 600; margin: 0 0 16px; border: 1px solid; }
  .page-flash--success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
  .page-flash--error { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

  /* Removing wrong records: tick boxes in a narrow first column, plus a bar
     that only appears once at least one record is ticked. */
  th.col-sel, td.col-sel { width: 34px; padding-left: 14px; padding-right: 0; }

  /* Selection bar for "Remove selected": light, muted sage so it sits
     quietly over the table — the loud part is the confirm dialog, not the
     bar. Dark mode keeps the original black pill (see the override below). */
  /* width: max-content is essential — with only left: 50% and no width the
     fixed pill would size itself to the right half of the viewport and
     squeeze the buttons until their labels wrapped to two lines. */
  .hr-selbar { position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%); z-index: 70; display: flex; align-items: center; gap: 14px; width: max-content; max-width: calc(100vw - 16px); background: #f2f6f1; color: #2c3a2f; border: 1px solid #d8e2d5; padding: 10px 12px 10px 18px; border-radius: 999px; box-shadow: 0 14px 34px rgba(31, 41, 33, 0.18); }
  .hr-selbar[hidden] { display: none; }
  .hr-selbar form { display: flex; align-items: center; gap: 10px; margin: 0; }
  .hr-selbar-count { font-size: 13.5px; font-weight: 600; white-space: nowrap; color: #4a5a4d; }
  .hr-selbar-count b { font-size: 14.5px; color: #166534; }
  .hr-selbar-clear { border: 1px solid #ccd8c9; background: #ffffff; color: #5b6b5a; font-size: 13px; font-weight: 700; border-radius: 999px; padding: 7px 14px; cursor: pointer; white-space: nowrap; transition: all 0.15s ease; }
  .hr-selbar-clear:hover { background: #e9efe7; border-color: #b9c8b6; color: #2c3a2f; }
  .hr-selbar-remove { border: 1px solid #bcd4bb; background: #dcecda; color: #166534; font-size: 13px; font-weight: 700; border-radius: 999px; padding: 8px 16px; cursor: pointer; white-space: nowrap; transition: all 0.15s ease; }
  .hr-selbar-remove:hover:not(:disabled) { background: #cfe4cd; border-color: #a6c4a5; color: #0f5132; }
  .hr-selbar-remove:disabled { opacity: 0.45; cursor: not-allowed; }
  .hr-selbar-archive { border: 1px solid #cbd5e1; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700; border-radius: 999px; padding: 8px 16px; cursor: pointer; white-space: nowrap; transition: all 0.15s ease; }
  .hr-selbar-archive:hover:not(:disabled) { background: #eef2f7; border-color: #94a3b8; color: #1e293b; }
  .hr-selbar-archive:disabled { opacity: 0.45; cursor: not-allowed; }

  /* Phones: hug the content instead of stretching edge-to-edge — a full
     width pill reads as huge dead padding on both sides. The pill stays
     centered with a max-width guard, and its own paddings shrink. */
  @media (max-width: 560px) {
    .hr-selbar { left: 50%; right: auto; width: max-content; max-width: calc(100vw - 20px); transform: translateX(-50%); justify-content: center; bottom: 14px; gap: 8px; padding: 8px 10px; }
    .hr-selbar form { gap: 8px; }
    .hr-selbar-count { font-size: 12.5px; }
    .hr-selbar-count b { font-size: 13.5px; }
    .hr-selbar-clear { padding: 6px 12px; font-size: 12.5px; }
    .hr-selbar-remove { padding: 7px 13px; font-size: 12.5px; }
    .hr-selbar-archive { padding: 7px 13px; font-size: 12.5px; }
  }

  /* Dark mode: the black pill stays; only the tints adapt to the dark
     palette (the red is gone here too — the confirm dialog still carries
     the danger tone where the actual decision happens). */
  [data-theme="dark"] .hr-selbar { background: #0f172a; color: #e2e8f0; border-color: #334155; box-shadow: 0 14px 34px rgba(0, 0, 0, 0.5); }
  [data-theme="dark"] .hr-selbar-count { color: #94a3b8; }
  [data-theme="dark"] .hr-selbar-count b { color: #86efac; }
  [data-theme="dark"] .hr-selbar-clear { background: transparent; border-color: #475569; color: #cbd5e1; }
  [data-theme="dark"] .hr-selbar-clear:hover { background: #1e293b; color: #ffffff; }
  [data-theme="dark"] .hr-selbar-remove { background: #1a3a28; border-color: #2f5d3a; color: #a7f3d0; }
  [data-theme="dark"] .hr-selbar-remove:hover:not(:disabled) { background: #215036; border-color: #3c7449; color: #d1fae5; }

  /* Same sage story for the tick boxes: the app's own green instead of a
     warning red, with the dark theme's standard accent in dark mode. */
  .col-sel input[type="checkbox"] { width: 15px; height: 15px; margin: 0; accent-color: #15803d; cursor: pointer; }
  [data-theme="dark"] .col-sel input[type="checkbox"] { accent-color: #4ade80; }
</style>

@php
  // Carry the table's current filters into the report builder so a report
  // can be generated for exactly the rows the officer is looking at.
  $aiReportQuery = http_build_query(array_filter(
      request()->only(['q', 'crop', 'season', 'farmer', 'year', 'month']),
      fn ($value) => is_string($value) && $value !== ''
  ));
@endphp

@if (session('success'))
  <div class="page-flash page-flash--success" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="page-flash page-flash--error" role="alert">{{ session('error') }}</div>
@endif

<x-breadcrumb :items="[
  ['label' => 'Harvest Performance', 'url' => route('admin.harvests.index')],
  ['label' => 'Harvest Reports'],
]"/>

<!-- Page actions: kept outside the table so the header stays about the data. -->
<div class="page-actions">
    <div class="table-actions">
      <a href="{{ route('admin.harvests.reports.generate') . ($aiReportQuery !== '' ? '?' . $aiReportQuery : '') }}" class="btn-ai-report" title="Generate a saved harvest report from the current filters">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
        Generate report
      </a>
      <a href="{{ route('admin.harvests.import') }}" class="btn-import" title="Import historical harvest records from a CSV or Excel file">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Import Records
      </a>
      <a href="{{ route('admin.harvests.uploads') }}" class="btn-uploads" title="Every historical harvest file this importer was ever given">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
        Uploads
      </a>
      <button id="exportBtn" class="btn-export" title="Export filtered data as CSV">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
      </button>
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
      <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Harvest Reports</h2>
      <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Recorded by farmers across the Aquilala network.</p>
    </div>
  </div>
  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
      <div class="app-scroll">
        <table id="adminHarvestsTable" class="data-table">
          <thead>
            <tr>
              <th class="col-sel">
                <input type="checkbox" id="hrSelAll" aria-label="Select every record matching the current filters">
              </th>
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
                  data-search="{{ strtolower($report->reporterName() . ' ' . ($report->crop_type ?? '') . ' ' . ($report->variety ?? '') . ' ' . ($report->season ?? '')) }}"
                  data-farmer="{{ strtolower($report->reporterName()) }}"
                  data-crop="{{ strtolower($report->crop_type ?? '') }}"
                  data-season="{{ strtolower($report->season ?? '') }}"
                  data-area="{{ (float) ($report->land_area ?? 0) }}"
                  data-yield="{{ (float) ($report->actual_yield_metric_tons ?? 0) }}"
                  data-expenses="{{ (float) ($report->actual_total_expenses ?? 0) }}"
                  data-date="{{ $report->harvest_date?->format('Y-m-d') ?? '' }}"
                  data-year="{{ $report->harvest_date?->year }}"
                  data-month="{{ $report->harvest_date?->month }}">
                <td class="col-sel">
                  <input type="checkbox" class="hr-sel" value="{{ $report->id }}"
                         aria-label="Select {{ $report->reporterName() }} — {{ $report->crop_type }} {{ $report->harvest_date->format('M d, Y') }}">
                </td>
                <td>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <x-user-avatar :user="$report->farmer ?? $report->member" :size="32" />
                    <span style="font-weight: 600; color: #1e293b;">{{ $report->reporterName() }}</span>
                  </div>
                </td>
                <td style="font-weight: 600; color: #1e293b;">
                  {{ $report->crop_type }}
                  @if($report->variety)
                    <div style="font-weight: 500; font-size: 11.5px; color: #64748b; margin-top: 2px;">{{ $report->variety }}</div>
                  @endif
                </td>
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
                <td colspan="8" style="text-align: center; padding: 48px 20px; color: #64748b;">
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

<!-- Bulk selection bar: hidden until a record is ticked; the confirm copy is
     rewritten by the script below so it always names the exact count. -->
<div class="hr-selbar" id="hrSelBar" hidden>
  <span class="hr-selbar-count"><b id="hrSelCount">0</b> selected</span>
  <form method="POST" action="{{ route('admin.harvests.reports.remove') }}">
    @csrf
    <input type="hidden" name="ids" id="hrSelIds" value="">
    <button type="button" class="hr-selbar-clear" id="hrSelClear">Clear</button>
  </form>
  <form method="POST" action="{{ route('admin.harvests.reports.archive') }}">
    @csrf
    <input type="hidden" name="ids" id="hrArchiveIds" value="">
    <button type="submit" class="hr-selbar-archive" id="hrSelArchive" disabled
            data-aqui-tone="neutral"
            data-aqui-title="Archive records?"
            data-aqui-confirm-label="Archive records"
            data-aqui-confirm="Selected records will leave Harvest Reports but remain available in the Archive.">
      Archive selected
    </button>
  </form>
  <form method="POST" action="{{ route('admin.harvests.reports.remove') }}">
    @csrf
    <input type="hidden" name="ids" id="hrRemoveIds" value="">
    <button type="submit" class="hr-selbar-remove" id="hrSelRemove" disabled
            data-aqui-tone="danger"
            data-aqui-title="Remove records?"
            data-aqui-confirm-label="Remove records"
            data-aqui-confirm="These records will be removed from Harvest Reports. The files they came from stay in Import Uploads, so you can re-import them any time. Linked planting batches become available to report again.">
      Remove selected
    </button>
  </form>
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
        var cells = row.querySelectorAll('td:not(.col-sel)');
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

  // Row selection for "Remove selected". Selection always means: rows that
  // are ticked AND still match the toolbar's search/filters — the toolbar
  // announces its matched set (all pages, not just the visible one) through
  // the ttb:render event, so select-all behaves the way it does in mail
  // apps: everything your filters are showing, on every client-side page.
  (function () {
    var table = document.getElementById('adminHarvestsTable');
    var bar = document.getElementById('hrSelBar');
    var selAll = document.getElementById('hrSelAll');
    if (!table || !bar || !selAll) return;

    var countEl = document.getElementById('hrSelCount');
    var idsInput = document.getElementById('hrSelIds');
    var archiveIdsInput = document.getElementById('hrArchiveIds');
    var removeIdsInput = document.getElementById('hrRemoveIds');
    var clearBtn = document.getElementById('hrSelClear');
    var removeBtn = document.getElementById('hrSelRemove');
    var archiveBtn = document.getElementById('hrSelArchive');
    var selected = new Set();
    var matched = [];

    function rowId(row) {
      return String(row.id || '').replace(/^report-/, '');
    }

    function sync() {
      table.querySelectorAll('tbody tr').forEach(function (row) {
        var box = row.querySelector('.hr-sel');
        if (box) box.checked = selected.has(rowId(row));
      });

      var matchedSelected = matched.filter(function (row) { return selected.has(rowId(row)); }).length;
      selAll.disabled = matched.length === 0;
      selAll.checked = matched.length > 0 && matchedSelected === matched.length;
      selAll.indeterminate = matchedSelected > 0 && matchedSelected < matched.length;

      var total = selected.size;
      var plural = total === 1 ? '' : 's';
      bar.hidden = total === 0;
      countEl.textContent = String(total);
      idsInput.value = Array.from(selected).join(',');
      archiveIdsInput.value = idsInput.value;
      removeIdsInput.value = idsInput.value;
      removeBtn.disabled = total === 0;
      archiveBtn.disabled = total === 0;
      removeBtn.setAttribute('data-aqui-title', 'Remove ' + total + ' record' + plural + '?');
      removeBtn.setAttribute('data-aqui-confirm',
        total + ' record' + plural + ' will be removed from Harvest Reports. ' +
        'The files they came from stay in Import Uploads, so you can re-import them any time. ' +
        'Linked planting batches become available to report again.');
    }

    document.addEventListener('ttb:render', function (e) {
      if (!table.isConnected) return; // stale listener from a Turbo-swapped page
      if (!e.detail || e.detail.table !== 'adminHarvestsTable') return;
      matched = e.detail.matched || [];
      // Never keep a selection outside what the filters currently show.
      var keep = new Set(matched.map(rowId));
      selected.forEach(function (id) { if (!keep.has(id)) selected.delete(id); });
      sync();
    });

    // On Turbo visits the toolbar can render before this script evaluates;
    // the engine parks its matched set on the table node for exactly that
    // ordering, so pick it up here and on every later visit.
    function refresh() {
      if (!table.isConnected) return;
      matched = table.__ttbMatched || [];
      sync();
    }
    document.addEventListener('turbo:load', refresh);
    refresh();

    selAll.addEventListener('change', function () {
      matched.forEach(function (row) {
        var id = rowId(row);
        if (selAll.checked) selected.add(id); else selected.delete(id);
      });
      sync();
    });

    table.addEventListener('change', function (e) {
      var box = e.target.closest ? e.target.closest('.hr-sel') : null;
      if (!box) return;
      var id = String(box.value);
      if (box.checked) selected.add(id); else selected.delete(id);
      sync();
    });

    clearBtn.addEventListener('click', function () {
      selected.clear();
      sync();
    });

    sync();
  })();
</script>
@endsection
