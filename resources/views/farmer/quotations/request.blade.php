@extends('layouts.sidebar')

@section('title', 'Request Quotation')

@section('content')
@php
  $selSeason = old('season') ?? $globalSeason;
  $climate = $globalClimateInfo;
  $cardStyle = function ($season) use ($selSeason) {
      return 'display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1px solid ' . ($selSeason === $season ? '#143d23' : '#e2e8f0') . '; border-radius: 10px; cursor: pointer; transition: all 0.2s; background: ' . ($selSeason === $season ? '#f0fdf4' : '#ffffff') . ';';
  };
  $climateDotStyle = 'display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: ' . ($climate['season'] === 'wet' ? '#0284c7' : '#d97706') . ';';
@endphp
<style>
  .input-field {
    width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0;
    border-radius: 10px; font-size: 14px; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .input-field:focus {
    outline: none; border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.1);
  }
  .input-label {
    display: block; font-size: 13px; font-weight: 700;
    color: #2c3530; margin-bottom: 6px;
  }
  .input-hint {
    font-size: 12px; color: #94a3b8; margin-top: 4px;
  }
  select.input-field {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
    padding-right: 40px; cursor: pointer;
  }
  .submit-btn {
    background: #143d23; color: white; border: none;
    padding: 14px 28px; border-radius: 10px; font-size: 15px;
    font-weight: 700; cursor: pointer; transition: background 0.2s, transform 0.1s;
    display: inline-flex; align-items: center; gap: 8px;
  }
  .submit-btn:hover { background: #0f2f1a; }
  .submit-btn:active { transform: scale(0.98); }
  .info-card {
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 10px; padding: 16px; display: flex; gap: 12px;
  }
  .info-card svg { flex-shrink: 0; margin-top: 2px; }
</style>

<div class="header-panel" style="margin-bottom: 24px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">
    Request a Cost Quotation
  </h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">
    Tell us about your farming plan, and our system will generate a recommended input list and cost estimate.
  </p>
</div>

@if(session('success'))
  <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#16a34a" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    <span style="font-size: 14px; color: #166534; font-weight: 500;">{{ session('success') }}</span>
  </div>
@endif

@if($errors->any())
  <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;">
    <ul style="margin: 0; padding-left: 20px; color: #dc2626; font-size: 14px;">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">

  <!-- Main Form -->
  <div style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb;">
      <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin: 0;">Farming Requirements</h2>
      <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Fill in the details about your farm and crop plan.</p>
    </div>

    <form action="{{ route('farmer.quotations.store') }}" method="POST" style="padding: 24px;">
      @csrf

      <!-- Step indicator -->
      <div style="display: flex; gap: 8px; margin-bottom: 24px;">
        <div style="flex: 1; height: 4px; background: #143d23; border-radius: 2px;"></div>
        <div style="flex: 1; height: 4px; background: #e2e8f0; border-radius: 2px;"></div>
      </div>

      <!-- Crop Type -->
      <div style="margin-bottom: 20px;">
        <label for="crop_type" class="input-label">Crop Type</label>
        @if($cropTypes->isNotEmpty())
          <select name="crop_type" id="crop_type" class="input-field" required>
            <option value="">— Select your crop —</option>
            @foreach($cropTypes as $type)
              <option value="{{ $type }}" @selected(old('crop_type') === $type)>{{ $type }}</option>
            @endforeach
          </select>
        @else
          <input type="text" name="crop_type" id="crop_type" class="input-field"
                 placeholder="e.g., Rice, Corn, Vegetables" value="{{ old('crop_type') }}" required>
        @endif
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

      <!-- Notes -->
      <div style="margin-bottom: 24px;">
        <label for="notes" class="input-label">Additional Notes <span style="color: #94a3b8; font-weight: 400;">(optional)</span></label>
        <textarea name="notes" id="notes" class="input-field" rows="3"
                  placeholder="Any specific requirements, preferred varieties, or special instructions...">{{ old('notes') }}</textarea>
      </div>

      <button type="submit" class="submit-btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        Submit Quotation Request
      </button>
    </form>
  </div>

  <!-- Info Panel -->
  <div>
    <div class="info-card" style="margin-bottom: 16px;">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="#143d23" stroke-width="2" fill="none" style="flex-shrink: 0; margin-top: 2px;">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
      </svg>
      <div>
        <div style="font-weight: 700; font-size: 14px; color: #1e2922; margin-bottom: 4px;">How It Works</div>
        <ol style="margin: 0; padding-left: 16px; font-size: 13px; color: #475569; line-height: 1.8;">
          <li>You submit your crop type, season, and land area</li>
          <li>A store owner reviews your request and applies our Decision Support formulas</li>
          <li>The system computes recommended input quantities based on standard rates per hectare</li>
          <li>An itemized cost quotation is generated with current pricing</li>
          <li>You receive the estimate and can accept or reject</li>
        </ol>
      </div>
    </div>

    <div class="info-card" style="margin-bottom: 16px;">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="#d97706" stroke-width="2" fill="none" style="flex-shrink: 0; margin-top: 2px;">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
      </svg>
      <div>
        <div style="font-weight: 700; font-size: 14px; color: #1e2922; margin-bottom: 4px;">Supported Crops</div>
        <p style="margin: 0; font-size: 13px; color: #475569;">
          Our system supports seasonal recommendations for common crops like Rice, Corn, and Vegetables.
          The Rule-Based Algorithm uses standard application rates that vary between Wet and Dry seasons to
          ensure accurate input recommendations.
        </p>
      </div>
    </div>

    <div class="info-card">
      <svg viewBox="0 0 24 24" width="20" height="20" stroke="#0284c7" stroke-width="2" fill="none" style="flex-shrink: 0; margin-top: 2px;">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      <div>
        <div style="font-weight: 700; font-size: 14px; color: #1e2922; margin-bottom: 4px;">Validity Period</div>
        <p style="margin: 0; font-size: 13px; color: #475569;">
          Once the store owner dispatches your quotation, it will have a validity period (typically 3-7 days).
          If it expires, you can generate a new quotation to reflect current prices and stock availability.
        </p>
      </div>
    </div>
  </div>

</div>
@endsection

