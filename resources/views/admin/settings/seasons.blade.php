@extends('layouts.sidebar')

@section('title', 'Season Settings')

@section('content')
  <div style="margin:0 0 14px 0;font-size:13px;color:#64748b;">
    <a href="{{ route('admin.settings.index') }}" style="color:#4A7A54;text-decoration:none;font-weight:600;">General Settings</a>
    <span style="color:#94a3b8;"> / Season Settings</span>
  </div>

  @php
    use App\Helpers\SeasonHelper;

    // PAGASA defaults — what a fresh install uses and what Reset restores:
    // wet season June 1 – November 30, dry season December 1 – May 31.
    $defaults = [
        'wet_start'      => SeasonHelper::DEFAULT_WET_START,
        'wet_end'        => SeasonHelper::DEFAULT_WET_END,
        'active_season'  => 'auto',
    ];

    // What is stored right now (what the whole system is currently using).
    [$wetStart, $wetEnd] = SeasonHelper::wetWindow();
    $current = [
        'wet_start'     => $wetStart,
        'wet_end'       => $wetEnd,
        'active_season' => SeasonHelper::activeSeasonOverride(),
    ];
    [$dryStart, $dryEnd] = SeasonHelper::dryWindow();
    $reco = SeasonHelper::recommendsSeasonally();

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
  @endphp

  @include('partials.settings-styles')

  <div class="set-page">
    <div class="set-content">
      <div class="header-row">
        <div>
          <h1>Season Settings</h1>
          <p>Crop season windows, active season detection, and seasonal variety recommendations. Defaults follow PAGASA (wet: June–November, dry: December–May).</p>
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
                    <select name="wet_start" id="wetStartSel">
                      @foreach($months as $num => $m)
                        <option value="{{ $num }}" @selected($num === $current['wet_start'])>{{ $m }}</option>
                      @endforeach
                    </select>
                    <span class="sep">to</span>
                    <select name="wet_end" id="wetEndSel">
                      @foreach($months as $num => $m)
                        <option value="{{ $num }}" @selected($num === $current['wet_end'])>{{ $m }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="hint">Flood-tolerant rice varieties are recommended during this window.</div>
                </div>
                <div class="set-field">
                  <label>Dry Season Window</label>
                  <div class="season-dates">
                    <select name="dry_start" id="dryStartSel" disabled>
                      @foreach($months as $num => $m)
                        <option value="{{ $num }}" @selected($num === $dryStart)>{{ $m }}</option>
                      @endforeach
                    </select>
                    <span class="sep">to</span>
                    <select name="dry_end" id="dryEndSel" disabled>
                      @foreach($months as $num => $m)
                        <option value="{{ $num }}" @selected($num === $dryEnd)>{{ $m }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="hint">Derived automatically: the dry season is whatever the wet season is not, so the two can never overlap or leave a gap.</div>
                </div>
              </div>
              <div class="set-card">
                <div class="set-field">
                  <label>Active Season</label>
                  <select name="active_season" id="activeSeasonSel">
                    <option value="auto" @selected($current['active_season'] === 'auto')>Auto-detect from current date</option>
                    <option value="wet" @selected($current['active_season'] === 'wet')>Wet Season (force)</option>
                    <option value="dry" @selected($current['active_season'] === 'dry')>Dry Season (force)</option>
                  </select>
                  <div class="hint">Determines season-specific seed and fertilizer recommendations. Auto-detect follows the windows on the left; forcing one season overrides the date everywhere until you switch back.</div>
                </div>
                <div class="set-field">
                  <div class="set-row">
                    <div class="info">
                      <b>Recommend seasonal varieties automatically</b>
                      <span>Suggest flood-tolerant seeds for wet season and drought-tolerant for dry.</span>
                    </div>
                    <label class="switch"><input type="checkbox" name="season_reco" id="seasonRecoChk" @checked($reco)><span class="slider"></span></label>
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
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 3200);
      }

      var defaults = {
        wet_start: {{ $defaults['wet_start'] }},
        wet_end: {{ $defaults['wet_end'] }},
        active_season: '{{ $defaults['active_season'] }}'
      };

      // The dry season is the complement of the wet window — mirror every
      // wet change into the disabled dry selects so the admin always sees
      // what the system will actually use.
      function updateDryWindow() {
        var ws = parseInt(document.getElementById('wetStartSel').value, 10);
        var we = parseInt(document.getElementById('wetEndSel').value, 10);
        var dryStart = (we % 12) + 1;
        var dryEnd = ws === 1 ? 12 : ws - 1;
        document.getElementById('dryStartSel').value = String(dryStart);
        document.getElementById('dryEndSel').value = String(dryEnd);
      }
      document.getElementById('wetStartSel').addEventListener('change', updateDryWindow);
      document.getElementById('wetEndSel').addEventListener('change', updateDryWindow);

      function setDefaults() {
        document.getElementById('wetStartSel').value = String(defaults.wet_start);
        document.getElementById('wetEndSel').value = String(defaults.wet_end);
        document.getElementById('activeSeasonSel').value = defaults.active_season;
        document.getElementById('seasonRecoChk').checked = true;
        updateDryWindow();
      }

      document.getElementById('saveBtn').addEventListener('click', function () {
        var btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving…';
        var payload = {
          wet_start: parseInt(document.getElementById('wetStartSel').value, 10),
          wet_end: parseInt(document.getElementById('wetEndSel').value, 10),
          active_season: document.getElementById('activeSeasonSel').value,
          season_reco: document.getElementById('seasonRecoChk').checked
        };
        fetch('{{ route("admin.settings.seasons.store") }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('#csrfForm [name=_token]').value },
          body: JSON.stringify(payload)
        })
          .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
          .then(function (res) {
            if (res.ok && res.body.ok) {
              showToast('Seasons saved — wet ' + res.body.wet + ', dry ' + res.body.dry + '. Applied everywhere immediately.');
            } else {
              var first = res.body && res.body.errors ? Object.values(res.body.errors)[0] : null;
              showToast(first ? first[0] : 'Saving failed — check the values.');
            }
          })
          .catch(function () { showToast('Failed to save — check your connection.'); })
          .finally(function () {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
          });
      });

      document.getElementById('resetBtn').addEventListener('click', function () {
        setDefaults();
        showToast('Restored PAGASA defaults (wet June–November). Click Save to apply.');
      });
    })();
  </script>
@endsection
