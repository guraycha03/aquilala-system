@extends('layouts.sidebar')

@section('title', 'Submit Harvest Report')

@section('content')
<style>
  .harvest-submit-page { max-width: 880px; }
  .header-panel {
    background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px;
    padding: 24px 26px; margin-bottom: 22px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
  }
  .sync-note {
    display: flex; align-items: flex-start; gap: 11px;
    background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    border: 1px solid #bbf7d0; border-radius: 12px; padding: 13px 16px;
    margin-bottom: 22px;
  }
  .sync-note svg { flex-shrink: 0; margin-top: 1px; }
  .sync-note b { font-size: 13px; color: #166534; }
  .sync-note p { margin: 2px 0 0 0; font-size: 12.5px; color: #3f5144; line-height: 1.55; }

  .record-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
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
    gap: 14px;
    padding-top: 18px;
  }
  @media (max-width: 820px) { .form-grid { grid-template-columns: 1fr; } }
  .form-field label {
    display: block; font-size: 12px; font-weight: 600;
    color: #4b5c50; margin-bottom: 6px;
  }
  .form-field input, .form-field select {
    width: 100%; height: 42px; padding: 0 12px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 13px; color: #1e293b; background: #fff;
    font-family: inherit; outline: none; transition: all 0.2s ease;
  }
  .form-field input:focus, .form-field select:focus {
    border-color: #4A7A54; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.08);
  }
  .form-errors { padding: 12px 18px; background: #fef2f2; border-bottom: 1px solid #fecaca; }
  .form-errors ul { margin: 0; padding-left: 16px; color: #b91c1c; font-size: 13px; }

  .btn-save {
    appearance: none; height: 44px; padding: 0 26px; border: none; border-radius: 10px;
    background: linear-gradient(135deg, #143d23 0%, #1a5230 100%); color: #fff;
    font-size: 13px; font-weight: 700; cursor: pointer;
    box-shadow: 0 2px 8px rgba(20,61,35,0.25);
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
      Record your actual harvest output, expenses, and planted area. Reports sync straight to the Aquilala administrator.
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
        <b style="font-size: 13px; color: #b91c1c;">Could not save your harvest report:</b>
        <ul>
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  {{-- <div class="sync-note">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#4A7A54" stroke-width="2" fill="none"><rect x="3" y="12" width="4" height="9"/><rect x="10" y="6" width="4" height="15"/><rect x="17" y="2" width="4" height="19"/></svg>
    <div>
      <b>Synced with the administrator</b>
      <p>Harvest data is shared only between you and the admin. Once submitted, it appears instantly on the admin's Harvest Reports page.</p>
    </div>
  </div> --}}

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
      @if(($batches ?? collect())->isEmpty())
        <div style="padding: 34px 6px 10px; text-align: center;">
          <div style="width: 46px; height: 46px; border-radius: 14px; background: #edf1e9; color: #648268; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
            <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.8" fill="none"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
          <h3 style="margin: 0 0 6px; font-size: 16px; color: #1e2922;">No crop batches ready to report</h3>
          <p style="margin: 0 auto 16px; font-size: 13.5px; color: #64748b; max-width: 460px; line-height: 1.6;">
            Harvest reports start from a crop batch. Confirm a planting in <b>My Crops</b> (association admins approve new plantings), or clear an existing report, and it will appear here.
          </p>
          <a href="{{ route('farmer.crops.index') }}" class="btn-history">
            <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
            Go to My Crops
          </a>
        </div>
      @else
      <form method="POST" action="{{ route('farmer.harvests.store') }}">
        @csrf
        <div class="form-grid" style="grid-template-columns: 1fr;">
          <div class="form-field">
            <label>Crop Batch <span style="color:#dc2626;">*</span></label>
            <select name="crop_batch_id" id="cropBatchSelect" required>
              <option value="">Select a confirmed crop batch…</option>
              @foreach($batches as $batch)
                <option value="{{ $batch->id }}"
                        {{ old('crop_batch_id') == $batch->id ? 'selected' : '' }}
                        data-crop="{{ $batch->crop?->name }}"
                        data-season="{{ $batch->season }}"
                        data-area="{{ $batch->land_area }}">
                  {{ $batch->crop?->name ?? 'Unknown crop' }} — {{ $batch->batch_name ?? 'batch #' . $batch->id }}
                  ({{ rtrim(rtrim(number_format((float) $batch->land_area, 2, '.', ''), '0'), '.') }} {{ $batch->land_area_unit === 'ha' ? 'ha' : $batch->land_area_unit }}, {{ ucfirst($batch->season) }} season)
                </option>
              @endforeach
            </select>
            <p style="margin: 6px 0 0; font-size: 11.5px; color: #94a3b8;">Picking a batch fills the crop, season and land area below. Yield, expenses and date stay yours to enter.</p>
          </div>
        </div>
        <div class="form-grid">
          <div class="form-field">
            <label>Crop Type</label>
            <select name="crop_type" id="cropTypeField" required>
              <option value="">Select crop…</option>
              @foreach($cropTypes as $cropName)
                <option value="{{ $cropName }}" {{ old('crop_type') === $cropName ? 'selected' : '' }}>{{ $cropName }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-field">
            <label>Season</label>
            <select name="season" id="seasonField" required>
              <option value="dry" {{ old('season') === 'dry' ? 'selected' : '' }}>Dry Season</option>
              <option value="wet" {{ old('season') === 'wet' ? 'selected' : '' }}>Wet Season</option>
            </select>
          </div>
          <div class="form-field">
            <label>Harvest Date</label>
            <input type="date" name="harvest_date" value="{{ old('harvest_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
          </div>
          <div class="form-field">
            <label>Land Area (hectares)</label>
            <input type="number" name="land_area" id="landAreaField" step="0.01" min="0.01" max="999.99" value="{{ old('land_area') }}" placeholder="e.g. 1.50" required>
          </div>
          <div class="form-field">
            <label>Actual Yield (metric tons)</label>
            <input type="number" name="actual_yield_metric_tons" step="0.01" min="0" value="{{ old('actual_yield_metric_tons') }}" placeholder="e.g. 4.20" required>
          </div>
          <div class="form-field">
            <label>Actual Total Expenses (₱)</label>
            <input type="number" name="actual_total_expenses" step="0.01" min="0" value="{{ old('actual_total_expenses') }}" placeholder="e.g. 68500" required>
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
      @endif
    </div>
  </div>

  <script>
    (function () {
      var batchSelect = document.getElementById('cropBatchSelect');
      if (!batchSelect) return;

      var cropField = document.getElementById('cropTypeField');
      var seasonField = document.getElementById('seasonField');
      var landField = document.getElementById('landAreaField');

      function applyBatch() {
        var opt = batchSelect.options[batchSelect.selectedIndex];
        if (!opt || !opt.value) return;
        var crop = opt.getAttribute('data-crop');
        if (crop && cropField) {
          for (var i = 0; i < cropField.options.length; i++) {
            if (cropField.options[i].value === crop) { cropField.selectedIndex = i; break; }
          }
        }
        var season = opt.getAttribute('data-season');
        if (season && seasonField) seasonField.value = season;
        var area = opt.getAttribute('data-area');
        if (area && landField) landField.value = area;
      }

      batchSelect.addEventListener('change', applyBatch);
      if (batchSelect.value) applyBatch();
    })();
  </script>

</div>
@endsection