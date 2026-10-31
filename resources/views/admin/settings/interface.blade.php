@extends('layouts.sidebar')

@section('title', 'Appearance')

@section('content')
  <div style="margin:0 0 14px 0;font-size:13px;color:#64748b;">
    <a href="{{ route('admin.settings.index') }}" style="color:#4A7A54;text-decoration:none;font-weight:600;">General Settings</a>
    <span style="color:#94a3b8;"> / Appearance</span>
  </div>

  @php
    // Live interface settings (persisted via admin.settings.interface.update)
    $curUnit = \App\Helpers\SettingsHelper::yieldUnit();
    $curDec  = \App\Helpers\SettingsHelper::yieldDecimals();
    $curSep  = \App\Helpers\SettingsHelper::yieldSeparator();
    $curZero = \App\Helpers\SettingsHelper::yieldZeroDash();
  @endphp

  @include('partials.settings-styles')

  <div class="set-page">
    <div class="set-content">
      <div class="header-row">
        <div>
          <h1>Appearance</h1>
          <p>Appearance, sub-page navigation, and how numbers are shown across the dashboard.</p>
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
            <div class="set-section-title">Side Menu Appearance</div>
            <p style="font-size:12px;color:#64748b;margin:-8px 0 14px 0;max-width:760px;">
              Choose a color for your navigation sidebar.
              and saves it to your account for every page.
            </p>
            @include('partials.sidebar-theme-picker')
          </div>

          <div class="set-section">
            <div class="set-section-title">Display Theme</div>
            <p style="font-size:12px;color:#64748b;margin:-8px 0 14px 0;max-width:760px;">
              Switch the whole system between light and dark. Your choice is
              saved to your account and applies on every page you open.
            </p>
            @include('partials.theme-mode-picker')
          </div>

          <div class="set-section">
            <div class="set-section-title">Harvest Report Summary Display</div>
            <p style="font-size:12px;color:#64748b;margin:-8px 0 14px 0;max-width:760px;">
              Controls how actual yields and expenses are presented across the dashboard.
              More interface options will appear here over time.
            </p>
            <div class="set-grid">
              <div class="set-card" style="grid-column:1 / -1;">
                <div class="set-field">
                  <label>Yield quantity unit</label>
                  <div class="unit-options">
                    <label class="unit-opt">
                      <input type="radio" name="yield_unit" value="mt" @checked($curUnit === 'mt')>
                      <span class="uo-box">
                        <span class="uo-name">Metric Tons</span>
                        <span class="uo-desc">Standard Philippine farming unit. 1 MT = 1,000 kg.</span>
                        <span class="uo-tag">e.g. 2.4 MT</span>
                      </span>
                    </label>
                    <label class="unit-opt">
                      <input type="radio" name="yield_unit" value="kg" @checked($curUnit === 'kg')>
                      <span class="uo-box">
                        <span class="uo-name">Kilograms</span>
                        <span class="uo-desc">Fine-grained detail, ideal for smaller harvests.</span>
                        <span class="uo-tag">e.g. 2,400 kg</span>
                      </span>
                    </label>
                    <label class="unit-opt">
                      <input type="radio" name="yield_unit" value="cavan" @checked($curUnit === 'cavan')>
                      <span class="uo-box">
                        <span class="uo-name">Cavan / Bag</span>
                        <span class="uo-desc">Everyday trading unit farmers use. 1 cavan ≈ 50 kg.</span>
                        <span class="uo-tag">e.g. 48 cavan</span>
                      </span>
                    </label>
                    <label class="unit-opt">
                      <input type="radio" name="yield_unit" value="auto" @checked($curUnit === 'auto')>
                      <span class="uo-box">
                        <span class="uo-name">Automatic</span>
                        <span class="uo-desc">Show small yields in kg and large yields in MT.</span>
                        <span class="uo-tag">switches below 1 MT</span>
                      </span>
                    </label>
                  </div>
                </div>
              </div>

              <div class="set-card">
                <div class="set-field">
                  <label>Decimal places</label>
                  <input type="number" name="yield_decimals" value="{{ $curDec }}" min="0" max="2">
                  <div class="hint">Digits after the decimal point (0–2).</div>
                </div>
                <div class="set-field">
                  <label>Thousands separator</label>
                  <select name="yield_number_separator">
                    <option value="comma" @selected($curSep === ',')>Comma — e.g. 2,400</option>
                    <option value="period" @selected($curSep === '.')>Period — e.g. 2.400</option>
                    <option value="none" @selected($curSep === '')>None — e.g. 2400</option>
                  </select>
                  <div class="hint">How large numbers are grouped. e.g. <b id="sepExample">2,400</b></div>
                </div>
              </div>

              <div class="set-card">
                <div class="set-field">
                  <div class="set-row">
                    <div class="info">
                      <b>Dash when there are no reports</b>
                      <span>Show — on the Total Yield row instead of 0 when no harvest reports exist.</span>
                    </div>
                    <label class="switch"><input type="checkbox" name="yield_zero_dash" @checked($curZero)><span class="slider"></span></label>
                  </div>
                </div>
                <div class="iface-preview">
                  <div class="pv-label">Live preview — Harvest Report Summary</div>
                  <div class="pv-line"><span class="pv-name">Total Yield</span><span class="pv-val" id="pvTotal">2.4 MT</span></div>
                  <div class="pv-line"><span class="pv-name">Rice</span><span class="pv-val" id="pvRice">1.6 MT</span></div>
                  <div class="pv-line"><span class="pv-name">Sweet Corn</span><span class="pv-val" id="pvCorn">0.85 MT</span></div>
                  <div class="pv-line" style="opacity:0.7;"><span class="pv-name">No reports yet</span><span class="pv-val" id="pvZero">—</span></div>
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
      function csrfToken() {
        var el = document.getElementById('csrfForm').querySelector('input[name=_token]');
        return el ? el.value : '';
      }

      function fmtNum(n, dec, sepKey) {
        var sep = sepKey === 'none' ? '' : (sepKey === 'period' ? '.' : ',');
        var decPoint = sep === '.' ? ',' : '.';
        var f = Number(n).toFixed(dec);
        var parts = f.split('.');
        return parts[1]
          ? parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep) + decPoint + parts[1]
          : parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep);
      }

      function fmtYield(mt, unit, dec, sepKey) {
        if (mt === 0) return '—';
        if (unit === 'auto') unit = mt < 1 ? 'kg' : 'mt';
        var num = unit === 'kg' ? mt * 1000 : (unit === 'cavan' ? mt * 20 : mt);
        var suffix = unit === 'kg' ? 'kg' : (unit === 'cavan' ? 'cavan' : 'MT');
        return fmtNum(num, dec, sepKey) + ' ' + suffix;
      }

      function updatePreview() {
        var unit = document.querySelector('input[name=yield_unit]:checked');
        if (!unit) return;
        var dec = parseInt(document.querySelector('input[name=yield_decimals]').value, 10) || 0;
        var sepKey = document.querySelector('select[name=yield_number_separator]').value;
        var zeroDash = document.querySelector('input[name=yield_zero_dash]').checked;
        document.getElementById('pvTotal').textContent = fmtYield(2.37, unit.value, dec, sepKey);
        document.getElementById('pvRice').textContent = fmtYield(1.6, unit.value, dec, sepKey);
        document.getElementById('pvCorn').textContent = fmtYield(0.85, unit.value, dec, sepKey);
        document.getElementById('pvZero').textContent = zeroDash ? '—' : fmtYield(0, unit.value, dec, sepKey);
        document.getElementById('sepExample').textContent = fmtNum(2400, 0, sepKey);
      }

      var toast = document.getElementById('setToast');
      var toastMsg = document.getElementById('setToastMsg');
      var toastTimer = null;
      function showToast(msg) {
        toastMsg.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2600);
      }

      document.getElementById('saveBtn').addEventListener('click', function () {
        var btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving…';
        var payload = {
          yield_unit: document.querySelector('input[name=yield_unit]:checked').value,
          yield_decimals: document.querySelector('input[name=yield_decimals]').value,
          yield_number_separator: document.querySelector('select[name=yield_number_separator]').value,
          yield_zero_dash: document.querySelector('input[name=yield_zero_dash]').checked
        };
        fetch('{{ route("admin.settings.interface.update") }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
          body: JSON.stringify(payload)
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.ok) showToast('Appearance settings saved. They apply everywhere immediately.');
            else showToast('Saved settings failed to apply.');
          })
          .catch(function () { showToast('Failed to save — check your connection.'); })
          .finally(function () {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
          });
      });

      document.getElementById('resetBtn').addEventListener('click', function () {
        document.querySelectorAll('[name]').forEach(function (el) {
          if (el.type === 'radio') el.checked = (el.value === 'mt');
          else if (el.type === 'checkbox') el.checked = false;
        });
        document.querySelector('input[name=yield_decimals]').value = '1';
        document.querySelector('select[name=yield_number_separator]').value = 'comma';
        updatePreview();
        showToast('Settings restored to defaults. Click Save to apply.');
      });

      document.querySelectorAll('input[name=yield_unit]').forEach(function (r) {
        r.addEventListener('change', updatePreview);
      });
      document.querySelector('input[name=yield_decimals]').addEventListener('input', updatePreview);
      document.querySelector('select[name=yield_number_separator]').addEventListener('change', updatePreview);
      document.querySelector('input[name=yield_zero_dash]').addEventListener('change', updatePreview);
      updatePreview();
    })();
  </script>
@endsection