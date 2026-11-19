@extends('layouts.sidebar')

@section('title', 'Submit Harvest Report')

@section('content')
<!-- Load Flatpickr CSS & JS for custom datepicker popup (matched to My Crops) -->
<link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
<script src="{{ asset('js/flatpickr.min.js') }}"></script>

<style>
  .harvest-submit-page { max-width: 880px; }
  .header-panel { margin-bottom: 24px; }

  .sync-note {
    display: flex; align-items: flex-start; gap: 11px;
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border: 1px solid #bbf7d0; border-radius: 12px; padding: 13px 16px;
    margin-bottom: 22px;
  }
  .sync-note svg { flex-shrink: 0; margin-top: 1px; }
  .sync-note b { font-size: 13px; color: #166534; }
  .sync-note p { margin: 2px 0 0 0; font-size: 12.5px; color: #3f5144; line-height: 1.55; }
  .sync-note p a { color: #4A7A54; font-weight: 700; text-decoration: underline; }
  .sync-note.neutral { background: linear-gradient(135deg, #f8fafc, #f1f5f9); border-color: #e2e8f0; }
  .sync-note.neutral b { color: #334155; }
  .sync-note.neutral p { color: #64748b; }

  /* ── Two tabs: pick a planting, or fill the form ── */
  .tabs {
    display: flex; gap: 4px; margin-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
  }
  .tab {
    appearance: none; -webkit-appearance: none; border: none; background: none;
    font-family: inherit; cursor: pointer;
    font-size: 13.5px; font-weight: 700; color: #64748b;
    padding: 11px 16px; margin-bottom: -1px;
    border-bottom: 2px solid transparent; border-radius: 9px 9px 0 0;
    display: inline-flex; align-items: center; gap: 8px;
    transition: color .18s ease, background .18s ease;
  }
  .tab:hover { color: #14532d; background: #f4f7f4; }
  .tab.active { color: #14532d; border-bottom-color: #4A7A54; }
  .tab svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .tab-count {
    font-size: 11px; font-weight: 700; line-height: 1.6;
    background: #edf1e9; color: #4A7A54; border-radius: 999px; padding: 0 7px;
  }
  .tab.active .tab-count { background: #4A7A54; color: #fff; }
  .tab-panel[hidden] { display: none; }

  /* ── Planting cards (tab 1) ── */
  .planting-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(272px, 1fr));
    gap: 14px;
  }
  .planting-card {
    background: #ffffff; border: 1px solid #e5e7eb; border-radius: 14px;
    padding: 16px 18px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);
    display: flex; flex-direction: column; gap: 13px;
    transition: box-shadow .2s ease, border-color .2s ease;
  }
  .planting-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.06); border-color: #cfe0d3; }
  .pc-head { display: flex; align-items: center; gap: 11px; }
  .pc-icon {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    background: #e6f0e7; color: #4A7A54;
    display: flex; align-items: center; justify-content: center;
  }
  .pc-icon svg { width: 20px; height: 20px; }
  .pc-id { min-width: 0; flex: 1; }
  .pc-title {
    font-size: 14.5px; font-weight: 700; color: #1e2922; line-height: 1.25;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .pc-sub {
    font-size: 11.5px; font-weight: 500; color: #94a3b8; margin-top: 2px;
    line-height: 1.4; overflow-wrap: anywhere;
  }
  .pc-stage {
    flex-shrink: 0; font-size: 10.5px; font-weight: 700; letter-spacing: .02em;
    padding: 4px 9px; border-radius: 999px; white-space: nowrap;
  }
  .pc-meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .pc-meta span {
    display: block; font-size: 10.5px; font-weight: 600; letter-spacing: .05em;
    text-transform: uppercase; color: #94a3b8;
  }
  .pc-meta b {
    display: block; font-size: 13.5px; font-weight: 700; color: #1e293b;
    margin-top: 3px; letter-spacing: -0.01em;
  }
  .pc-use {
    appearance: none; -webkit-appearance: none; font-family: inherit;
    height: 38px; border-radius: 10px; cursor: pointer;
    border: 1.5px solid #4A7A54; background: #fff; color: #14532d;
    font-size: 12.5px; font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    transition: background .18s ease, color .18s ease;
  }
  .pc-use:hover { background: #4A7A54; color: #fff; }
  .pc-use svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.4; stroke-linecap: round; stroke-linejoin: round; }
  .btn-switch {
    appearance: none; -webkit-appearance: none; font-family: inherit; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px; margin-top: 12px;
    background: #f8fafc; border: 1.5px solid #e2e8f0; color: #14532d;
    padding: 11px 18px; border-radius: 11px; font-size: 13px; font-weight: 700;
    transition: all .2s ease;
  }
  .btn-switch:hover { border-color: #4A7A54; background: #f0fdf4; }
  .btn-switch svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .record-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: visible;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }
  .record-head {
    padding: 16px 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .record-head b { font-size: 14px; color: #1e2922; }
  .record-head .rec-icon {
    width: 30px; height: 30px; border-radius: 9px;
    background: #edf1e9; color: #648268;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .record-body { padding: 0 18px 20px; border-top: 1px solid #f1f5f9; }
  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    padding-top: 18px;
  }
  @media (max-width: 820px) { .form-grid { grid-template-columns: 1fr; } }

  /* ── Reusable Input, Dropdown (.q-select), & Flatpickr Calendar Styles ── */
  .form-field label {
    display: block; font-size: 13px; font-weight: 700;
    color: #1a2b1e; margin-bottom: 6px; line-height: 1.4;
  }
  .opt-tag { color: #94a3b8; font-weight: 400; }
  .input-hint {
    font-size: 12px; color: #718276; margin-top: 7px; line-height: 1.55;
  }

  .form-field input[type="number"],
  .form-field input[type="text"] {
    width: 100%; height: 44px; padding: 0 14px;
    border: 1.5px solid #d7dfda; border-radius: 10px;
    font-size: 13.5px; font-weight: 500; color: #1e2922; background: #ffffff;
    font-family: inherit; outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .form-field input[type="number"]:hover,
  .form-field input[type="text"]:hover {
    border-color: #b9cec0;
  }
  .form-field input[type="number"]:focus,
  .form-field input[type="text"]:focus {
    border-color: #648268;
    box-shadow: 0 0 0 3px rgba(100, 130, 104, 0.12);
  }

  /* Custom Flatpickr Datepicker Wrapper & Popup Styles (matched to My Crops) */
  .ncb-date-wrapper { position: relative; width: 100%; }
  .ncb-date-wrapper input[type="text"] {
    padding-right: 40px;
    cursor: pointer;
    font-variant-numeric: tabular-nums;
  }
  .ncb-date-icon {
    position: absolute;
    right: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #4A7A54;
    pointer-events: none;
  }

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
  .flatpickr-calendar.arrowTop:before,
  .flatpickr-calendar.arrowTop:after { display: none !important; }
  .flatpickr-innerContainer,
  .flatpickr-rContainer,
  .flatpickr-days { width: 100% !important; }
  .dayContainer {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
  }
  .flatpickr-months {
    display: flex !important;
    align-items: center !important;
    margin-bottom: 8px !important;
  }
  .flatpickr-months .flatpickr-month {
    background: #ffffff !important;
    color: #1e293b !important;
    fill: #1e293b !important;
    height: 34px !important;
  }
  .flatpickr-current-month {
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
  }
  .flatpickr-current-month .flatpickr-monthDropdown-months,
  .flatpickr-current-month input.cur-year {
    font-weight: 700 !important;
    color: #1e293b !important;
  }
  .flatpickr-months .flatpickr-prev-month:hover svg,
  .flatpickr-months .flatpickr-next-month:hover svg {
    fill: #4A7A54 !important;
  }
  span.flatpickr-weekday {
    color: #64748b !important;
    font-weight: 700 !important;
    font-size: 11px !important;
  }
  .flatpickr-day {
    border-radius: 8px !important;
    color: #334155 !important;
    font-weight: 500 !important;
    height: 36px !important;
    line-height: 36px !important;
    margin-top: 2px !important;
  }
  .flatpickr-day:hover {
    background: #f1f5f9 !important;
    border-color: transparent !important;
    color: #4A7A54 !important;
  }
  .flatpickr-day.selected,
  .flatpickr-day.selected:hover {
    background: #4A7A54 !important;
    border-color: #4A7A54 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
  }
  .flatpickr-day.today {
    border-color: #4A7A54 !important;
    font-weight: 700 !important;
    color: #4A7A54 !important;
  }
  .flatpickr-day.today.selected {
    color: #ffffff !important;
  }

  /* Custom Select Dropdowns (.q-select) */
  .q-select { position: relative; }
  .q-select-btn {
    width: 100%; min-height: 44px;
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 10px 14px; border: 1.5px solid #d7dfda; border-radius: 10px;
    background: #ffffff; cursor: pointer; font-family: inherit; text-align: left;
    transition: border-color 0.15s, box-shadow 0.15s;
  }
  .q-select-btn:hover { border-color: #b9cec0; }
  .q-select.open .q-select-btn {
    border-color: #648268;
    box-shadow: 0 0 0 3px rgba(100, 130, 104, 0.12);
  }
  .qsel-title {
    font-size: 13.5px; font-weight: 600; color: #1e2922;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .qsel-title.is-placeholder { color: #879a8c; font-weight: 500; }
  .qsel-chev {
    width: 16px; height: 16px; color: #64748b; flex-shrink: 0;
    transition: transform 0.18s;
  }
  .q-select.open .qsel-chev { transform: rotate(180deg); }
  .q-select-menu {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 60;
    background: #ffffff; border: 1px solid #e0e5e1; border-radius: 12px;
    box-shadow: 0 10px 34px rgba(20, 61, 35, 0.14);
    padding: 6px; display: none; max-height: 270px; overflow-y: auto;
  }
  .q-select.open .q-select-menu { display: block; }
  .q-select-item {
    display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px;
    border-radius: 9px; cursor: pointer; transition: background 0.12s;
  }
  .q-select-item:hover { background: #f4f6f5; }
  .q-select-item.is-active { background: #e9f6d2; }
  .qsel-t { font-size: 13.5px; font-weight: 700; color: #1e2922; }
  .q-select-item.is-active .qsel-t { color: #14532d; }
  .qsel-s { font-size: 12px; color: #64748b; margin-top: 2px; line-height: 1.45; }
  .qsel-check {
    width: 15px; height: 15px; color: #14532d; flex-shrink: 0;
    margin-left: auto; margin-top: 2px; visibility: hidden;
  }
  .q-select-item.is-active .qsel-check { visibility: visible; }
  .q-select-empty {
    padding: 12px; font-size: 12.5px; color: #879a8c; line-height: 1.45;
  }

  .form-errors { padding: 12px 18px; background: #fef2f2; border-bottom: 1px solid #fecaca; border-radius: 14px; }
  .form-errors ul { margin: 0; padding-left: 16px; color: #b91c1c; font-size: 13px; }

  .btn-save {
    appearance: none; height: 44px; padding: 0 26px; border: none; border-radius: 10px;
    background: #3F6B4D; color: #fff;
    font-size: 13px; font-weight: 700; cursor: pointer;
    box-shadow: 0 2px 8px rgba(63,107,77,0.22);
    display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;
  }
  .btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(20,61,35,0.30); }
  .btn-history {
    display: inline-flex; align-items: center; gap: 8px;
    background: #f8fafc; border: 1.5px solid #e2e8f0; color: #14532d;
    padding: 11px 18px; border-radius: 11px; font-size: 13px; font-weight: 700;
    text-decoration: none; transition: all 0.2s ease;
  }
  .btn-history:hover { border-color: #4A7A54; background: #f0fdf4; }
  .btn-history svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
  .btn-save svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
</style>

<div class="harvest-submit-page">

  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">Submit Harvest Report</h1>
    <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">
      Record how much you harvested, your farm expenses, and the land area used. You can pick from your planted crops or enter a past harvest by hand.
    </p>
  </div>

  @if(session('success'))
    <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(74,122,84,0.06);">
      <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(74,122,84,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="#4A7A54" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <span style="font-size: 14px; color: #166534; font-weight: 600;">{{ session('success') }}</span>
    </div>
  @endif

  @if($errors->any())
    <div class="record-card" style="border-color: #fecaca; margin-bottom: 20px;">
      <div class="form-errors">
        <b style="font-size: 13px; color: #b91c1c;">Please check the following before saving:</b>
        <ul>
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  

  @php
      $openForm = $errors->any() || old('crop_batch_id') || ($batches ?? collect())->isEmpty();

      $cropLibrary = collect(config('crop_library.crops', []))
          ->keyBy(fn ($c) => strtolower($c['name']));

      $cropOptions = collect($cropTypes ?? [])
          ->map(function ($cropName) use ($cropLibrary) {
              $entry = $cropLibrary->get(strtolower($cropName));
              $aliases = array_values(array_filter($entry['aliases'] ?? []));
              $terms = array_values(array_filter(array_unique(array_merge(
                  [$cropName, $entry['name'] ?? ''],
                  $aliases,
                  array_column($entry['varieties'] ?? [], 'name'),
                  [$entry['scientific_name'] ?? '']
              ))));
              return [
                  'name'  => $cropName,
                  'local' => implode(', ', $aliases),
                  'terms' => $terms,
              ];
          })
          ->values()
          ->all();
  @endphp

  <div class="tabs" role="tablist" data-default-tab="{{ $openForm ? 'form' : 'plantings' }}">
    <button type="button" class="tab" data-tab="plantings" role="tab" id="tab-plantings" aria-controls="panel-plantings" aria-selected="false">
      <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
      From my planted crops
      <span class="tab-count">{{ ($batches ?? collect())->count() }}</span>
    </button>
    <button type="button" class="tab" data-tab="form" role="tab" id="tab-form" aria-controls="panel-form" aria-selected="false">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      Report form
    </button>
  </div>

  <div class="tab-panel" id="panel-plantings" role="tabpanel" aria-labelledby="tab-plantings">
    @if(($batches ?? collect())->isEmpty())
      <div class="sync-note neutral" style="margin-bottom: 0;">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="#64748b" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>
          <b>No crop plantings on file yet — that's okay</b>
          <p>You can still submit a harvest report using the <strong>Report form</strong> tab, even for a past harvest. Crops you add and confirm in <a href="{{ route('farmer.crops.index') }}">My Crops</a> will show up here.</p>
          <button type="button" class="btn-switch" data-goto="form">
            <svg viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            Fill out the form myself
          </button>
        </div>
      </div>
    @else
      <div class="planting-grid">
        @foreach($batches as $batch)
          <div class="planting-card">
            <div class="pc-head">
              <div class="pc-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
              </div>
              <div class="pc-id">
                <div class="pc-title">{{ $batch->crop?->name ?? 'Unknown crop' }}</div>
                <div class="pc-sub">{{ $batch->batch_name ?? 'Batch #' . $batch->id }}</div>
              </div>
              <span class="pc-stage" style="background: {{ $batch->growth_stage_color }}1f; color: {{ $batch->growth_stage_color }};">{{ match ($batch->growth_stage) {
                  'land_prep' => 'Land prep',
                  'seeding' => 'Seeding',
                  'vegetative' => 'Vegetative',
                  'reproductive' => 'Flowering',
                  'ripening' => 'Ripening',
                  'harvested' => 'Harvested',
                  'failed' => 'Failed',
                  default => ucfirst((string) $batch->growth_stage),
              } }}</span>
            </div>
            <div class="pc-meta">
              <div>
                <span>Land area</span>
                <b>{{ rtrim(rtrim(number_format((float) $batch->land_area, 2, '.', ''), '0'), '.') }} {{ $batch->land_area_unit === 'ha' ? 'ha' : $batch->land_area_unit }}</b>
              </div>
              <div>
                <span>Season</span>
                <b>{{ ucfirst($batch->season) }}</b>
              </div>
              <div>
                <span>Planted</span>
                <b>{{ $batch->planting_date?->format('M d, Y') ?? '—' }}</b>
              </div>
              <div>
                <span>Variety</span>
                <b>{{ $batch->variety ?: '—' }}</b>
              </div>
            </div>
            <button type="button" class="pc-use" data-batch-id="{{ $batch->id }}">
              Report this harvest
              <svg viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <div class="tab-panel" id="panel-form" role="tabpanel" aria-labelledby="tab-form">
  <div class="record-card">
    <div class="record-head">
      <div style="display: flex; align-items: center; gap: 12px;">
        <span class="rec-icon">
          <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </span>
        <b>Harvest Report Form</b>
      </div>
    </div>
    <div class="record-body">
      <form method="POST" action="{{ route('farmer.harvests.store') }}">
        @csrf
        <div class="form-grid" style="grid-template-columns: 1fr;">
          <div class="form-field">
            <label id="batchLabel">Planted Crop <span class="opt-tag">(optional)</span></label>
            <div class="q-select" data-field="crop_batch_id" id="batchSelectWrap">
              <input type="hidden" name="crop_batch_id" id="cropBatchInput" value="{{ old('crop_batch_id', '') }}">
              <select aria-hidden="true" tabindex="-1" style="display: none;">
                <option value="">None</option>
              </select>
              <button type="button" class="q-select-btn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="batchLabel">
                <span class="qsel-title">None — Enter details manually</span>
                <svg class="qsel-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
              <div class="q-select-menu" role="listbox" aria-labelledby="batchLabel">
                <div class="q-select-item" data-value="" role="option">
                  <div class="qsel-opt">
                    <div class="qsel-t">None — Enter details manually</div>
                    <div class="qsel-s">For a past harvest or a crop not listed in My Crops</div>
                  </div>
                  <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                @foreach($batches as $batch)
                  @php
                    $areaFmt = rtrim(rtrim(number_format((float) $batch->land_area, 2, '.', ''), '0'), '.');
                    $unitFmt = $batch->land_area_unit === 'ha' ? 'ha' : $batch->land_area_unit;
                  @endphp
                  <div class="q-select-item"
                       data-value="{{ $batch->id }}"
                       data-crop="{{ $batch->crop?->name }}"
                       data-season="{{ $batch->season }}"
                       data-area="{{ $batch->land_area }}"
                       data-variety="{{ $batch->variety ?? '' }}"
                       data-variety-key="{{ $batch->variety_key ?? '' }}"
                       role="option">
                    <div class="qsel-opt">
                      <div class="qsel-t">{{ $batch->crop?->name ?? 'Unknown crop' }} &mdash; {{ $batch->batch_name ?? 'Batch #' . $batch->id }}</div>
                      <div class="qsel-s">{{ $areaFmt }} {{ $unitFmt }} &middot; {{ ucfirst($batch->season) }} season</div>
                    </div>
                    <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </div>
                @endforeach
              </div>
            </div>
            <p class="input-hint">
              Select a planted crop to auto-fill your crop, season, and farm size. Choose <strong>None</strong> if you are typing a past harvest manually.
            </p>
          </div>
        </div>

        <div class="form-grid">
          <!-- Crop Type: Box itself is the search-as-you-type input (matched to My Crops) -->
          <div class="form-field">
            <label for="cropTypeQuery">Crop Type</label>
            <div class="q-select" id="cropTypeSearchWrap">
              <input type="hidden" name="crop_type" id="cropTypeField" value="{{ old('crop_type', '') }}" required>
              <input type="text"
                     id="cropTypeQuery"
                     value="{{ old('crop_type', '') }}"
                     placeholder="Type a crop name (e.g. palay, mais, saging)"
                     autocomplete="off"
                     spellcheck="false"
                     aria-label="Search crop type"
                     aria-autocomplete="list"
                     aria-expanded="false">
              <div class="q-select-menu" role="listbox" id="cropTypeMenu"></div>
            </div>
          </div>

          <div class="form-field" id="hrvVarietyField" style="display:none;">
            <label id="varietyLabel">Variety</label>
            <div class="q-select" data-field="variety" id="varietySelectWrap">
              <input type="hidden" name="variety_key" id="harvestVarietyKey" value="{{ old('variety_key', '') }}">
              <input type="hidden" name="variety" id="harvestVariety" value="{{ old('variety', '') }}" required>
              <button type="button" class="q-select-btn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="varietyLabel">
                <span class="qsel-title">Select variety</span>
                <svg class="qsel-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
              <div class="q-select-menu" role="listbox" aria-labelledby="varietyLabel">
                <div class="q-select-item" data-value="" role="option">
                  <div class="qsel-opt"><div class="qsel-t">Select variety</div></div>
                  <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
              </div>
            </div>
            <p class="input-hint" id="harvestVarietyHint">Choose the crop first. Uses the same crop library as My Crops.</p>
          </div>

          <div class="form-field">
            <label id="seasonLabel">Season</label>
            <div class="q-select" data-field="season" id="seasonSelectWrap" style="pointer-events:none; opacity:0.7;">
              <input type="hidden" name="season" id="seasonField" value="{{ old('season', \App\Helpers\SeasonHelper::getCurrentSeason()) }}" required>
              <button type="button" class="q-select-btn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="seasonLabel" disabled>
                <span class="qsel-title">{{ \App\Helpers\SeasonHelper::getSeasonLabel() }}</span>
                <svg class="qsel-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
            </div>
            <p class="input-hint">Auto-detected from harvest date. Wet: Jun–Nov, Dry: Dec–May.</p>
          </div>

          <!-- Harvest Date: Custom Flatpickr Datepicker (matched to My Crops) -->
          <div class="form-field">
            <label for="harvestDateField">Harvest Date</label>
            <div class="ncb-date-wrapper">
              <input type="text" class="custom-datepicker" name="harvest_date" id="harvestDateField" value="{{ old('harvest_date', now()->toDateString()) }}" placeholder="Select date" required>
              <svg class="ncb-date-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
          </div>

          <div class="form-field">
            <label for="landAreaField">Land Area (hectares)</label>
            <input type="number" name="land_area" id="landAreaField" step="0.01" min="0.01" max="999.99" value="{{ old('land_area') }}" placeholder="e.g. 1.50" required>
          </div>

          <div class="form-field">
            <label for="actualYieldField">Actual Yield (metric tons)</label>
            <input type="number" name="actual_yield_metric_tons" id="actualYieldField" step="0.01" min="0" value="{{ old('actual_yield_metric_tons') }}" placeholder="e.g. 4.20" required>
          </div>

          <div class="form-field">
            <label for="actualExpensesField">Actual Total Expenses (₱)</label>
            <input type="number" name="actual_total_expenses" id="actualExpensesField" step="0.01" min="0" value="{{ old('actual_total_expenses') }}" placeholder="e.g. 68500" required>
          </div>
        </div>

        <div style="margin-top: 20px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
          <button type="submit" class="btn-save">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            Submit Harvest Report
          </button>
          <a href="{{ route('farmer.harvests.index') }}" class="btn-history">
            <svg viewBox="0 0 24 24"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><polyline points="12 7 12 12 16 14"/></svg>
            View My Harvest History
          </a>
        </div>
      </form>
    </div>
  </div>
  </div><!-- /#panel-form -->

  <script>
    (function () {
      // ── Season auto-detection from harvest date ──────────────────────────
      // Mirrors App\Helpers\SeasonHelper::getCurrentSeason logic.
      // Wet window defaults: Jun (6) – Nov (11). Dry: Dec (12) – May (5).
      var WET_START = 6, WET_END = 11;
      function getSeasonFromDate(dateStr) {
        if (!dateStr) return 'dry';
        var m = parseInt(dateStr.split('-')[1], 10);
        return (m >= WET_START && m <= WET_END) ? 'wet' : 'dry';
      }
      function applySeasonFromDate(dateStr) {
        var season = getSeasonFromDate(dateStr);
        var field = document.getElementById('seasonField');
        var title = document.querySelector('#seasonSelectWrap .qsel-title');
        if (field) field.value = season;
        if (title) title.textContent = season === 'wet' ? 'Wet Season' : 'Dry Season';
      }

      // ── Custom Flatpickr Calendar Initialization (matched to My Crops) ──
      if (typeof flatpickr !== 'undefined') {
        flatpickr('#harvestDateField', {
          dateFormat: 'Y-m-d',
          maxDate: 'today',
          disableMobile: 'true',
          onChange: function (selectedDates, dateStr) {
            applySeasonFromDate(dateStr);
          }
        });
        // Initialize season from current date value on load
        var initialDate = document.getElementById('harvestDateField')?.value;
        if (initialDate) applySeasonFromDate(initialDate);
      }

      // ── Tabs ────────────────────────────────────────────────────────────
      var tablist = document.querySelector('.tabs');
      var tabs = tablist ? Array.prototype.slice.call(tablist.querySelectorAll('.tab')) : [];
      var panels = {
        plantings: document.getElementById('panel-plantings'),
        form: document.getElementById('panel-form')
      };

      function activateTab(name, opts) {
        if (!panels[name]) name = 'form';
        tabs.forEach(function (t) {
          var on = t.getAttribute('data-tab') === name;
          t.classList.toggle('active', on);
          t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        Object.keys(panels).forEach(function (key) {
          if (panels[key]) panels[key].hidden = (key !== name);
        });
        if (opts && opts.scroll && panels[name]) {
          panels[name].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }

      tabs.forEach(function (t) {
        t.addEventListener('click', function () { activateTab(t.getAttribute('data-tab')); });
      });

      activateTab((tablist && tablist.getAttribute('data-default-tab')) || 'plantings');

      // ── Search-as-You-Type Crop Type Box (matched to My Crops) ───────────
      var _CROP_OPTIONS = @json($cropOptions);
      var cropWrap  = document.getElementById('cropTypeSearchWrap');
      var cropField = document.getElementById('cropTypeField');
      var cropQuery = document.getElementById('cropTypeQuery');
      var cropMenu  = document.getElementById('cropTypeMenu');

      function normText(s) {
        var val = (s || '').toString().toLowerCase();
        if (val.normalize) val = val.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        return val.replace(/[^a-z0-9]+/g, ' ').trim();
      }

      function levDist(a, b) {
        if (a.length === 0) return b.length;
        if (b.length === 0) return a.length;
        var matrix = [];
        for (var i = 0; i <= b.length; i++) matrix[i] = [i];
        for (var j = 0; j <= a.length; j++) matrix[0][j] = j;
        for (var r = 1; r <= b.length; r++) {
          for (var c = 1; c <= a.length; c++) {
            matrix[r][c] = b.charAt(r - 1) === a.charAt(c - 1)
              ? matrix[r - 1][c - 1]
              : Math.min(matrix[r - 1][c - 1] + 1, matrix[r][c - 1] + 1, matrix[r - 1][c] + 1);
          }
        }
        return matrix[b.length][a.length];
      }

      function scoreWord(term, word) {
        var t = normText(term).replace(/ /g, '');
        var w = normText(word).replace(/ /g, '');
        if (!t || !w) return 999;
        if (t === w) return 0;
        if (w.indexOf(t) === 0) return 1;
        if (w.indexOf(t) !== -1) return 2;
        if (t.indexOf(w) === 0) return 3;
        var d = levDist(t, w);
        var limit = t.length <= 4 ? 1 : (t.length <= 7 ? 2 : 3);
        return d <= limit ? 4 + d : 999;
      }

      function scoreMatch(terms, query) {
        var words = normText(query).split(' ').filter(Boolean);
        if (!words.length) return 0;
        var best = 999;
        (terms || []).forEach(function (term) {
          var termWords = normText(term).split(' ').filter(Boolean);
          var score = words.reduce(function (total, word) {
            var wordScore = termWords.reduce(function (lowest, termWord) {
              return Math.min(lowest, scoreWord(termWord, word));
            }, 999);
            return wordScore === 999 ? 999 : total + wordScore;
          }, 0);
          best = Math.min(best, score);
        });
        return best;
      }

      function escHtml(s) {
        return String(s == null ? '' : s)
          .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
      }

      function renderCropMenu() {
        if (!cropMenu || !cropQuery) return;
        var q = cropQuery.value.trim();
        var scored = _CROP_OPTIONS.map(function (c) {
          return { crop: c, score: scoreMatch(c.terms, q) };
        }).filter(function (r) {
          return r.score < 999;
        }).sort(function (a, b) {
          return a.score - b.score;
        });

        if (!scored.length) {
          cropMenu.innerHTML = '<div class="q-select-empty">No crop matches "' + escHtml(q) + '". Try a local name (e.g. palay, mais, saging, talong, kalabasa).</div>';
          return;
        }

        cropMenu.innerHTML = scored.map(function (r) {
          var c = r.crop;
          var active = cropField && cropField.value === c.name ? ' is-active' : '';
          return '<div class="q-select-item' + active + '" data-crop-name="' + escHtml(c.name) + '" role="option">' +
            '<div class="qsel-opt"><div class="qsel-t">' + escHtml(c.name) + '</div></div>' +
            '<svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' +
          '</div>';
        }).join('');
      }

      function onHarvestCropPicked(name) {
        if (batchInput && batchInput.value) return; // batch owns fields/variety
        setHarvestManual(true, name || (cropField?cropField.value:''), '', '');
      }

      function openCropMenu() {
        if (!cropWrap || !cropQuery) return;
        document.querySelectorAll('.q-select.open').forEach(function (o) {
          if (o !== cropWrap) {
            o.classList.remove('open');
            var b = o.querySelector('.q-select-btn');
            if (b) b.setAttribute('aria-expanded', 'false');
          }
        });
        renderCropMenu();
        cropWrap.classList.add('open');
        cropQuery.setAttribute('aria-expanded', 'true');
      }

      function closeCropMenu() {
        if (!cropWrap || !cropQuery) return;
        cropWrap.classList.remove('open');
        cropQuery.setAttribute('aria-expanded', 'false');
      }

      function selectCropName(name) {
        if (!cropField || !cropQuery) return;
        cropField.value = name;
        cropQuery.value = name;
        closeCropMenu();
        onHarvestCropPicked(name);
      }

      if (cropQuery && cropMenu) {
        cropQuery.addEventListener('focus', openCropMenu);
        cropQuery.addEventListener('click', function (e) {
          e.stopPropagation();
          openCropMenu();
        });
        cropQuery.addEventListener('input', function () {
          var typed = cropQuery.value.trim();
          var exact = _CROP_OPTIONS.find(function (c) {
            return c.name.toLowerCase() === typed.toLowerCase();
          });
          cropField.value = exact ? exact.name : '';
          if (exact) { onHarvestCropPicked(exact.name); } else if (!typed) { onHarvestCropPicked(''); }
          openCropMenu();
        });
        cropQuery.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            closeCropMenu();
            cropQuery.blur();
          } else if (e.key === 'Enter') {
            var first = cropMenu.querySelector('.q-select-item[data-crop-name]');
            if (first) {
              e.preventDefault();
              selectCropName(first.getAttribute('data-crop-name'));
            }
          }
        });
        cropMenu.addEventListener('click', function (e) {
          var item = e.target.closest('.q-select-item[data-crop-name]');
          if (!item) return;
          selectCropName(item.getAttribute('data-crop-name'));
        });
      }

      // ── Standard Custom Select Dropdowns (.q-select[data-field]) ─────────
      function syncQSelect(sel) {
        if (!sel) return;
        var hidden = sel.querySelector('input[type="hidden"]');
        var title = sel.querySelector('.qsel-title');
        var items = Array.prototype.slice.call(sel.querySelectorAll('.q-select-item'));
        var val = hidden ? hidden.value : '';

        items.forEach(function (item) {
          item.classList.toggle('is-active', item.getAttribute('data-value') === val);
        });

        var active = items.find(function (item) { return item.getAttribute('data-value') === val; });
        if (active && title) {
          var t = active.querySelector('.qsel-t');
          title.textContent = t ? t.textContent.trim() : val;
          title.classList.remove('is-placeholder');
        }
      }

      document.querySelectorAll('.q-select[data-field]').forEach(function (sel) {
        var hidden = sel.querySelector('input[type="hidden"]');
        var btn = sel.querySelector('.q-select-btn');

        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          var isOpen = sel.classList.contains('open');
          document.querySelectorAll('.q-select.open').forEach(function (o) {
            o.classList.remove('open');
            var b = o.querySelector('.q-select-btn');
            if (b) b.setAttribute('aria-expanded', 'false');
          });
          if (!isOpen) {
            sel.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');
          }
        });

        sel.addEventListener('click', function (e) {
          var item = e.target.closest('.q-select-item');
          if (!item) return;
          if (hidden) {
            hidden.value = item.getAttribute('data-value');
            hidden.dispatchEvent(new Event('change'));
          }
          sel.classList.remove('open');
          btn.setAttribute('aria-expanded', 'false');
          syncQSelect(sel);
        });

        syncQSelect(sel);
      });

      document.addEventListener('click', function (e) {
        document.querySelectorAll('.q-select.open').forEach(function (o) {
          if (!o.contains(e.target)) {
            o.classList.remove('open');
            var b = o.querySelector('.q-select-btn');
            if (b) b.setAttribute('aria-expanded', 'false');
            if (o === cropWrap && cropQuery) {
              cropQuery.setAttribute('aria-expanded', 'false');
            }
          }
        });
      });

      // ── Choosing a planting → auto-fill Crop, Season & Land Area ─────────
      var _HRV_VARIETIES = @json($cropVarieties ?? []);
      var _HRV_GENERAL = @json(\App\Services\CropLibraryService::GENERAL_VARIETY);
      var _HRV_GENERAL_KEY = @json(\App\Services\CropLibraryService::GENERAL_VARIETY_KEY);
      var hrvVarietyField = document.getElementById('hrvVarietyField');
      var hrvVarietyWrap = document.getElementById('varietySelectWrap');
      var hrvVarietyHidden = document.getElementById('harvestVariety');
      var hrvVarietyKey = document.getElementById('harvestVarietyKey');
      var hrvVarietyTitle = hrvVarietyWrap ? hrvVarietyWrap.querySelector('.qsel-title') : null;
      var hrvVarietyMenu = hrvVarietyWrap ? hrvVarietyWrap.querySelector('.q-select-menu') : null;
      var hrvVarietyHint = document.getElementById('harvestVarietyHint');

      function fillHarvestVarieties(cropName, selectedKey, selectedName) {
        if (!hrvVarietyMenu || !hrvVarietyHidden) return;
        var opts = _HRV_VARIETIES[cropName] || [{key: _HRV_GENERAL_KEY, name: _HRV_GENERAL}];
        hrvVarietyMenu.innerHTML = '';
        opts.forEach(function (o) {
          var val = o.name;
          var item = document.createElement('div');
          item.className = 'q-select-item';
          item.setAttribute('data-value', val);
          item.setAttribute('data-key', o.key || '');
          item.setAttribute('role', 'option');
          item.innerHTML = '<div class="qsel-opt"><div class="qsel-t">' + val + '</div></div>' +
            '<svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
          hrvVarietyMenu.appendChild(item);
          var match = (selectedKey && o.key === selectedKey) || (!selectedKey && selectedName && o.name === selectedName) || (!selectedKey && !selectedName && o.key === _HRV_GENERAL_KEY);
          if (match) {
            item.classList.add('is-active');
            hrvVarietyHidden.value = val;
            if (hrvVarietyTitle) {
              hrvVarietyTitle.textContent = val;
              hrvVarietyTitle.classList.remove('is-placeholder');
            }
            hrvVarietyKey.value = o.key || _HRV_GENERAL_KEY;
          }
        });
        if (!hrvVarietyHidden.value && opts.length) {
          hrvVarietyHidden.value = opts[0].name;
          hrvVarietyKey.value = opts[0].key || _HRV_GENERAL_KEY;
          if (hrvVarietyTitle) {
            hrvVarietyTitle.textContent = opts[0].name;
            hrvVarietyTitle.classList.remove('is-placeholder');
          }
        }
        hrvVarietyMenu.querySelectorAll('.q-select-item').forEach(function (item) {
          item.addEventListener('click', function () {
            var v = item.getAttribute('data-value');
            var k = item.getAttribute('data-key');
            hrvVarietyHidden.value = v;
            hrvVarietyKey.value = k || _HRV_GENERAL_KEY;
            if (hrvVarietyTitle) {
              hrvVarietyTitle.textContent = v;
              hrvVarietyTitle.classList.remove('is-placeholder');
            }
            hrvVarietyWrap.classList.remove('open');
          });
        });
      }
      function setHarvestManual(isManual, cropName, vKey, vName){
        [cropField,seasonField,landField].forEach(function(f){ if(!f) return; f.readOnly=!isManual; var wrap=f.closest('.q-select'); if(wrap){ wrap.classList.toggle('is-readonly',!isManual); var b=wrap.querySelector('.q-select-btn'); if(b) b.tabIndex=isManual?0:-1; }});
        if(cropQuery){ cropQuery.readOnly=!isManual; cropQuery.tabIndex=isManual?0:-1; if(cropWrap) cropWrap.classList.toggle('is-readonly',!isManual); }
        if(hrvVarietyField) hrvVarietyField.style.display='';
        if(hrvVarietySel) hrvVarietySel.disabled=!isManual;
        fillHarvestVarieties(cropName||'', vKey||'', vName||'');
        if(hrvVarietyHint) hrvVarietyHint.textContent=isManual ? (cropName?'Variety for this harvest (from crop library).':'Choose the crop first. Uses the same crop library as My Crops.') : 'Variety comes from the selected planting batch.';
      }
      var batchWrap   = document.getElementById('batchSelectWrap');
      var batchInput  = document.getElementById('cropBatchInput');
      var seasonWrap  = document.getElementById('seasonSelectWrap');
      var seasonField = document.getElementById('seasonField');
      var landField   = document.getElementById('landAreaField');

      function applyBatch() {
        if (!batchWrap || !batchInput) return;
        var val = batchInput.value;
        if (!val) {
          setHarvestManual(true, cropField ? cropField.value : '', hrvVarietyKey ? hrvVarietyKey.value : '', hrvVarietySel ? hrvVarietySel.value : '');
          return;
        }

        var activeItem = batchWrap.querySelector('.q-select-item[data-value="' + CSS.escape(val) + '"]');
        if (!activeItem) return;

        var crop = activeItem.getAttribute('data-crop') || '';
        if (crop) {
          var exists = _CROP_OPTIONS.some(function (c) { return c.name === crop; });
          if (!exists) {
            _CROP_OPTIONS.unshift({ name: crop, local: '', terms: [crop] });
          }
          if (cropField) cropField.value = crop;
          if (cropQuery) cropQuery.value = crop;
        }

        var season = activeItem.getAttribute('data-season');
        if (season && seasonField) {
          seasonField.value = season;
          syncQSelect(seasonWrap);
        }

        var area = activeItem.getAttribute('data-area');
        if (area && landField) {
          landField.value = area;
        }

        var bVar = activeItem.getAttribute('data-variety') || '';
        var bVk = activeItem.getAttribute('data-variety-key') || '';
        setHarvestManual(false, crop, bVk, bVar);
      }

      if (batchInput) {
        batchInput.addEventListener('change', applyBatch);
      }
      applyBatch();

      Array.prototype.slice.call(document.querySelectorAll('[data-batch-id]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = btn.getAttribute('data-batch-id');
          if (batchInput && batchWrap) {
            batchInput.value = id;
            syncQSelect(batchWrap);
            batchInput.dispatchEvent(new Event('change'));
          }
          activateTab('form', { scroll: true });
        });
      });

      Array.prototype.slice.call(document.querySelectorAll('[data-goto]')).forEach(function (el) {
        el.addEventListener('click', function () {
          activateTab(el.getAttribute('data-goto'), { scroll: true });
        });
      });
    })();
  </script>

</div>
@endsection