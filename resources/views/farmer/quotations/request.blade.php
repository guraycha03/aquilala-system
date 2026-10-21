@extends('layouts.sidebar')

@section('title', 'Generate Cost Quotation')

@section('content')
@php
  $selSeason = old('season') ?? $globalSeason;
  $climate = $globalClimateInfo;
@endphp
<style>
  .input-field {
    width: 100%; padding: 12px 15px; border: 1px solid #d7dfda;
    border-radius: 10px; font-size: 14px; line-height: 1.5; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .input-field:focus {
    outline: none; border-color: #648268;
    box-shadow: 0 0 0 3px rgba(100, 130, 104, 0.12);
  }
  .input-label {
    display: block; font-size: 13.5px; font-weight: 700;
    color: #1a2b1e; margin: 0; line-height: 1.4;
  }
  .input-hint {
    font-size: 12px; color: #879a8c; margin-top: 8px; line-height: 1.6;
  }
  .opt-tag { color: #94a3b8; font-weight: 400; }
  select.input-field {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
    padding-right: 40px; cursor: pointer;
  }
  .submit-btn {
    background: linear-gradient(135deg, #143d23 0%, #1a5230 100%);
    color: white; border: none;
    padding: 14px 30px; border-radius: 11px; font-size: 15px;
    font-weight: 700; cursor: pointer; transition: all 0.2s;
    display: inline-flex; align-items: center; gap: 9px;
    box-shadow: 0 3px 12px rgba(20,61,35,0.22);
  }
  .submit-btn:hover { box-shadow: 0 5px 18px rgba(20,61,35,0.3); transform: translateY(-1px); }
  .submit-btn:active { transform: scale(0.98); }
  .submit-btn svg { width: 17px; height: 17px; stroke: currentColor; stroke-width: 2.2; fill: none; stroke-linecap: round; stroke-linejoin: round; }

  /* Guided question layout — clean, organized, breathable. The step
     number shares the title row instead of reserving a whole left
     gutter for itself; the control below spans the full column. */
  .q-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 36px 44px;
    align-items: start;
  }
  .q-span { grid-column: 1 / -1; }
  @media (max-width: 860px) { .q-grid { grid-template-columns: 1fr; } }
  .q-head {
    display: flex; align-items: center; gap: 11px; margin-bottom: 13px;
  }
  .q-num {
    width: 30px; height: 30px; border-radius: 10px; flex-shrink: 0;
    background: #eef3ef; color: #143d23;
    display: flex; align-items: center; justify-content: center;
    font-size: 12.5px; font-weight: 800;
  }
  .q-body { min-width: 0; }

  /* Season choice cards — class-based (NOT data-inline-style) so the
     dark-mode generator can scrape them into dark-mode.css and the
     cards actually go dark at runtime. */
  .season-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .season-card {
    display: flex; align-items: center; gap: 11px; padding: 14px 16px;
    border: 1.5px solid #d7dfda; border-radius: 12px; cursor: pointer;
    background: #ffffff;
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
  }
  .season-card:hover { border-color: #b9cec0; }
  .season-card.is-selected {
    background: #e9f6d2; border-color: #b5d97d;
    box-shadow: 0 1px 4px rgba(20, 61, 35, 0.08);
  }
  .season-card.is-selected .sc-title { color: #14532d; }
  .season-card.is-selected .sc-sub { color: #5f7d5c; }
  .season-card .sc-title { font-weight: 700; font-size: 14px; color: #1e2922; line-height: 1.35; }
  .season-card .sc-sub { font-size: 12px; color: #64748b; margin-top: 2px; }
  @media (max-width: 420px) { .season-grid { grid-template-columns: 1fr; } }

  /* "Currently in season" hint line */
  .climate-line { display: inline-flex; align-items: center; gap: 6px; }
  .climate-dot {
    display: inline-block; width: 9px; height: 9px;
    border-radius: 50%; flex-shrink: 0;
  }
  .climate-dot.wet { background: #0284c7; }
  .climate-dot.dry { background: #d97706; }
  .climate-sub { color: #64748b; }

  /* Land area row */
  .area-row { display: flex; gap: 10px; max-width: 330px; }
  .area-unit {
    display: flex; align-items: center; padding: 0 14px;
    background: #f4f6f5; border: 1px solid #d7dfda; border-radius: 10px;
    font-size: 14px; color: #64748b; font-weight: 600; white-space: nowrap;
  }

  /* Field-condition select groups (Step 4) */
  .cond-group + .cond-group { margin-top: 15px; }
  .cond-group .input-label { margin-bottom: 6px; }

  /* Custom select dropdowns (soil, weather risk) — same language as the
     crop selector, so every choice list on the page feels the same. */
  .q-select { position: relative; }
  .q-select-btn {
    width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 12px 15px; border: 1.5px solid #d7dfda; border-radius: 10px;
    background: #ffffff; cursor: pointer; font-family: inherit; text-align: left;
    transition: border-color 0.15s, box-shadow 0.15s;
  }
  .q-select-btn:hover { border-color: #b9cec0; }
  /* Open state uses the same soft matte sage as focus — the near-black
     green (#143d23) read as a hard dark border on an active field. */
  .q-select.open .q-select-btn { border-color: #648268; box-shadow: 0 0 0 3px rgba(100, 130, 104, 0.12); }
  .qsel-title { font-size: 14px; font-weight: 600; color: #1e2922; }
  .qsel-chev { width: 16px; height: 16px; color: #64748b; flex-shrink: 0; transition: transform 0.18s; }
  .q-select.open .qsel-chev { transform: rotate(180deg); }
  .q-select-menu {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 60;
    background: #ffffff; border: 1px solid #e0e5e1; border-radius: 12px;
    box-shadow: 0 10px 34px rgba(20, 61, 35, 0.14);
    padding: 6px; display: none; max-height: 268px; overflow-y: auto;
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
  .qsel-check { width: 15px; height: 15px; color: #14532d; flex-shrink: 0; margin-left: auto; margin-top: 2px; visibility: hidden; }
  .q-select-item.is-active .qsel-check { visibility: visible; }

  /* Budget "fit suggestions" toggle */
  .budget-toggle {
    display: flex; align-items: flex-start; gap: 11px; cursor: pointer;
    margin-top: 14px; padding: 12px 14px; max-width: 420px;
    border: 1.5px solid #d7dfda; border-radius: 12px; background: #ffffff;
    transition: border-color 0.15s, background 0.15s;
  }
  .budget-toggle:hover { border-color: #b9cec0; }
  .budget-toggle.is-on { background: #f4f9e9; border-color: #b5d97d; }
  .budget-toggle.is-disabled { opacity: 0.55; cursor: not-allowed; }
  .budget-toggle input { position: absolute; opacity: 0; pointer-events: none; }
  .bt-switch {
    position: relative; width: 40px; height: 23px; border-radius: 20px;
    background: #d7dfda; flex-shrink: 0; margin-top: 1px; transition: background 0.18s;
  }
  .bt-knob {
    position: absolute; top: 2.5px; left: 3px; width: 18px; height: 18px;
    border-radius: 50%; background: #ffffff; box-shadow: 0 1px 3px rgba(20, 40, 28, 0.25);
    transition: transform 0.18s;
  }
  .budget-toggle input:checked + .bt-switch { background: #14532d; }
  .budget-toggle input:checked + .bt-switch .bt-knob { transform: translateX(16px); }
  .budget-toggle input:focus-visible + .bt-switch { box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.15); }
  .bt-title { display: block; font-size: 13.5px; font-weight: 700; color: #1a2b1e; }
  .bt-sub { display: block; font-size: 12px; color: #879a8c; line-height: 1.5; margin-top: 2px; }

  /* Product filters: category + budget chip */
  .product-filter-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 11px; }
  .pf-select { max-width: 250px; }
  .pf-chip {
    display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px;
    border-radius: 20px; background: #f4f9e9; border: 1px solid #cfdfa8;
    font-size: 12px; font-weight: 600; color: #4a6353;
  }
  .pf-chip svg { color: #6b8a52; }

  /* Live budget total under the product picker — recalculates the moment
     a product, the budget, or the fit toggle changes. States reuse the
     budget-banner palette (muted green / amber / rose) so the dark-mode
     generator can scrape them. */
  .budget-meter {
    display: flex; align-items: center; gap: 9px; flex-wrap: wrap;
    margin-top: 12px; padding: 11px 14px; border-radius: 10px;
    border: 1.5px solid #d7dfda; background: #f7faf8;
    font-size: 13px; line-height: 1.55; color: #46554c;
  }
  .budget-meter .bm-amount { font-weight: 800; color: #1a2b1e; }
  .budget-meter.bm-ok { border-color: #bbf7d0; background: #f0fdf4; color: #3f6b4d; }
  .budget-meter.bm-ok .bm-amount { color: #166534; }
  .budget-meter.bm-flex { border-color: #f0e0ad; background: #fdf9ec; color: #7a6431; }
  .budget-meter.bm-flex .bm-amount { color: #85620f; }
  .budget-meter.bm-over { border-color: #f6d0cc; background: #fdf3f2; color: #8c524b; }
  .budget-meter.bm-over .bm-amount { color: #a1352b; }

  .form-sheet {
    background: #ffffff; border: 1px solid #e4ebe6; border-radius: 18px;
    padding: 38px 42px 40px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }

  /* Page header + help button */
  .page-title-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 16px; margin-bottom: 22px; flex-wrap: wrap;
  }
  .help-btn {
    width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
    margin-top: -6.5px; /* center the button on the 24px title line */
    background: #ffffff; border: 1px solid #d7dfda; cursor: pointer;
    color: #143d23; display: flex; align-items: center; justify-content: center;
    transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }
  .help-btn:hover { border-color: #143d23; background: #f3f8f4; transform: translateY(-1px); }
  .help-btn:active { transform: scale(0.96); }
  .help-btn svg { width: 24px; height: 24px; stroke: currentColor; stroke-width: 2.2; fill: none; stroke-linecap: round; stroke-linejoin: round; }

  /* ── Instructions modal ── */
  .imodal-overlay {
    position: fixed; inset: 0; background: rgba(20, 30, 24, 0.45);
    display: flex; align-items: center; justify-content: center;
    z-index: 1500; padding: 18px;
    opacity: 0; visibility: hidden; transition: opacity 0.2s ease, visibility 0.2s ease;
  }
  .imodal-overlay.open { opacity: 1; visibility: visible; }
  .imodal {
    background: #ffffff; border-radius: 18px; width: min(620px, 100%);
    max-height: 84vh; overflow-y: auto;
    box-shadow: 0 28px 70px rgba(15, 25, 18, 0.35);
    transform: translateY(10px) scale(0.98); transition: transform 0.22s ease;
    scrollbar-width: thin;
  }
  .imodal-overlay.open .imodal { transform: translateY(0) scale(1); }
  .imodal-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;
    padding: 20px 24px; border-bottom: 1px solid #f0f2f1;
    position: sticky; top: 0; background: #ffffff; border-radius: 18px 18px 0 0; z-index: 1;
  }
  .imodal-head h3 { font-size: 16px; font-weight: 800; color: #1a2b1e; margin: 0; }
  .imodal-head p { font-size: 13px; color: #879a8c; margin: 4px 0 0 0; }
  .imodal-close {
    width: 32px; height: 32px; border-radius: 9px; border: none; cursor: pointer;
    background: #f0f2f1; color: #6b7c6e; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s ease;
  }
  .imodal-close:hover { background: #fee2e2; color: #b91c1c; }
  .imodal-close svg { width: 15px; height: 15px; stroke: currentColor; stroke-width: 2.2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
  .imodal-body { padding: 22px 24px 26px; }
  .im-step { display: flex; gap: 14px; padding: 12px 0; }
  .im-step + .im-step { border-top: 1px solid #f0f2f1; }
  .im-step-badge {
    width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800;
  }
  .im-step-badge.green { background: #edf1e9; color: #648268; }
  .im-step-badge.blue  { background: #dbeafe; color: #1d4ed8; }
  .im-step-badge.teal  { background: #ccfbf1; color: #0f766e; }
  .im-step-badge.amber { background: #fef3c7; color: #b45309; }
  .im-step-badge.violet{ background: #ede9fe; color: #6d28d9; }
  .im-step-title { font-size: 13.5px; font-weight: 700; color: #1a2b1e; }
  .im-step-desc { font-size: 13px; color: #51605a; line-height: 1.6; margin-top: 3px; }
  .im-note {
    margin-top: 16px; padding: 14px 16px; background: #f0fdf4;
    border: 1px solid #bbf7d0; border-radius: 12px;
    font-size: 12.5px; color: #15803d; line-height: 1.6;
  }

  /* Product Selector */
  .product-selector-wrap { position: relative; }
  .product-search-input {
    width: 100%; padding: 12px 15px 12px 38px; border: 1px solid #d7dfda;
    border-radius: 10px; font-size: 14px; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .product-search-input:focus {
    outline: none; border-color: #648268;
    box-shadow: 0 0 0 3px rgba(100, 130, 104, 0.12);
  }
  .product-search-icon {
    position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
    pointer-events: none; color: #879a8c;
  }
  .product-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #ffffff; border: 1px solid #e0e5e1; border-radius: 11px;
    box-shadow: 0 10px 34px rgba(20,61,35,0.14); max-height: 260px;
    overflow-y: auto; z-index: 50; display: none;
  }
  .product-dropdown.open { display: block; }
  .product-dropdown-item {
    padding: 11px 15px; cursor: pointer; display: flex;
    align-items: center; gap: 12px; font-size: 14px;
    border-bottom: 1px solid #f4f6f5; transition: background 0.15s;
  }
  .product-dropdown-item:last-child { border-bottom: none; }
  .product-dropdown-item:hover { background: #f3f8f4; }
  .product-dropdown-item.selected { background: #f3f8f4; }
  .product-dropdown-item .prod-name { font-weight: 600; color: #1a2b1e; flex: 1; }
  .product-dropdown-item .prod-meta { font-size: 12px; color: #b0b8b3; }
  .product-dropdown-item .prod-price { font-weight: 700; color: #143d23; white-space: nowrap; }
  .product-dropdown-empty {
    padding: 22px 15px; text-align: center; color: #b0b8b3; font-size: 13px;
  }
  .selected-products { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
  .selected-product-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 13px; background: #eef3ef;
    border: 1px solid #d8e3db; border-radius: 9px;
    font-size: 13px; color: #3f7a4f; font-weight: 600;
  }
  .selected-product-chip button {
    background: none; border: none; color: #b0b8b3; cursor: pointer;
    padding: 0; display: flex; line-height: 1; transition: color 0.15s;
  }
  .selected-product-chip button:hover { color: #b0403a; }
  .product-loading { padding: 18px; text-align: center; color: #879a8c; font-size: 13px; }
  .product-prompt {
    padding: 22px; text-align: center; color: #879a8c; font-size: 13px;
    border: 1.5px dashed #d3dcd6; border-radius: 11px; background: #f6f8f7;
  }

  /* Searchable Crop Selector */
  .crop-selector-wrap { position: relative; }
  .crop-search-input {
    width: 100%; padding: 12px 15px 12px 38px; border: 1px solid #d7dfda;
    border-radius: 10px; font-size: 14px; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .crop-search-input:focus {
    outline: none; border-color: #648268;
    box-shadow: 0 0 0 3px rgba(100, 130, 104, 0.12);
  }
  .crop-search-icon {
    position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
    pointer-events: none; color: #879a8c;
  }
  .crop-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #ffffff; border: 1px solid #e0e5e1; border-radius: 11px;
    box-shadow: 0 10px 34px rgba(20,61,35,0.14); max-height: 260px;
    overflow-y: auto; z-index: 50; display: none;
  }
  .crop-dropdown.open { display: block; }
  .crop-dropdown-item {
    padding: 11px 15px; cursor: pointer; font-size: 14px;
    border-bottom: 1px solid #f4f6f5; transition: background 0.15s;
    color: #1a2b1e;
  }
  .crop-dropdown-item:last-child { border-bottom: none; }
  .crop-dropdown-item:hover { background: #f3f8f4; }
  .crop-dropdown-item.active { background: #f3f8f4; font-weight: 600; color: #143d23; }
  .crop-dropdown-empty {
    padding: 22px 15px; text-align: center; color: #879a8c; font-size: 13px;
  }

  .submit-row {
    margin-top: 28px; padding-top: 24px; border-top: 1px solid #f0f2f1;
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'My Cost Quotations', 'url' => route('farmer.quotations.index')],
  ['label' => 'Generate Quotation'],
]"/>

<div class="page-title-row">
  <div>
    <h1 style="font-size: 24px; font-weight: 800; color: #1a2b1e; margin: 0 0 5px 0; letter-spacing: -0.02em;">
      Generate a Cost Quotation
    </h1>
    <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">
      Answer 3 quick questions to get an instant, itemized cost estimate — no approval needed.
    </p>
  </div>
  <button type="button" class="help-btn" id="helpBtn" title="How it works" aria-label="How it works">
    <svg viewBox="0 0 24 24"><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
  </button>
</div>

@if(session('success'))
  <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 11px; padding: 13px 17px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px;">
    <div style="width: 26px; height: 26px; border-radius: 8px; background: rgba(74,122,84,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="15" height="15" stroke="#4A7A54" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span style="font-size: 14px; color: #166534; font-weight: 600;">{{ session('success') }}</span>
  </div>
@endif

@if($errors->any())
  <div style="background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 1px solid #fca5a5; border-radius: 11px; padding: 13px 17px; margin-bottom: 18px;">
    <ul style="margin: 0; padding-left: 20px; color: #dc2626; font-size: 13.5px;">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form action="{{ route('farmer.quotations.store') }}" method="POST" class="form-sheet" id="quotationForm">
  @csrf

  <div class="q-grid">

  <!-- 1. Crop Type -->
  <div class="q-block">
    <div class="q-head">
      <span class="q-num">1</span>
      <label for="crop_type" class="input-label">What crop are you planting?</label>
    </div>
    <div class="q-body">
      <div class="crop-selector-wrap">
        <svg class="crop-search-icon" viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="crop-search" class="crop-search-input"
               placeholder="Search or select a crop..." autocomplete="off"
               value="{{ old('crop_type') }}">
        <input type="hidden" name="crop_type" id="crop_type" value="{{ old('crop_type') }}">
        <div id="crop-dropdown" class="crop-dropdown">
          @foreach($cropTypes as $type)
            <div class="crop-dropdown-item" data-value="{{ $type }}">{{ $type }}</div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <!-- 2. Season -->
  <div class="q-block">
    <div class="q-head">
      <span class="q-num">2</span>
      <label class="input-label">Which season are you planting for?</label>
    </div>
    <div class="q-body">
      <div class="season-grid">
        <label class="season-card {{ $selSeason === 'wet' ? 'is-selected' : '' }}">
          <input type="radio" name="season" value="wet" @checked($selSeason === 'wet') style="accent-color: #143d23;" required>
          <div>
            <div class="sc-title">Wet Season</div>
            <div class="sc-sub">June – January</div>
          </div>
        </label>
        <label class="season-card {{ $selSeason === 'dry' ? 'is-selected' : '' }}">
          <input type="radio" name="season" value="dry" @checked($selSeason === 'dry') style="accent-color: #143d23;" required>
          <div>
            <div class="sc-title">Dry Season</div>
            <div class="sc-sub">February – May</div>
          </div>
        </label>
      </div>
      <div class="input-hint">
        <span class="climate-line">
          <span class="climate-dot {{ $climate['season'] }}"></span>
          <strong>Currently:</strong> {{ $globalSeasonLabel }}
        </span>
      </div>
    </div>
  </div>

  <!-- 3. Land Area -->
  <div class="q-block">
    <div class="q-head">
      <span class="q-num">3</span>
      <label for="land_area" class="input-label">How big is your farm area?</label>
    </div>
    <div class="q-body">
      <div class="area-row">
        <input type="number" name="land_area" id="land_area" class="input-field"
               placeholder="e.g., 1.5" step="0.01" min="0.01" max="999.99"
               value="{{ old('land_area') }}" required>
        <div class="area-unit">hectares</div>
      </div>
      <div class="input-hint">The quantity of each input is calculated from this area.</div>
    </div>
  </div>

  <!-- 4. Field Conditions -->
  <div class="q-block">
    <div class="q-head">
      <span class="q-num">4</span>
      <label class="input-label">What are your field conditions?</label>
    </div>
    <div class="q-body">
      <div class="cond-group">
        <label class="input-label" id="soilLabel">Soil type</label>
        <div class="q-select" data-field="soil_type">
          <input type="hidden" name="soil_type" id="soil_type" value="{{ old('soil_type', 'clay_loam') }}">
          <button type="button" class="q-select-btn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="soilLabel">
            <span class="qsel-title">Clay loam</span>
            <svg class="qsel-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="q-select-menu" role="listbox" aria-labelledby="soilLabel">
            <div class="q-select-item" data-value="clay_loam" role="option">
              <div class="qsel-opt">
                <div class="qsel-t">Clay loam</div>
                <div class="qsel-s">Typical rice soil — no adjustment needed</div>
              </div>
              <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="q-select-item" data-value="sandy" role="option">
              <div class="qsel-opt">
                <div class="qsel-t">Sandy</div>
                <div class="qsel-s">Fertilizer drains faster — adds 15% more fertilizer</div>
              </div>
              <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="q-select-item" data-value="peat" role="option">
              <div class="qsel-opt">
                <div class="qsel-t">Peat</div>
                <div class="qsel-s">Acidic soil — adds the lime cost it needs</div>
              </div>
              <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
          </div>
        </div>
      </div>
      <div class="cond-group">
        <label class="input-label" id="riskLabel">Weather risk this planting</label>
        <div class="q-select" data-field="weather_context">
          <input type="hidden" name="weather_context" id="weather_context" value="{{ old('weather_context', 'normal') }}">
          <button type="button" class="q-select-btn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="riskLabel">
            <span class="qsel-title">Normal season</span>
            <svg class="qsel-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="q-select-menu" role="listbox" aria-labelledby="riskLabel">
            <div class="q-select-item" data-value="normal" role="option">
              <div class="qsel-opt">
                <div class="qsel-t">Normal season</div>
                <div class="qsel-s">No extreme weather expected — nothing extra added</div>
              </div>
              <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="q-select-item" data-value="low_rain" role="option">
              <div class="qsel-opt">
                <div class="qsel-t">Low rainfall</div>
                <div class="qsel-s">Drier than usual — plan for scarce water</div>
              </div>
              <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="q-select-item" data-value="typhoon" role="option">
              <div class="qsel-opt">
                <div class="qsel-t">Typhoon risk</div>
                <div class="qsel-s">Adds extra fungicide + an 8% price buffer</div>
              </div>
              <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="q-select-item" data-value="el_nino" role="option">
              <div class="qsel-opt">
                <div class="qsel-t">El Niño drought</div>
                <div class="qsel-s">Adds irrigation fuel/water fees + a 10% buffer</div>
              </div>
              <svg class="qsel-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
          </div>
        </div>
        <div class="input-hint">Tell us what weather to plan for — the system adds the protective inputs and price buffers each risk needs, so the estimate matches your situation.</div>
      </div>
    </div>
  </div>

  <!-- 5. Budget -->
  <div class="q-block">
    <div class="q-head">
      <span class="q-num">5</span>
      <label for="budget_cap" class="input-label">What&rsquo;s your budget for this planting? <span class="opt-tag">(optional)</span></label>
    </div>
    <div class="q-body">
      <div class="area-row">
        <div class="area-unit">₱</div>
        <input type="number" name="budget_cap" id="budget_cap" class="input-field"
               placeholder="e.g., 25000" step="0.01" min="1" max="99999999.99"
               value="{{ old('budget_cap') }}">
      </div>
      <label class="budget-toggle" for="budget_strict">
        <input type="checkbox" name="budget_strict" id="budget_strict" value="1" @checked(old('budget_strict'))>
        <span class="bt-switch"><span class="bt-knob"></span></span>
        <span>
          <span class="bt-title">Fit suggestions to my budget</span>
          <span class="bt-sub">On: suggestions and included inputs stay within this budget — up to double it, so a must-have just over the line can still be considered. Off: full recommendations, with the budget used as a guide.</span>
        </span>
      </label>
      <div class="input-hint">Leave blank for no limit.</div>
    </div>
  </div>

  <!-- 6. Products -->
  <div class="q-block q-span">
    <div class="q-head">
      <span class="q-num">6</span>
      <label class="input-label">Select products <span class="opt-tag">(optional)</span></label>
    </div>
    <div class="q-body">
      <div id="product-selector-section">
        <div id="product-prompt" class="product-prompt">
          Pick your crop and season above to see the available products.
        </div>
        <div id="product-loading" class="product-loading" style="display: none;">
          <svg viewBox="0 0 24 24" width="15" height="15" stroke="#94a3b8" stroke-width="2" fill="none" style="vertical-align: middle; animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          Loading products...
        </div>
        <div id="product-selector-container" style="display: none;">
          <div class="product-filter-row">
            <select id="product-category" class="input-field pf-select" aria-label="Filter products by category">
              <option value="">All categories</option>
            </select>
            <span id="budget-filter-chip" class="pf-chip" style="display: none;">
              <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg>
              Budget <b id="budget-chip-budget">₱0</b> · up to <b id="budget-chip-amount">₱0</b>
            </span>
          </div>
          <div class="product-selector-wrap">
            <svg class="product-search-icon" viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="product-search" class="product-search-input"
                   placeholder="Search products by name, brand, or SKU..." autocomplete="off">
            <div id="product-dropdown" class="product-dropdown"></div>
          </div>
          <div id="selected-products" class="selected-products"></div>
          <div id="budget-meter" class="budget-meter" style="display: none;" aria-live="polite"></div>
        </div>
      </div>
      <div class="input-hint">Leave empty to auto-include the best input for each job for your crop and season — with &lsquo;Fit suggestions to my budget&rsquo; on, the plan is fitted live inside your budget (up to twice it only when a must-have needs it).</div>
    </div>
  </div>

  <!-- 7. Notes -->
  <div class="q-block q-span">
    <div class="q-head">
      <span class="q-num">7</span>
      <label for="notes" class="input-label">Additional notes <span class="opt-tag">(optional)</span></label>
    </div>
    <div class="q-body">
      <textarea name="notes" id="notes" class="input-field" rows="3"
                placeholder="Any specific requirements, preferred varieties, or special instructions...">{{ old('notes') }}</textarea>
    </div>
  </div>

  </div>

  <div class="submit-row">
    <button type="submit" class="submit-btn">
      <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
      Generate Cost Quotation
    </button>
  </div>
</form>

<!-- Instructions modal -->
<div class="imodal-overlay" id="helpModal" aria-hidden="true">
  <div class="imodal" role="dialog" aria-modal="true" aria-labelledby="helpModalTitle">
    <div class="imodal-head">
      <div>
        <h3 id="helpModalTitle">How to Generate a Cost Quotation</h3>
        <p>A few quick questions, one instant estimate</p>
      </div>
      <button type="button" class="imodal-close" id="helpModalClose" aria-label="Close">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="imodal-body">
      <div class="im-step">
        <div class="im-step-badge green">
          <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <div>
          <div class="im-step-title">1. Pick your crop &amp; season</div>
          <div class="im-step-desc">Search the crop directory (e.g., Rice, Corn) and choose the planting season. This sets the recommended input rates.</div>
        </div>
      </div>
      <div class="im-step">
        <div class="im-step-badge blue">
          <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </div>
        <div>
          <div class="im-step-title">2. Enter your farm area</div>
          <div class="im-step-desc">Tell us your land area in hectares. Every input quantity is computed from this number.</div>
        </div>
      </div>
      <div class="im-step">
        <div class="im-step-badge teal">
          <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <div>
          <div class="im-step-title">3. Field conditions &amp; budget</div>
          <div class="im-step-desc">Pick your soil type and the weather risk you expect, so quantities and price buffers match your situation. Set an optional budget — switch on &ldquo;Fit suggestions to my budget&rdquo; to have the plan fitted inside it (up to twice your budget, only when a must-have needs it).</div>
        </div>
      </div>
      <div class="im-step">
        <div class="im-step-badge amber">
          <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        </div>
        <div>
          <div class="im-step-title">4. Select products (optional)</div>
          <div class="im-step-desc">Choose specific inputs and their quantities. Leave this empty to auto-include every recommended input.</div>
        </div>
      </div>
      <div class="im-step">
        <div class="im-step-badge violet">
          <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div>
          <div class="im-step-title">5. Generate &amp; review</div>
          <div class="im-step-desc">Hit &ldquo;Generate Cost Quotation&rdquo; and get a plain-language summary with a full itemized breakdown on the next page.</div>
        </div>
      </div>
      <div class="im-note">
        <strong>How the price works:</strong> recommended rate per hectare &times; your land area = quantity needed; quantity &times; current store price = each line. With a budget set, essentials always stay and optional items are listed as deferred so you can add them later. Quotations are valid for 14 days and are for planning — not a purchase commitment.
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  // --- Searchable Crop Selector ---
  const cropSearchInput = document.getElementById('crop-search');
  const cropHiddenInput = document.getElementById('crop_type');
  const cropDropdown = document.getElementById('crop-dropdown');
  const cropItems = Array.from(cropDropdown.querySelectorAll('.crop-dropdown-item'));

  function filterCrops(query) {
    const q = query.toLowerCase();
    let anyVisible = false;
    cropItems.forEach(item => {
      const match = item.dataset.value.toLowerCase().includes(q);
      item.style.display = match ? '' : 'none';
      if (match) anyVisible = true;
    });
    if (!anyVisible && q) {
      let customItem = cropDropdown.querySelector('.crop-custom-option');
      if (!customItem) {
        customItem = document.createElement('div');
        customItem.className = 'crop-dropdown-item crop-custom-option';
        cropDropdown.appendChild(customItem);
      }
      customItem.textContent = 'Use "' + query + '" as crop type';
      customItem.dataset.value = query;
      customItem.style.display = '';
      customItem.onclick = function () {
        selectCrop(query);
      };
    } else {
      const customItem = cropDropdown.querySelector('.crop-custom-option');
      if (customItem) customItem.remove();
    }
  }

  function selectCrop(value) {
    cropHiddenInput.value = value;
    cropSearchInput.value = value;
    cropDropdown.classList.remove('open');
    cropItems.forEach(item => {
      item.classList.toggle('active', item.dataset.value === value);
    });
    cropHiddenInput.dispatchEvent(new Event('change'));
  }

  cropSearchInput.addEventListener('focus', function () {
    cropDropdown.classList.add('open');
    filterCrops(cropSearchInput.value);
  });

  cropSearchInput.addEventListener('input', function () {
    cropDropdown.classList.add('open');
    filterCrops(cropSearchInput.value);
    cropHiddenInput.value = '';
  });

  cropDropdown.addEventListener('click', function (e) {
    const item = e.target.closest('.crop-dropdown-item');
    if (item) selectCrop(item.dataset.value);
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.crop-selector-wrap')) {
      cropDropdown.classList.remove('open');
    }
  });

  // --- Custom select dropdowns (soil type, weather risk) ---
  document.querySelectorAll('.q-select').forEach(function (sel) {
    const hidden = sel.querySelector('input[type="hidden"]');
    const btn = sel.querySelector('.q-select-btn');
    const title = sel.querySelector('.qsel-title');
    const items = Array.from(sel.querySelectorAll('.q-select-item'));

    function sync() {
      items.forEach(function (i) {
        i.classList.toggle('is-active', i.dataset.value === hidden.value);
      });
      const active = items.find(function (i) { return i.dataset.value === hidden.value; });
      title.textContent = active ? active.querySelector('.qsel-t').textContent : 'Select…';
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const isOpen = sel.classList.contains('open');
      document.querySelectorAll('.q-select.open').forEach(function (o) {
        o.classList.remove('open');
        o.querySelector('.q-select-btn').setAttribute('aria-expanded', 'false');
      });
      if (!isOpen) {
        sel.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
      }
    });

    items.forEach(function (i) {
      i.addEventListener('click', function () {
        hidden.value = i.dataset.value;
        sel.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
        sync();
      });
    });

    sync();
  });

  document.addEventListener('click', function (e) {
    document.querySelectorAll('.q-select.open').forEach(function (o) {
      if (!o.contains(e.target)) {
        o.classList.remove('open');
        o.querySelector('.q-select-btn').setAttribute('aria-expanded', 'false');
      }
    });
  });

  // --- Product Selector ---
  const cropInput = cropHiddenInput;
  const seasonInputs = document.querySelectorAll('input[name="season"]');
  const promptEl = document.getElementById('product-prompt');
  const loadingEl = document.getElementById('product-loading');
  const selectorEl = document.getElementById('product-selector-container');
  const searchInput = document.getElementById('product-search');
  const dropdownEl = document.getElementById('product-dropdown');
  const selectedEl = document.getElementById('selected-products');

  let allProducts = [];
  let selectedIds = new Set();
  let debounceTimer = null;

  const productsUrl = '{{ route("farmer.quotations.products") }}';
  const csrfToken = '{{ csrf_token() }}';

  function getSelectedSeason() {
    const checked = document.querySelector('input[name="season"]:checked');
    return checked ? checked.value : null;
  }

  function fetchProducts() {
    const cropType = cropInput.value.trim();
    const season = getSelectedSeason();

    if (!cropType || !season) {
      promptEl.style.display = '';
      loadingEl.style.display = 'none';
      selectorEl.style.display = 'none';
      allProducts = [];
      selectedIds.clear();
      renderSelected();
      return;
    }

    promptEl.style.display = 'none';
    loadingEl.style.display = '';
    selectorEl.style.display = 'none';

    fetch(`${productsUrl}?crop_type=${encodeURIComponent(cropType)}&season=${season}`, {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(products => {
      allProducts = products;
      loadingEl.style.display = 'none';
      selectorEl.style.display = '';
      populateCategoryOptions();
      renderDropdown('');
    })
    .catch(() => {
      loadingEl.innerHTML = '<span style="color:#dc2626;">Failed to load products.</span>';
    });
  }

  function renderDropdown(query) {
    const q = query.toLowerCase();
    const cat = categorySelect ? categorySelect.value : '';
    const cap = activeBudgetCap();

    let filtered = allProducts.filter(p =>
      (!q || p.name.toLowerCase().includes(q) || (p.brand||'').toLowerCase().includes(q) || (p.sku||'').toLowerCase().includes(q))
      && (!cat || p.category === cat)
      && (!cap || parseFloat(p.price) <= cap)
    );
    if (cap) filtered = filtered.slice().sort((a, b) => parseFloat(a.price) - parseFloat(b.price));

    if (filtered.length === 0) {
      dropdownEl.innerHTML = cap
        ? '<div class="product-dropdown-empty">No products up to ₱' + cap.toLocaleString('en-PH') + ' (your budget’s fit limit)' + (cat ? ' in ' + escapeHtml(cat) : '') + ' for this crop and season.</div>'
        : '<div class="product-dropdown-empty">No products found for this crop and season.</div>';
    } else {
      dropdownEl.innerHTML = filtered.map(p => `
        <div class="product-dropdown-item ${selectedIds.has(p.id) ? 'selected' : ''}" data-id="${p.id}">
          <div style="flex: 1; min-width: 0;">
            <div class="prod-name">${escapeHtml(p.name)}</div>
            <div class="prod-meta">${escapeHtml(p.brand || '')} ${p.sku ? '· ' + escapeHtml(p.sku) : ''} ${p.category ? '· ' + escapeHtml(p.category) : ''}</div>
          </div>
          <div class="prod-price">₱${parseFloat(p.price).toFixed(2)}<span style="font-weight:400;font-size:11px;color:#94a3b8;">/${escapeHtml(p.price_unit || 'ea')}</span></div>
          ${selectedIds.has(p.id) ? '<svg viewBox="0 0 24 24" width="16" height="16" stroke="#4A7A54" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>' : ''}
        </div>
      `).join('');
    }

    dropdownEl.classList.add('open');
  }

  function toggleProduct(id) {
    const product = allProducts.find(p => p.id === id);
    if (!product) return;

    if (selectedIds.has(id)) {
      selectedIds.delete(id);
    } else {
      selectedIds.add(id);
    }
    renderSelected();
    renderDropdown(searchInput.value);
  }

  function renderSelected() {
    if (selectedIds.size === 0) {
      selectedEl.innerHTML = '';
      updateHiddenInput();
      updateBudgetMeter();
      return;
    }

    selectedEl.innerHTML = [...selectedIds].map(id => {
      const p = allProducts.find(x => x.id === id);
      if (!p) return '';
      return `<span class="selected-product-chip">
        ${escapeHtml(p.name)}
        <button type="button" onclick="window._removeProduct(${p.id})" title="Remove">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </span>`;
    }).join('');

    updateHiddenInput();
    updateBudgetMeter();
  }

  // Live budget total: recalculated the moment a product, the budget, or
  // the fit toggle changes — the farmer sees where the picks stand against
  // the budget (and its 2× fit limit) before ever generating. Reads its
  // own elements so it can run whenever renderSelected does.
  function updateBudgetMeter() {
    const meter = document.getElementById('budget-meter');
    if (!meter) return;

    const cap = parseFloat(document.getElementById('budget_cap')?.value);
    const toggle = document.getElementById('budget_strict');
    const strictOn = !!(toggle && toggle.checked && !toggle.disabled && cap > 0);

    if (!(cap > 0)) {
      meter.style.display = 'none';
      meter.className = 'budget-meter';
      return;
    }

    const ceiling = strictOn ? cap * 2 : cap;
    const total = [...selectedIds].reduce((sum, id) => {
      const p = allProducts.find(x => x.id === id);
      return sum + (p ? parseFloat(p.price) : 0);
    }, 0);
    const peso = (n) => '₱' + n.toLocaleString('en-PH', { maximumFractionDigits: 2 });

    let cls, html;
    if (selectedIds.size === 0) {
      cls = strictOn ? 'bm-flex' : '';
      html = strictOn
        ? '<span class="bm-amount">' + peso(0) + '</span> selected — leave empty and the system will fit the best suggestions up to <span class="bm-amount">' + peso(ceiling) + '</span> (2× your ' + peso(cap) + ' budget).'
        : 'Budget noted: <span class="bm-amount">' + peso(cap) + '</span>. Leave products empty to auto-include the best input for each job, or pick your own.';
    } else if (strictOn && total > ceiling) {
      cls = 'bm-over';
      html = 'Selected <span class="bm-amount">' + peso(total) + '</span> — past your ' + peso(ceiling) + ' fit limit (2× the ' + peso(cap) + ' budget). The quotation will trim what no longer fits.';
    } else if (strictOn && total > cap) {
      cls = 'bm-flex';
      html = 'Selected <span class="bm-amount">' + peso(total) + '</span> — over your ' + peso(cap) + ' budget but within the ' + peso(ceiling) + ' flexible limit.';
    } else if (strictOn) {
      cls = 'bm-ok';
      html = 'Selected <span class="bm-amount">' + peso(total) + '</span> of your ' + peso(cap) + ' budget — ' + peso(cap - total) + ' still free.';
    } else {
      cls = '';
      html = 'Selected <span class="bm-amount">' + peso(total) + '</span> · budget <span class="bm-amount">' + peso(cap) + '</span> (a guide — fit mode keeps the plan inside it).';
    }

    meter.className = 'budget-meter ' + cls;
    meter.innerHTML = html;
    meter.style.display = '';
  }

  function updateHiddenInput() {
    let hidden = document.getElementById('selected_product_ids');
    if (!hidden) {
      hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'selected_product_ids';
      hidden.id = 'selected_product_ids';
      document.getElementById('quotationForm').appendChild(hidden);
    }
    hidden.value = JSON.stringify([...selectedIds]);
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  window._removeProduct = function (id) {
    selectedIds.delete(id);
    renderSelected();
    renderDropdown(searchInput.value);
  };

  cropInput.addEventListener('change', fetchProducts);
  seasonInputs.forEach(r => r.addEventListener('change', fetchProducts));

  // Season cards: mirror the radio state onto the card styling so the
  // highlight follows the click without a round-trip.
  const seasonCards = Array.from(document.querySelectorAll('.season-card'));
  seasonInputs.forEach(r => r.addEventListener('change', function () {
    seasonCards.forEach(c => c.classList.toggle(
      'is-selected',
      c.querySelector('input[name="season"]').checked
    ));
  }));

  searchInput.addEventListener('focus', () => renderDropdown(searchInput.value));
  searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => renderDropdown(searchInput.value), 150);
  });

  dropdownEl.addEventListener('click', (e) => {
    const item = e.target.closest('.product-dropdown-item');
    if (item) toggleProduct(parseInt(item.dataset.id));
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.product-selector-wrap')) {
      dropdownEl.classList.remove('open');
    }
  });

  // --- Budget fitting toggle + category filter ---
  const budgetInput = document.getElementById('budget_cap');
  const strictToggle = document.getElementById('budget_strict');
  const strictWrap = strictToggle ? strictToggle.closest('.budget-toggle') : null;
  const categorySelect = document.getElementById('product-category');
  const budgetChip = document.getElementById('budget-filter-chip');
  const budgetChipAmount = document.getElementById('budget-chip-amount');
  const budgetChipBudget = document.getElementById('budget-chip-budget');

  // The hard ceiling the suggestion list is filtered by — the stated
  // budget, or twice it while "fit suggestions to my budget" is on
  // (the 2× flexibility the engine fits against too).
  function activeBudgetCap() {
    const cap = parseFloat(budgetInput && budgetInput.value);
    const on = strictToggle && strictToggle.checked && !strictToggle.disabled && cap > 0;
    return on ? cap * 2 : null;
  }

  function populateCategoryOptions() {
    if (!categorySelect) return;
    const current = categorySelect.value;
    const cats = [...new Set(allProducts.map(p => p.category).filter(Boolean))]
      .sort((a, b) => a.localeCompare(b));
    categorySelect.innerHTML = '<option value="">All categories</option>' +
      cats.map(c => '<option value="' + escapeHtml(c) + '">' + escapeHtml(c) + '</option>').join('');
    categorySelect.value = cats.includes(current) ? current : '';
  }

  function syncBudgetUI() {
    const hasBudget = parseFloat(budgetInput && budgetInput.value) > 0;
    if (strictToggle) {
      if (!hasBudget) {
        strictToggle.checked = false;
        strictToggle.disabled = true;
      } else {
        strictToggle.disabled = false;
      }
    }
    if (strictWrap) {
      strictWrap.classList.toggle('is-disabled', !hasBudget);
      strictWrap.classList.toggle('is-on', hasBudget && strictToggle.checked);
    }
    const active = activeBudgetCap();
    const stated = parseFloat(budgetInput && budgetInput.value);
    if (budgetChip) {
      budgetChip.style.display = active ? '' : 'none';
      if (active && budgetChipAmount) {
        budgetChipAmount.textContent = '₱' + active.toLocaleString('en-PH', { maximumFractionDigits: 2 });
      }
      if (active && budgetChipBudget) {
        budgetChipBudget.textContent = '₱' + stated.toLocaleString('en-PH', { maximumFractionDigits: 2 });
      }
    }
  }

  function refreshSuggestions() {
    syncBudgetUI();
    populateCategoryOptions();
    updateBudgetMeter();
    if (dropdownEl.classList.contains('open')) renderDropdown(searchInput.value);
  }

  if (budgetInput) budgetInput.addEventListener('input', refreshSuggestions);
  if (strictToggle) strictToggle.addEventListener('change', refreshSuggestions);
  if (categorySelect) categorySelect.addEventListener('change', () => renderDropdown(searchInput.value));
  syncBudgetUI();
  updateBudgetMeter();

  if (cropInput.value.trim() && getSelectedSeason()) {
    fetchProducts();
  }

  // --- Instructions modal ---
  const helpBtn = document.getElementById('helpBtn');
  const helpModal = document.getElementById('helpModal');
  const helpModalClose = document.getElementById('helpModalClose');

  function openHelpModal() {
    helpModal.classList.add('open');
    helpModal.setAttribute('aria-hidden', 'false');
  }
  function closeHelpModal() {
    helpModal.classList.remove('open');
    helpModal.setAttribute('aria-hidden', 'true');
  }

  if (helpBtn) helpBtn.addEventListener('click', openHelpModal);
  if (helpModalClose) helpModalClose.addEventListener('click', closeHelpModal);
  if (helpModal) {
    helpModal.addEventListener('click', (e) => {
      if (e.target === helpModal) closeHelpModal();
    });
  }
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && helpModal.classList.contains('open')) closeHelpModal();
  });
})();
</script>
<style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
@endsection