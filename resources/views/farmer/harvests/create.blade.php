@extends('layouts.sidebar')

@section('title', 'Submit Harvest Report')

@section('content')
<style>
  .harvest-submit-page { max-width: 880px; }
  /* Title block: no card, no background — the heading sits on the page
     background exactly like the Harvest Reports list and the other pages. */
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
  /* Grey variant: the "no plantings yet" reassurance, which must read as
     information rather than as a green success banner. */
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
      Record your actual harvest output, expenses, and planted area — starting from a crop batch, or entirely by hand for a past harvest. Reports sync straight to the Aquilala administrator.
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

  <div class="sync-note">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#4A7A54" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
    <div>
      <b>Start from a planting — or file it by hand</b>
      <p>Pick a confirmed crop batch and the crop, season and land area fill themselves in. Leave it on <strong>None</strong> to type everything yourself — for a past harvest, or a crop you never registered in the system.</p>
    </div>
  </div>

  @php
      // Land on the form when there is nothing to choose from, or when a
      // submit just bounced back with errors / old input still on screen.
      $openForm = $errors->any() || old('crop_batch_id') || ($batches ?? collect())->isEmpty();
  @endphp

  <div class="tabs" role="tablist" data-default-tab="{{ $openForm ? 'form' : 'plantings' }}">
    <button type="button" class="tab" data-tab="plantings" role="tab" id="tab-plantings" aria-controls="panel-plantings" aria-selected="false">
      <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
      From a planting
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
          <b>No crop plantings on file yet — that's fine</b>
          <p>You can still submit a harvest report from the <strong>Report form</strong> tab, including one from a past season. Any planting you confirm in <a href="{{ route('farmer.crops.index') }}">My Crops</a> will appear here.</p>
          <button type="button" class="btn-switch" data-goto="form">
            <svg viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            Fill the form myself
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
              File harvest report
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
            <label>Crop Batch <span style="font-weight: 500; color: #94a3b8;">(optional)</span></label>
            <select name="crop_batch_id" id="cropBatchSelect">
              <option value="" {{ !old('crop_batch_id') ? 'selected' : '' }}>None — I'll enter the harvest details myself</option>
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
            <p style="margin: 6px 0 0; font-size: 11.5px; color: #94a3b8;">
              Picking a batch fills the crop, season and land area below — yield, expenses and date stay yours to enter. Choose <b>None</b> to record a harvest with no planting linked: a past harvest, or a crop you never registered here.
            </p>
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
    </div>
  </div>
  </div><!-- /#panel-form -->

  <script>
    (function () {
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

      // Land where there is something to do: the form when there was nothing
      // to choose from (or a submit just bounced back), otherwise the chooser.
      activateTab((tablist && tablist.getAttribute('data-default-tab')) || 'plantings');

      // ── Choosing a planting → jump to the form with it selected ─────────
      var batchSelect = document.getElementById('cropBatchSelect');
      var cropField = document.getElementById('cropTypeField');
      var seasonField = document.getElementById('seasonField');
      var landField = document.getElementById('landAreaField');

      // Batch → form. With "None" chosen nothing is touched: the farmer's own
      // entries (or an empty form) stand, which is what makes the standalone
      // / past-harvest path work.
      function applyBatch() {
        if (!batchSelect) return;
        var opt = batchSelect.options[batchSelect.selectedIndex];
        if (!opt || !opt.value) return;
        var crop = opt.getAttribute('data-crop');
        if (crop && cropField) {
          var matched = false;
          for (var i = 0; i < cropField.options.length; i++) {
            if (cropField.options[i].value === crop) { cropField.selectedIndex = i; matched = true; break; }
          }
          // A batch can name a crop the picker doesn't list (a renamed or
          // placeholder registry entry). Add it rather than leave the required
          // field blank and block the submit.
          if (!matched) {
            var extra = document.createElement('option');
            extra.value = crop;
            extra.textContent = crop;
            cropField.appendChild(extra);
            cropField.value = crop;
          }
        }
        var season = opt.getAttribute('data-season');
        if (season && seasonField) seasonField.value = season;
        var area = opt.getAttribute('data-area');
        if (area && landField) landField.value = area;
      }

      if (batchSelect) {
        batchSelect.addEventListener('change', applyBatch);
        if (batchSelect.value) applyBatch();
      }

      Array.prototype.slice.call(document.querySelectorAll('[data-batch-id]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = btn.getAttribute('data-batch-id');
          if (batchSelect) {
            batchSelect.value = id;
            batchSelect.dispatchEvent(new Event('change'));
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