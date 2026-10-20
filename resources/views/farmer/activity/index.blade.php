@extends('layouts.sidebar')

@section('title', 'Activity History')

@section('content')
<!-- Load AlpineJS -->


<!-- Load Flatpickr CSS & JS for custom datepicker popups -->
<link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
<script src="{{ asset('js/flatpickr.min.js') }}"></script>

<style>
  /* Base Layout & Page Header */
  .page-header { margin-bottom: 24px; }
  .page-title { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0 0 4px 0; letter-spacing: -0.025em; }
  .page-subtitle { color: #64748b; font-size: 14px; margin: 0; line-height: 1.5; }

  /* Dashboard Stats Grid */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }
  .stat-pill {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  }
  .stat-pill:hover {
    transform: translateY(-2px);
    border-color: #cbd5e1;
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -4px rgba(0,0,0,0.02);
  }
  .stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .stat-val { font-size: 24px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; }
  .stat-lbl { font-size: 12px; font-weight: 600; margin-top: 3px; letter-spacing: 0.01em; }

  .stat-pill.green .stat-icon  { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
  .stat-pill.green .stat-val   { color: #14532d; }
  .stat-pill.green .stat-lbl   { color: #166534; }

  .stat-pill.amber .stat-icon { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
  .stat-pill.amber .stat-val { color: #78350f; }
  .stat-pill.amber .stat-lbl { color: #92400e; }

  .stat-pill.red .stat-icon   { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
  .stat-pill.red .stat-val   { color: #7f1d1d; }
  .stat-pill.red .stat-lbl   { color: #991b1b; }

  .stat-pill.slate .stat-icon { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
  .stat-pill.slate .stat-val { color: #0f172a; }
  .stat-pill.slate .stat-lbl { color: #475569; }

  /* Smart Filter Toolbar */
  /* position/z-index: in dark mode the generated dark-mode.css gives this bar and
     .timeline-card a frost backdrop-filter, and backdrop-filter forces a stacking
     context — the later-in-tree timeline then painted above the bar's dropdown
     menus (z 600 stays inside the bar's own context), making them unclickable.
     A positioned z above the timeline keeps the open menus on top in both themes. */
  .activity-filter-bar {
    position: relative;
    z-index: 20;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 16px;
    margin-bottom: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }

  .filter-main-row {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
  }

  /* Search Field Styling */
  .af-search {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 14px;
    height: 42px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    transition: all 0.2s ease;
  }
  .af-search:focus-within {
    border-color: #16a34a;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.08);
  }
  .af-search svg { flex-shrink: 0; color: #94a3b8; }
  .af-search input {
    border: none; outline: none; background: transparent;
    font-size: 13px; color: #0f172a; width: 100%; font-family: inherit; font-weight: 500;
  }
  .af-search input::placeholder { color: #94a3b8; }

  /* Collapsible Filter Panel */
  .filter-drawer {
    display: none;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid #f1f5f9;
  }
  .filter-drawer.is-open {
    display: block;
  }

  .drawer-inner {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
  }

  /* Custom Alpine Select Dropdowns */
  .ncb-group-inline { position: relative; min-width: 170px; }
  .ncb-select-trigger {
    width: 100%; height: 41px; padding: 0 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 13px; color: #1e293b; background-color: #fff; transition: all 0.2s ease;
    box-sizing: border-box; font-family: inherit; cursor: pointer;
    display: flex; justify-content: space-between; align-items: center; user-select: none;
  }
  .ncb-select-trigger.placeholder { color: #94a3b8; }
  .ncb-select-trigger:hover { border-color: #cbd5e1; }
  .ncb-select-trigger.active { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,0.08); outline: none; }
  .ncb-select-arrow { transition: transform 0.2s ease; color: #64748b; flex-shrink: 0; }
  .ncb-select-arrow.rotate { transform: rotate(180deg); }

  .ncb-select-menu {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 10px rgba(0,0,0,0.05);
    max-height: 200px; overflow-y: auto; z-index: 600; padding: 4px;
    scrollbar-width: thin;
  }
  .ncb-select-option {
    padding: 8px 12px; font-size: 13px; color: #1e293b; border-radius: 6px;
    cursor: pointer; transition: background 0.15s ease, color 0.15s ease;
  }
  .ncb-select-option:hover { background: #f1f5f9; color: #16a34a; }
  .ncb-select-option.selected { background: #f0fdf4; color: #16a34a; font-weight: 600; }

  /* Date Inputs & Icon Wrappers */
  .af-range {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
  }
  .ncb-date-wrapper { position: relative; width: 145px; }
  .ncb-date-wrapper input.custom-datepicker {
    width: 100%; height: 41px; padding: 0 32px 0 12px; border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 13px; color: #1e293b; background-color: #fff; transition: all 0.2s ease;
    box-sizing: border-box; font-family: inherit; cursor: pointer; outline: none;
  }
  .ncb-date-wrapper input.custom-datepicker:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.08);
  }
  .ncb-date-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #16a34a; pointer-events: none; }

  /* Custom Flatpickr Calendar Override Styles */
  .flatpickr-calendar {
    background: #ffffff !important;
    border-radius: 14px !important;
    border: 1.5px solid #e2e8f0 !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12) !important;
    font-family: inherit !important;
    padding: 12px 14px !important;
    width: 315px !important;
    box-sizing: border-box !important;
    z-index: 9999 !important;
  }
  .flatpickr-calendar.arrowTop:before, .flatpickr-calendar.arrowTop:after { display: none !important; }
  .flatpickr-innerContainer, .flatpickr-rContainer { width: 100% !important; }
  .flatpickr-days { width: 100% !important; }
  .dayContainer { width: 100% !important; min-width: 100% !important; max-width: 100% !important; }
  .flatpickr-months { display: flex !important; align-items: center !important; margin-bottom: 8px !important; }
  .flatpickr-months .flatpickr-month { background: #ffffff !important; color: #1e293b !important; fill: #1e293b !important; height: 34px !important; }
  .flatpickr-current-month { padding: 0 !important; display: flex !important; align-items: center !important; justify-content: center !important; }
  .flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 700 !important; color: #1e293b !important; }
  .flatpickr-current-month input.cur-year { font-weight: 700 !important; color: #1e293b !important; }
  .flatpickr-months .flatpickr-prev-month:hover svg, .flatpickr-months .flatpickr-next-month:hover svg { fill: #16a34a !important; }
  span.flatpickr-weekday { color: #64748b !important; font-weight: 700 !important; font-size: 11px !important; }
  .flatpickr-day { border-radius: 8px !important; color: #334155 !important; font-weight: 500 !important; height: 36px !important; line-height: 36px !important; margin-top: 2px !important; }
  .flatpickr-day:hover { background: #f1f5f9 !important; border-color: transparent !important; color: #16a34a !important; }
  .flatpickr-day.selected, .flatpickr-day.selected:hover { background: #16a34a !important; border-color: #16a34a !important; color: #ffffff !important; font-weight: 700 !important; }
  .flatpickr-day.today { border-color: #16a34a !important; font-weight: 700 !important; color: #16a34a !important; }
  .flatpickr-day.today.selected { color: #ffffff !important; }

  /* Filter toggle wears the shared .btn-filter look (layouts.sidebar) —
     only its layout hook remains here so the drawer JS keeps working. */
  .af-btn-toggle { flex-shrink: 0; }

  /* Apply Filters Button - Updated to Green Theme */
  .af-btn-apply {
    appearance: none; -webkit-appearance: none;
    height: 41px;
    padding: 0 20px;
    font-size: 13px;
    font-weight: 700;
    border-radius: 10px;
    border: none;
    background: #16a34a;
    color: #ffffff;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
    transition: all 0.2s ease;
  }
  .af-btn-apply:hover {
    background: #15803d;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(22, 163, 74, 0.3);
  }

  .af-clear {
    height: 41px;
    padding: 0 14px;
    border-radius: 10px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.2s ease;
  }
  .af-clear:hover { background: #fee2e2; }

  .af-result-count {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
    margin-left: auto;
    white-space: nowrap;
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 20px;
  }

  .af-filter-summary {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-top: 10px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #166534;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    line-height: 1;
  }
  .af-filter-summary svg { flex-shrink: 0; color: #16a34a; }
  .af-filter-clear {
    margin-left: auto;
    color: #dc2626;
    text-decoration: none;
    font-weight: 700;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 6px;
    padding: 3px 8px;
    transition: background 0.15s ease;
    white-space: nowrap;
  }
  .af-filter-clear:hover { background: #fee2e2; }

  /* Timeline Card & List Styling */
  .timeline-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03), 0 4px 12px rgba(0,0,0,0.01);
    border: 1px solid #e2e8f0;
    overflow: hidden;
  }

  .activity-item {
    display: flex;
    gap: 16px;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.15s ease;
  }
  .activity-item:last-child { border-bottom: none; }
  .activity-item:hover { background: #f8fafc; }

  .activity-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }

  /* Empty State View */
  .empty-state {
    text-align: center; padding: 64px 24px;
    background: #ffffff;
  }
  .empty-icon {
    width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
  }
</style>

<div class="page-header">
  <h1 class="page-title">Activity History</h1>
  <p class="page-subtitle">Your complete history — sign-ins, cost quotations generated, crops planted, and system logs.</p>
</div>

<!-- Stats Row -->
<div class="stats-row kpi-strip">
  <div class="stat-pill green">
    <div class="stat-icon">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <div>
      <div class="stat-val">{{ $totalQuotations ?? 0 }}</div>
      <div class="stat-lbl">Quotations Generated</div>
    </div>
  </div>
  <div class="stat-pill amber">
    <div class="stat-icon">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    </div>
    <div>
      <div class="stat-val">{{ $totalCrops ?? 0 }}</div>
      <div class="stat-lbl">Crop Activities</div>
    </div>
  </div>
  <div class="stat-pill red">
    <div class="stat-icon">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
    </div>
    <div>
      <div class="stat-val">{{ $totalDeleted ?? 0 }}</div>
      <div class="stat-lbl">Quotations Deleted</div>
    </div>
  </div>
  <div class="stat-pill slate">
    <div class="stat-icon">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
    </div>
    <div>
      <div class="stat-val">{{ $totalLogins ?? 0 }}</div>
      <div class="stat-lbl">Logins</div>
    </div>
  </div>
</div>

<!-- Smart Filter Bar -->
<div class="activity-filter-bar">
  <form id="activityFilterForm" method="GET" action="{{ route('farmer.activity.index') }}">
    
    <!-- Top Row: Search Input & Toggle Button -->
    <div class="filter-main-row">
      <div class="af-search">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Search activity…" autocomplete="off">
      </div>

      <button type="button" class="af-btn-toggle btn-filter" id="filterToggleBtn"
              aria-label="Show filter options" title="Filters">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
      </button>

      <span class="af-result-count">
        {{ isset($activities) ? $activities->total() : 0 }} {{ Str::plural('result', isset($activities) ? $activities->total() : 0) }}
      </span>
    </div>

    <!-- Filter Summary Subtext -- shows exactly what filtering is currently applied -->
    @php
      $activeDateRange = null;
      $activeType = ($type ?? false) ? ucwords(str_replace(['.', '_', ' '], [' → ', ' ', ' '], $type)) : null;
      $periodLabelsMap = [
        'today' => 'today', '7days' => 'last 7 days', '30days' => 'last 30 days', 'month' => 'this month',
      ];
      $customFrom = ($from ?? false) ? \Illuminate\Support\Carbon::parse($from)->format('M d, Y') : null;
      $customTo   = ($to ?? false) ? \Illuminate\Support\Carbon::parse($to)->format('M d, Y') : null;

      $filterParts = [];
      if ($customFrom && $customTo && $customFrom === $customTo) {
        $activeDateRange = $customFrom;
      } elseif ($customFrom || $customTo) {
        $activeDateRange = ($customFrom ?? '…') . ' – ' . ($customTo ?? 'today');
      } elseif (($period ?? 'all') !== 'all' && isset($periodLabelsMap[$period ?? ''])) {
        $activeDateRange = $periodLabelsMap[$period ?? ''];
      }

      if ($activeDateRange) $filterParts[] = "date: {$activeDateRange}";
      if ($activeType) $filterParts[] = "type: {$activeType}";
      if ($search ?? false) $filterParts[] = "search: \"{$search}\"";
      $filterSummary = implode(' · ', $filterParts);
    @endphp

    @if($filterSummary)
      <div class="af-filter-summary">
        <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span>Results for {{ $filterSummary }}</span>
        <a href="{{ route('farmer.activity.index') }}" class="af-filter-clear">Clear all</a>
      </div>
    @endif

    <!-- Hidden Filter Drawer -->
    <div class="filter-drawer {{ (($period ?? 'all') !== 'all' || ($from ?? false) || ($to ?? false) || ($type ?? false)) ? 'is-open' : '' }}" id="filterDrawer">
      <div class="drawer-inner">

        <!-- Custom Period Dropdown -->
        @php
          $periodLabels = [
            'all' => 'All Time',
            'today' => 'Today',
            '7days' => 'Last 7 Days',
            '30days' => 'Last 30 Days',
            'month' => 'This Month',
          ];
          $currentPeriod = $period ?? 'all';
        @endphp
        <div class="ncb-group-inline" x-data="{ open: false, value: '{{ $currentPeriod }}', label: '{{ $periodLabels[$currentPeriod] ?? 'All Time' }}' }">
          <input type="hidden" name="period" :value="value">
          <div class="ncb-select-trigger" :class="{ 'active': open }" @click="open = !open" @click.outside="open = false" tabindex="0">
            <span x-text="label">{{ $periodLabels[$currentPeriod] ?? 'All Time' }}</span>
            <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>
          <div class="ncb-select-menu" x-show="open" x-cloak style="display: none;">
            @foreach($periodLabels as $val => $lbl)
              <div class="ncb-select-option" :class="{ 'selected': value === '{{ $val }}' }" @click="value = '{{ $val }}'; label = '{{ $lbl }}'; open = false">
                {{ $lbl }}
              </div>
            @endforeach
          </div>
        </div>

        <!-- Custom Activity Type Dropdown -->
        @php
          $currentType = $type ?? '';
          $currentTypeLabel = 'All Activity Types';
          if ($currentType) {
            $currentTypeLabel = ucwords(str_replace(['.', '_', ' '], [' → ', ' ', ' '], $currentType));
          }
        @endphp
        <div class="ncb-group-inline" x-data="{ open: false, value: '{{ $currentType }}', label: '{{ $currentTypeLabel }}' }">
          <input type="hidden" name="type" :value="value">
          <div class="ncb-select-trigger" :class="{ 'active': open }" @click="open = !open" @click.outside="open = false" tabindex="0">
            <span x-text="label">{{ $currentTypeLabel }}</span>
            <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>
          <div class="ncb-select-menu" x-show="open" x-cloak style="display: none;">
            <div class="ncb-select-option" :class="{ 'selected': value === '' }" @click="value = ''; label = 'All Activity Types'; open = false">
              All Activity Types
            </div>
            @if(isset($uniqueTypes) && is_iterable($uniqueTypes))
              @foreach($uniqueTypes as $t)
                @php $formatted = ucwords(str_replace(['.', '_', ' '], [' → ', ' ', ' '], $t)); @endphp
                <div class="ncb-select-option" :class="{ 'selected': value === '{{ $t }}' }" @click="value = '{{ $t }}'; label = '{{ $formatted }}'; open = false">
                  {{ $formatted }}
                </div>
              @endforeach
            @endif
          </div>
        </div>

        <!-- Date Range Selection with Custom Flatpickr -->
        <div class="af-range">
          <div class="ncb-date-wrapper">
            <input type="text" name="from" value="{{ $from ?? '' }}" class="custom-datepicker" placeholder="From date" aria-label="From date">
            <svg class="ncb-date-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <span>to</span>
          <div class="ncb-date-wrapper">
            <input type="text" name="to" value="{{ $to ?? '' }}" class="custom-datepicker" placeholder="To date" aria-label="To date">
            <svg class="ncb-date-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
        </div>

        <!-- Apply Button -->
        <button type="submit" class="af-btn-apply">Apply Filters</button>

        <!-- Clear Link -->
        @if(($search ?? false) || ($period ?? '') !== 'all' || ($from ?? false) || ($to ?? false) || ($type ?? false))
          <a href="{{ route('farmer.activity.index') }}" class="af-clear">Clear</a>
        @endif
      </div>
    </div>

  </form>
</div>

<!-- Activity Timeline -->
<div class="timeline-card">
  <div data-paginated>
  @if(isset($activities) && $activities->count() > 0)
    @foreach($activities as $activity)
      <div class="activity-item">
        <div class="activity-icon" style="background: {{ $activity->bg ?? '#f8fafc' }}; color: {{ $activity->color ?? '#475569' }};">
          {!! $activity->icon ?? '' !!}
        </div>
        <div style="flex: 1; min-width: 0;">
          <div style="font-size: 14px; color: #0f172a; line-height: 1.4; font-weight: 500;">
            {{ $activity->display_description ?? '' }}
          </div>
          @if(isset($activity->created_at))
            <div style="font-size: 12px; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 6px; font-weight: 500;">
              <svg viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              <span>{{ optional($activity->created_at)->diffForHumans() }}</span>
              <span>&middot;</span>
              <span>{{ optional($activity->created_at)->format('M d, Y \a\t g:i A') }}</span>
            </div>
          @endif
        </div>
      </div>
    @endforeach

    {{ $activities->withQueryString()->links('partials.pagination') }}
  @else
    <div class="empty-state">
      <div class="empty-icon">
        <svg viewBox="0 0 24 24" width="28" height="28" stroke="#94a3b8" stroke-width="1.75" fill="none">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
      </div>
      <p style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">No Activity Found</p>
      <p style="font-size: 14px; color: #64748b; margin: 0; line-height: 1.5;">
        @if(($search ?? false) || ($period ?? '') !== 'all' || ($from ?? false) || ($to ?? false) || ($type ?? false))
          No activities match your current filters. Try adjusting or clearing them.
        @else
          Start generating cost quotations or planting crops to see your activity here.
        @endif
      </p>
    </div>
  @endif
  </div>
</div>

<!-- Scripts for Flatpickr Initialization and Drawer Toggle -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Drawer toggle handler
    const toggleBtn = document.getElementById('filterToggleBtn');
    const drawer = document.getElementById('filterDrawer');

    if (toggleBtn && drawer) {
      toggleBtn.addEventListener('click', function () {
        drawer.classList.toggle('is-open');
      });
    }

    // Flatpickr setup — restrict selection to today or earlier (no future data)
    if (typeof flatpickr !== "undefined") {
      flatpickr(".custom-datepicker", {
        dateFormat: "Y-m-d",
        disableMobile: true,
        maxDate: "today",
        defaultDate: null,
        onChange: function (selectedDates, dateStr, instance) {
          // Ensure the "To" date is never before the "From" date in the same picker
          const fromInput = document.querySelector('input[name="from"]');
          const toInput = document.querySelector('input[name="to"]');
          if (fromInput && toInput && fromInput._flatpickr && toInput._flatpickr) {
            if (fromInput === instance.element && toInput._flatpickr.selectedDates[0] && dateStr) {
              const fromD = new Date(dateStr);
              const toD = toInput._flatpickr.selectedDates[0];
              if (fromD > toD) {
                toInput._flatpickr.clear();
              }
            }
          }
        }
      });
    }
  });
</script>

@push('scripts')
  @include('partials.ajax-pagination')
@endpush
@endsection