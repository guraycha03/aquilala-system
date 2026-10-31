@extends('layouts.sidebar')

@section('title', 'Season Settings')

@section('content')
  <div style="margin:0 0 14px 0;font-size:13px;color:#64748b;">
    <a href="{{ route('admin.settings.index') }}" style="color:#4A7A54;text-decoration:none;font-weight:600;">General Settings</a>
    <span style="color:#94a3b8;"> / Season Settings</span>
  </div>

  @php
    // SAMPLE defaults — persist to a settings table later.
    $defaults = [
        'wet_start'      => 'June',
        'wet_end'        => 'January',
        'dry_start'      => 'February',
        'dry_end'        => 'May',
        'active_season'  => 'wet',
    ];
    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  @endphp

  @include('partials.settings-styles')

  <div class="set-page">
    <div class="set-content">
      <div class="header-row">
        <div>
          <h1>Season Settings</h1>
          <p>Crop season windows, active season detection, and seasonal variety recommendations.</p>
        </div>
        <div class="header-actions">
          <button id="resetBtn" class="btn-set ghost">
            <svg viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
            Reset
          </button>
          <button id="saveBtn" class="btn-set">
            <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
        </div>
      </div>


      <div class="set-panel">
        <div class="set-body">

          <div class="set-section">
            <div class="set-section-title">Cropping Seasons</div>
            <div class="set-grid">
              <div class="set-card">
                <div class="set-field">
                  <label>Wet Season Window</label>
                  <div class="season-dates">
                    <select name="wet_start">
                      @foreach($months as $m)
                        <option value="{{ $m }}" @selected($m === $defaults['wet_start'])>{{ $m }}</option>
                      @endforeach
                    </select>
                    <span class="sep">to</span>
                    <select name="wet_end">
                      @foreach($months as $m)
                        <option value="{{ $m }}" @selected($m === $defaults['wet_end'])>{{ $m }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="hint">Flood-tolerant rice varieties are recommended during this window.</div>
                </div>
                <div class="set-field">
                  <label>Dry Season Window</label>
                  <div class="season-dates">
                    <select name="dry_start">
                      @foreach($months as $m)
                        <option value="{{ $m }}" @selected($m === $defaults['dry_start'])>{{ $m }}</option>
                      @endforeach
                    </select>
                    <span class="sep">to</span>
                    <select name="dry_end">
                      @foreach($months as $m)
                        <option value="{{ $m }}" @selected($m === $defaults['dry_end'])>{{ $m }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
              </div>
              <div class="set-card">
                <div class="set-field">
                  <label>Active Season</label>
                  <select name="active_season">
                    <option value="auto">Auto-detect from current date</option>
                    <option value="wet" @selected($defaults['active_season'] === 'wet')>Wet Season</option>
                    <option value="dry" @selected($defaults['active_season'] === 'dry')>Dry Season</option>
                  </select>
                  <div class="hint">Determines season-specific seed and fertilizer recommendations.</div>
                </div>
                <div class="set-field">
                  <div class="set-row">
                    <div class="info">
                      <b>Recommend seasonal varieties automatically</b>
                      <span>Suggest flood-tolerant seeds for wet season and drought-tolerant for dry.</span>
                    </div>
                    <label class="switch"><input type="checkbox" name="season_reco" checked><span class="slider"></span></label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          </div>
      </div>
    </div>
  </div>

  <div class="set-toast" id="setToast">
    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="setToastMsg">Settings saved successfully.</span>
  </div>

  <form id="csrfForm" style="display:none;">@csrf</form>

  <script>
    (function () {
      var toast = document.getElementById('setToast');
      var toastMsg = document.getElementById('setToastMsg');
      var toastTimer = null;
      function showToast(msg) {
        toastMsg.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2600);
      }

      function setDefaults() {
        document.querySelectorAll('[name]').forEach(function (el) {
          var map = {
            wet_start: '{{ $defaults["wet_start"] }}',
            wet_end: '{{ $defaults["wet_end"] }}',
            dry_start: '{{ $defaults["dry_start"] }}',
            dry_end: '{{ $defaults["dry_end"] }}',
            active_season: '{{ $defaults["active_season"] }}'
          };
          if (el.type === 'checkbox') {
            el.checked = el.name === 'season_reco';
          } else if (el.tagName === 'SELECT') {
            var sv = map[el.name];
            if (sv) el.value = sv;
          }
        });
      }

      document.getElementById('saveBtn').addEventListener('click', function () {
        var btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving…';
        setTimeout(function () {
          btn.disabled = false;
          btn.textContent = 'Save Changes';
          showToast('Seasonal settings saved.');
        }, 600);
      });

      document.getElementById('resetBtn').addEventListener('click', function () {
        setDefaults();
        showToast('Settings restored to defaults. Click Save to apply.');
      });
    })();
  </script>
@endsection