@extends('layouts.sidebar')

@section('title', 'Generate Cost Quotation')

@section('content')
@php
  $selSeason = old('season') ?? $globalSeason;
  $climate = $globalClimateInfo;
  $cardStyle = function ($season) use ($selSeason) {
      $selected = $selSeason === $season;
      return 'display: flex; align-items: center; gap: 10px; padding: 13px 16px; border: 1.5px solid ' . ($selected ? '#143d23' : '#d7dfda') . '; border-radius: 11px; cursor: pointer; transition: all 0.15s; background: ' . ($selected ? '#f0fdf4' : '#ffffff') . ';';
  };
  $climateDotStyle = 'display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: ' . ($climate['season'] === 'wet' ? '#0284c7' : '#d97706') . ';';
@endphp
<style>
  .input-field {
    width: 100%; padding: 12px 15px; border: 1px solid #d7dfda;
    border-radius: 10px; font-size: 14px; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .input-field:focus {
    outline: none; border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.08);
  }
  .input-label {
    display: block; font-size: 13.5px; font-weight: 700;
    color: #1a2b1e; margin-bottom: 7px;
  }
  .input-hint {
    font-size: 12px; color: #879a8c; margin-top: 7px; line-height: 1.45;
  }
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

  /* Guided question layout — clean, organized, breathable */
  .q-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px 40px;
    align-items: start;
  }
  .q-block { display: flex; gap: 15px; }
  .q-span { grid-column: 1 / -1; }
  @media (max-width: 860px) { .q-grid { grid-template-columns: 1fr; } }
  .q-num {
    width: 34px; height: 34px; border-radius: 11px; flex-shrink: 0;
    background: #eef5ee; color: #143d23;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 800; margin-top: 1px;
  }
  .q-body { flex: 1; min-width: 0; }

  .form-sheet {
    background: #ffffff; border: 1px solid #e4ebe6; border-radius: 18px;
    padding: 34px 38px 36px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }

  /* Page header + help button */
  .page-title-row {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 16px; margin-bottom: 22px; flex-wrap: wrap;
  }
  .help-btn {
    width: 44px; height: 44px; border-radius: 13px; flex-shrink: 0;
    background: #ffffff; border: 1px solid #d7dfda; cursor: pointer;
    color: #143d23; display: flex; align-items: center; justify-content: center;
    transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }
  .help-btn:hover { border-color: #143d23; background: #f0fdf4; transform: translateY(-1px); }
  .help-btn:active { transform: scale(0.96); }
  .help-btn svg { width: 19px; height: 19px; stroke: currentColor; stroke-width: 2.2; fill: none; stroke-linecap: round; stroke-linejoin: round; }

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
  .im-step-badge.green { background: #dcfce7; color: #166534; }
  .im-step-badge.blue  { background: #dbeafe; color: #1d4ed8; }
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
    outline: none; border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.08);
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
  .product-dropdown-item:hover { background: #f0fdf4; }
  .product-dropdown-item.selected { background: #f0fdf4; }
  .product-dropdown-item .prod-name { font-weight: 600; color: #1a2b1e; flex: 1; }
  .product-dropdown-item .prod-meta { font-size: 12px; color: #b0b8b3; }
  .product-dropdown-item .prod-price { font-weight: 700; color: #143d23; white-space: nowrap; }
  .product-dropdown-empty {
    padding: 22px 15px; text-align: center; color: #b0b8b3; font-size: 13px;
  }
  .selected-products { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
  .selected-product-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 13px; background: #f0fdf4;
    border: 1px solid #bbf7d0; border-radius: 9px;
    font-size: 13px; color: #166534; font-weight: 600;
  }
  .selected-product-chip button {
    background: none; border: none; color: #b0b8b3; cursor: pointer;
    padding: 0; display: flex; line-height: 1; transition: color 0.15s;
  }
  .selected-product-chip button:hover { color: #b0403a; }
  .product-loading { padding: 18px; text-align: center; color: #879a8c; font-size: 13px; }
  .product-prompt {
    padding: 22px; text-align: center; color: #879a8c; font-size: 13px;
    border: 1.5px dashed #d3dcd6; border-radius: 11px; background: #fafdfb;
  }

  /* Searchable Crop Selector */
  .crop-selector-wrap { position: relative; }
  .crop-search-input {
    width: 100%; padding: 12px 15px 12px 38px; border: 1px solid #d7dfda;
    border-radius: 10px; font-size: 14px; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .crop-search-input:focus {
    outline: none; border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.08);
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
  .crop-dropdown-item:hover { background: #f0fdf4; }
  .crop-dropdown-item.active { background: #f0fdf4; font-weight: 600; color: #143d23; }
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
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
  </button>
</div>

@if(session('success'))
  <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 11px; padding: 13px 17px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px;">
    <div style="width: 26px; height: 26px; border-radius: 8px; background: rgba(22,163,74,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="15" height="15" stroke="#16a34a" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
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
    <div class="q-num">1</div>
    <div class="q-body">
      <label for="crop_type" class="input-label">What crop are you planting?</label>
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
      <div class="input-hint">Type to search the crop directory, or pick from the list.</div>
    </div>
  </div>

  <!-- 2. Season -->
  <div class="q-block">
    <div class="q-num">2</div>
    <div class="q-body">
      <label class="input-label">Which season are you planting for?</label>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <label data-inline-style="{{ $cardStyle('wet') }}">
          <input type="radio" name="season" value="wet" @checked((old('season') ?? $globalSeason) === 'wet') style="accent-color: #143d23;" required>
          <div>
            <div style="font-weight: 600; font-size: 14px; color: #1e2922;">Wet Season</div>
            <div style="font-size: 12px; color: #94a3b8;">June – January</div>
          </div>
        </label>
        <label data-inline-style="{{ $cardStyle('dry') }}">
          <input type="radio" name="season" value="dry" @checked((old('season') ?? $globalSeason) === 'dry') style="accent-color: #143d23;" required>
          <div>
            <div style="font-weight: 600; font-size: 14px; color: #1e2922;">Dry Season</div>
            <div style="font-size: 12px; color: #94a3b8;">February – May</div>
          </div>
        </label>
      </div>
      <div class="input-hint">
        <span style="display: flex; align-items: center; gap: 6px;">
          <span data-inline-style="{{ $climateDotStyle }}"></span>
          <strong>Currently:</strong> {{ $globalSeasonLabel }} — {{ $climate['months'] }}
          @if(isset($climate['sub_season']))
            <span style="color: #64748b;">({{ $climate['sub_season'] }})</span>
          @endif
        </span>
      </div>
    </div>
  </div>

  <!-- 3. Land Area -->
  <div class="q-block">
    <div class="q-num">3</div>
    <div class="q-body">
      <label for="land_area" class="input-label">How big is your farm area?</label>
      <div style="display: flex; gap: 10px; max-width: 320px;">
        <input type="number" name="land_area" id="land_area" class="input-field"
               placeholder="e.g., 1.5" step="0.01" min="0.01" max="999.99"
               value="{{ old('land_area') }}" required>
        <div style="display: flex; align-items: center; padding: 0 14px; background: #f4f6f5; border: 1px solid #d7dfda; border-radius: 10px; font-size: 14px; color: #64748b; font-weight: 600; white-space: nowrap;">hectares</div>
      </div>
      <div class="input-hint">The quantity of each input is calculated from this area.</div>
    </div>
  </div>

  <!-- 4. Products -->
  <div class="q-block q-span">
    <div class="q-num">4</div>
    <div class="q-body">
      <label class="input-label">Select products <span style="color: #94a3b8; font-weight: 400;">(optional)</span></label>
      <div id="product-selector-section">
        <div id="product-prompt" class="product-prompt">
          Pick your crop and season above to see the available products.
        </div>
        <div id="product-loading" class="product-loading" style="display: none;">
          <svg viewBox="0 0 24 24" width="15" height="15" stroke="#94a3b8" stroke-width="2" fill="none" style="vertical-align: middle; animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          Loading products...
        </div>
        <div id="product-selector-container" style="display: none;">
          <div class="product-selector-wrap">
            <svg class="product-search-icon" viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="product-search" class="product-search-input"
                   placeholder="Search products by name, brand, or SKU..." autocomplete="off">
            <div id="product-dropdown" class="product-dropdown"></div>
          </div>
          <div id="selected-products" class="selected-products"></div>
        </div>
      </div>
      <div class="input-hint">Leave empty to auto-include every input recommended for your crop and season.</div>
    </div>
  </div>

  <!-- 5. Notes -->
  <div class="q-block q-span">
    <div class="q-num">5</div>
    <div class="q-body">
      <label for="notes" class="input-label">Additional notes <span style="color: #94a3b8; font-weight: 400;">(optional)</span></label>
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
    <span style="font-size: 12.5px; color: #879a8c;">Instant estimate — store owners can see which products you need.</span>
  </div>
</form>

<!-- Instructions modal -->
<div class="imodal-overlay" id="helpModal" aria-hidden="true">
  <div class="imodal" role="dialog" aria-modal="true" aria-labelledby="helpModalTitle">
    <div class="imodal-head">
      <div>
        <h3 id="helpModalTitle">How to Generate a Cost Quotation</h3>
        <p>Three quick questions, one instant estimate</p>
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
        <div class="im-step-badge amber">
          <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        </div>
        <div>
          <div class="im-step-title">3. Select products (optional)</div>
          <div class="im-step-desc">Choose specific inputs and their quantities. Leave this empty to auto-include every recommended input.</div>
        </div>
      </div>
      <div class="im-step">
        <div class="im-step-badge violet">
          <svg viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div>
          <div class="im-step-title">4. Generate &amp; review</div>
          <div class="im-step-desc">Hit &ldquo;Generate Cost Quotation&rdquo; and get a plain-language summary with a full itemized breakdown on the next page.</div>
        </div>
      </div>
      <div class="im-note">
        <strong>How the price works:</strong> recommended rate per hectare &times; your land area = quantity needed; quantity &times; current store price = each line. Quotations are valid for 14 days and are for planning — not a purchase commitment.
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
      renderDropdown('');
    })
    .catch(() => {
      loadingEl.innerHTML = '<span style="color:#dc2626;">Failed to load products.</span>';
    });
  }

  function renderDropdown(query) {
    const q = query.toLowerCase();
    const filtered = q
      ? allProducts.filter(p => p.name.toLowerCase().includes(q) || (p.brand||'').toLowerCase().includes(q) || (p.sku||'').toLowerCase().includes(q))
      : allProducts;

    if (filtered.length === 0) {
      dropdownEl.innerHTML = '<div class="product-dropdown-empty">No products found for this crop and season.</div>';
    } else {
      dropdownEl.innerHTML = filtered.map(p => `
        <div class="product-dropdown-item ${selectedIds.has(p.id) ? 'selected' : ''}" data-id="${p.id}">
          <div style="flex: 1; min-width: 0;">
            <div class="prod-name">${escapeHtml(p.name)}</div>
            <div class="prod-meta">${escapeHtml(p.brand || '')} ${p.sku ? '· ' + escapeHtml(p.sku) : ''} ${p.category ? '· ' + escapeHtml(p.category) : ''}</div>
          </div>
          <div class="prod-price">₱${parseFloat(p.price).toFixed(2)}<span style="font-weight:400;font-size:11px;color:#94a3b8;">/${escapeHtml(p.price_unit || 'ea')}</span></div>
          ${selectedIds.has(p.id) ? '<svg viewBox="0 0 24 24" width="16" height="16" stroke="#16a34a" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>' : ''}
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