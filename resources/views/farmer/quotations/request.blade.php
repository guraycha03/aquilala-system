@extends('layouts.sidebar')

@section('title', 'Generate Cost Quotation')

@section('content')
@php
  $selSeason = old('season') ?? $globalSeason;
  $climate = $globalClimateInfo;
  $cardStyle = function ($season) use ($selSeason) {
      return 'display: flex; align-items: center; gap: 10px; padding: 14px 16px; border: 2px solid ' . ($selSeason === $season ? '#143d23' : '#e8ecea') . '; border-radius: 12px; cursor: pointer; transition: all 0.2s; background: ' . ($selSeason === $season ? 'linear-gradient(135deg, #f0fdf4, #dcfce7)' : '#ffffff') . ';';
  };
  $climateDotStyle = 'display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: ' . ($climate['season'] === 'wet' ? '#0284c7' : '#d97706') . ';';
@endphp
<style>
  .input-field {
    width: 100%; padding: 13px 16px; border: 1px solid #e2e8f0;
    border-radius: 12px; font-size: 14px; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .input-field:focus {
    outline: none; border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.08);
  }
  .input-label {
    display: block; font-size: 13px; font-weight: 700;
    color: #1a2b1e; margin-bottom: 6px;
  }
  .input-hint {
    font-size: 12px; color: #879a8c; margin-top: 6px; line-height: 1.4;
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
    padding: 14px 32px; border-radius: 12px; font-size: 15px;
    font-weight: 700; cursor: pointer; transition: all 0.2s;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 16px rgba(20,61,35,0.25);
  }
  .submit-btn:hover { box-shadow: 0 6px 24px rgba(20,61,35,0.3); transform: translateY(-1px); }
  .submit-btn:active { transform: scale(0.98); }

  .info-card {
    background: #ffffff; border: 1px solid #f0f2f1;
    border-radius: 14px; padding: 20px; display: flex; gap: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    transition: all 0.2s;
  }
  .info-card:hover { transform: translateY(-1px); box-shadow: 0 4px 20px rgba(0,0,0,0.06); }

  /* Product Selector */
  .product-selector-wrap { position: relative; }
  .product-search-input {
    width: 100%; padding: 13px 16px 13px 40px; border: 1px solid #e2e8f0;
    border-radius: 12px; font-size: 14px; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .product-search-input:focus {
    outline: none; border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.08);
  }
  .product-search-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    pointer-events: none; color: #879a8c;
  }
  .product-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #ffffff; border: 1px solid #e8ecea; border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1); max-height: 260px;
    overflow-y: auto; z-index: 50; display: none;
  }
  .product-dropdown.open { display: block; }
  .product-dropdown-item {
    padding: 12px 16px; cursor: pointer; display: flex;
    align-items: center; gap: 12px; font-size: 14px;
    border-bottom: 1px solid #f5f7f6; transition: background 0.15s;
  }
  .product-dropdown-item:last-child { border-bottom: none; }
  .product-dropdown-item:hover { background: #f0fdf4; }
  .product-dropdown-item.selected { background: #f0fdf4; }
  .product-dropdown-item .prod-name { font-weight: 600; color: #1a2b1e; flex: 1; }
  .product-dropdown-item .prod-meta { font-size: 12px; color: #b0b8b3; }
  .product-dropdown-item .prod-price { font-weight: 700; color: #143d23; white-space: nowrap; }
  .product-dropdown-empty {
    padding: 24px 16px; text-align: center; color: #b0b8b3; font-size: 13px;
  }
  .selected-products { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
  .selected-product-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #bbf7d0; border-radius: 10px;
    font-size: 13px; color: #166534; font-weight: 600;
  }
  .selected-product-chip button {
    background: none; border: none; color: #b0b8b3; cursor: pointer;
    padding: 0; display: flex; line-height: 1; transition: color 0.15s;
  }
  .selected-product-chip button:hover { color: #dc2626; }
  .product-loading { padding: 20px; text-align: center; color: #879a8c; font-size: 13px; }
  .product-prompt {
    padding: 24px; text-align: center; color: #879a8c; font-size: 13px;
    border: 2px dashed #e8ecea; border-radius: 12px; background: #fafdfb;
  }

  /* Searchable Crop Selector */
  .crop-selector-wrap { position: relative; }
  .crop-search-input {
    width: 100%; padding: 13px 16px 13px 40px; border: 1px solid #e2e8f0;
    border-radius: 12px; font-size: 14px; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .crop-search-input:focus {
    outline: none; border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.08);
  }
  .crop-search-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    pointer-events: none; color: #879a8c;
  }
  .crop-dropdown {
    position: absolute; top: calc(100% + 4px); left: 0; right: 0;
    background: #ffffff; border: 1px solid #e8ecea; border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1); max-height: 260px;
    overflow-y: auto; z-index: 50; display: none;
  }
  .crop-dropdown.open { display: block; }
  .crop-dropdown-item {
    padding: 12px 16px; cursor: pointer; font-size: 14px;
    border-bottom: 1px solid #f5f7f6; transition: background 0.15s;
    color: #1a2b1e;
  }
  .crop-dropdown-item:last-child { border-bottom: none; }
  .crop-dropdown-item:hover { background: #f0fdf4; }
  .crop-dropdown-item.active { background: #f0fdf4; font-weight: 600; color: #143d23; }
  .crop-dropdown-empty {
    padding: 24px 16px; text-align: center; color: #879a8c; font-size: 13px;
  }

  @media (max-width: 768px) {
    .info-row { grid-template-columns: 1fr !important; }
    #info-help-btn { right: 8px !important; top: -52px !important; }
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'My Cost Quotations', 'url' => route('farmer.quotations.index')],
  ['label' => 'Request Quotation'],
]"/>

<div style="margin-bottom: 28px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; position: relative;">
  <div>
    <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">
      Generate a Cost Quotation
    </h1>
    <p style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">
      Get an instant farming cost breakdown — no approval needed. Store owners see this as demand intel.
    </p>
  </div>
  <button type="button" id="info-help-btn" aria-label="Help and information"
          style="width: 40px; height: 40px; flex-shrink: 0; position: absolute; top: 0; right: -11px; border-radius: 50%; background: #ffffff; border: 1px solid #e2e8f0; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.2s;">
    <img src="{{ asset('images/question-mark.png') }}" alt="Help and information"
         style="width: 20px; height: 20px; object-fit: contain; display: block;">
  </button>
</div>

@if(session('success'))
  <div style="background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 1px solid #86efac; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(22,163,74,0.06);">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(22,163,74,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#16a34a" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span style="font-size: 14px; color: #166534; font-weight: 600;">{{ session('success') }}</span>
  </div>
@endif

@if($errors->any())
  <div style="background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 1px solid #fca5a5; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(220,38,38,0.06);">
    <ul style="margin: 0; padding-left: 20px; color: #dc2626; font-size: 14px;">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<!-- Main Form -->
<div style="background: #ffffff; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02); border: 1px solid #f0f2f1; overflow: hidden;">
  <div style="padding: 22px 28px; border-bottom: 1px solid #f0f2f1; background: #fafdfb;">
      <h2 style="font-size: 17px; font-weight: 700; color: #1a2b1e; margin: 0;">Farming Requirements</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">Fill in the details to get an instant cost quotation.</p>
  </div>

    <form action="{{ route('farmer.quotations.store') }}" method="POST" style="padding: 28px;">
      @csrf

      <!-- Instant quotation indicator -->
      <div style="display: flex; gap: 8px; margin-bottom: 28px;">
        <div style="flex: 1; height: 4px; background: linear-gradient(90deg, #143d23, #22c55e); border-radius: 2px;"></div>
      </div>

      <!-- Crop Type -->
      <div style="margin-bottom: 20px;">
        <label for="crop_type" class="input-label">Crop Type</label>
        <div class="crop-selector-wrap">
          <svg class="crop-search-icon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
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
        <div class="input-hint">What crop are you planning to plant this season?</div>
      </div>

      <!-- Season — Auto-detected based on Philippine climate -->
      <div style="margin-bottom: 20px;">
        <label class="input-label">Farming Season</label>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
          <label data-inline-style="{{ $cardStyle('wet') }}">
            <input type="radio" name="season" value="wet" @checked((old('season') ?? $globalSeason) === 'wet') style="accent-color: #143d23;" required>
            <div>
              <div style="font-weight: 600; font-size: 14px; color: #1e2922;">Wet Season</div>
              <div style="font-size: 12px; color: #94a3b8;">June – October</div>
            </div>
          </label>
          <label data-inline-style="{{ $cardStyle('dry') }}">
            <input type="radio" name="season" value="dry" @checked((old('season') ?? $globalSeason) === 'dry') style="accent-color: #143d23;" required>
            <div>
              <div style="font-weight: 600; font-size: 14px; color: #1e2922;">Dry Season</div>
              <div style="font-size: 12px; color: #94a3b8;">November – May</div>
            </div>
          </label>
        </div>
        <div class="input-hint">
          <span style="display: flex; align-items: center; gap: 6px; margin-top: 6px;">
            <span data-inline-style="{{ $climateDotStyle }}"></span>
            <strong>Currently:</strong> {{ $globalSeasonLabel }} — {{ $climate['months'] }}
            @if(isset($climate['sub_season']))
              <span style="color: #64748b;">({{ $climate['sub_season'] }})</span>
            @endif
          </span>
        </div>
      </div>

      <!-- Land Area -->
      <div style="margin-bottom: 20px;">
        <label for="land_area" class="input-label">Land Area (Hectares)</label>
        <div style="display: flex; gap: 10px;">
          <input type="number" name="land_area" id="land_area" class="input-field"
                 placeholder="e.g., 1.5" step="0.01" min="0.01" max="999.99"
                 value="{{ old('land_area') }}" required>
          <div style="display: flex; align-items: center; padding: 0 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; color: #64748b; font-weight: 600; white-space: nowrap;">ha</div>
        </div>
        <div class="input-hint">Enter the total size of your farm area for this crop.</div>
      </div>

      <!-- Available Products -->
      <div style="margin-bottom: 20px;">
        <label class="input-label">Select Products <span style="color: #94a3b8; font-weight: 400;">(optional)</span></label>
        <div id="product-selector-section">
          <div id="product-prompt" class="product-prompt">
            Select a crop type and season above to browse available products.
          </div>
          <div id="product-loading" class="product-loading" style="display: none;">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="#94a3b8" stroke-width="2" fill="none" style="vertical-align: middle; animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            Loading products...
          </div>
          <div id="product-selector-container" style="display: none;">
            <div class="product-selector-wrap">
              <svg class="product-search-icon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" id="product-search" class="product-search-input"
                     placeholder="Search products by name, brand, or SKU..." autocomplete="off">
              <div id="product-dropdown" class="product-dropdown"></div>
            </div>
            <div id="selected-products" class="selected-products"></div>
          </div>
        </div>
        <div class="input-hint">      Optionally pick specific products you'd like included in the quotation. Quantities will be calculated based on your land area.</div>
      </div>

      <!-- Notes -->
      <div style="margin-bottom: 24px;">
        <label for="notes" class="input-label">Additional Notes <span style="color: #94a3b8; font-weight: 400;">(optional)</span></label>
        <textarea name="notes" id="notes" class="input-field" rows="3"
                  placeholder="Any specific requirements, preferred varieties, or special instructions...">{{ old('notes') }}</textarea>
      </div>

      <button type="submit" class="submit-btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        Generate Cost Quotation
      </button>
    </form>
  </div>
</div>

<!-- Info Modal -->
<div id="info-modal" role="dialog" aria-modal="true" aria-label="Help and information"
     style="position: fixed; inset: 0; z-index: 1000; display: none; align-items: center; justify-content: center; padding: 20px;">
  <div id="info-modal-backdrop" style="position: absolute; inset: 0; background: rgba(15, 30, 20, 0.5); backdrop-filter: blur(4px);"></div>
  <div style="position: relative; background: #ffffff; border-radius: 18px; max-width: 560px; width: 100%; max-height: 85vh; overflow-y: auto; box-shadow: 0 24px 60px rgba(0,0,0,0.2);">
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #f0f2f1; position: sticky; top: 0; background: #ffffff;">
      <h2 style="font-size: 17px; font-weight: 800; color: #1a2b1e; margin: 0;">Need Some Help?</h2>
      <button type="button" id="info-modal-close" aria-label="Close"
              style="width: 34px; height: 34px; border-radius: 50%; background: #f8fafc; border: 1px solid #eef1f4; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; color: #64748b; transition: background 0.2s;">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div style="padding: 24px; display: flex; flex-direction: column; gap: 18px;">

      <!-- How It Works -->
      <div class="info-card" style="padding: 18px;">
        <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #f0fdf4, #dcfce7); display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #bbf7d0;">
          <svg viewBox="0 0 24 24" width="18" height="18" stroke="#143d23" stroke-width="2" fill="none">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
          </svg>
        </div>
        <div>
          <div style="font-weight: 700; font-size: 14px; color: #1a2b1e; margin-bottom: 4px;">How It Works</div>
          <ol style="margin: 0; padding-left: 16px; font-size: 13px; color: #4b5c50; line-height: 1.9;">
            <li>You enter your crop type, season, and land area</li>
            <li>The system instantly computes a cost quotation using Decision Support formulas</li>
            <li>You see a full breakdown with product quantities and pricing</li>
            <li>Store owners see your quotation as demand intelligence — no pressure to buy</li>
            <li>Contact the store directly if you decide to proceed with a purchase</li>
          </ol>
        </div>
      </div>

      <!-- Supported Crops -->
      <div class="info-card" style="padding: 18px;">
        <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #fffbeb, #fef3c7); display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #fde68a;">
          <svg viewBox="0 0 24 24" width="18" height="18" stroke="#d97706" stroke-width="2" fill="none">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
          </svg>
        </div>
        <div>
          <div style="font-weight: 700; font-size: 14px; color: #1a2b1e; margin-bottom: 4px;">Supported Crops</div>
          <p style="margin: 0; font-size: 13px; color: #4b5c50; line-height: 1.5;">
            Our system supports instant cost quotations for Rice, Corn, Vegetables, Fruits, and more.
            Quotations are based on standard application rates that vary between Wet and Dry seasons.
          </p>
        </div>
      </div>

      <!-- Validity Period -->
      <div class="info-card" style="padding: 18px;">
        <div style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #eff6ff, #dbeafe); display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #bfdbfe;">
          <svg viewBox="0 0 24 24" width="18" height="18" stroke="#0284c7" stroke-width="2" fill="none">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
          </svg>
        </div>
        <div>
          <div style="font-weight: 700; font-size: 14px; color: #1a2b1e; margin-bottom: 4px;">Quotation Validity</div>
          <p style="margin: 0; font-size: 13px; color: #4b5c50; line-height: 1.5;">
            Quotations are valid for 14 days. Prices may change, so generate a new quotation if needed.
            This is for planning — not a purchase commitment.
          </p>
        </div>
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
      // Show a "type custom" option
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
    // Trigger product fetch
    cropHiddenInput.dispatchEvent(new Event('change'));
  }

  cropSearchInput.addEventListener('focus', function () {
    cropDropdown.classList.add('open');
    filterCrops(cropSearchInput.value);
  });

  cropSearchInput.addEventListener('input', function () {
    cropDropdown.classList.add('open');
    filterCrops(cropSearchInput.value);
    // Clear hidden value while typing so stale value isn't submitted
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

  // --- Product Selector (unchanged logic, updated reference) ---
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
      document.querySelector('form').appendChild(hidden);
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

  // Event listeners
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

  // Initial load if values already set (e.g. validation error)
  if (cropInput.value.trim() && getSelectedSeason()) {
    fetchProducts();
  }
})();

// --- Info Modal ---
(function () {
  const btn = document.getElementById('info-help-btn');
  const modal = document.getElementById('info-modal');
  const backdrop = document.getElementById('info-modal-backdrop');
  const closeBtn = document.getElementById('info-modal-close');
  if (!btn || !modal) return;

  function open() {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
  function close() {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }

  btn.addEventListener('click', open);
  closeBtn.addEventListener('click', close);
  backdrop.addEventListener('click', close);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && modal.style.display === 'flex') close();
  });
})();
</script>
<style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
@endsection
