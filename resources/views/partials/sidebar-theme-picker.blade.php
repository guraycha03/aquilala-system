@php
    $stpThemes = config('sidebar.themes');
    $stpSaved = (string) auth()->user()->sidebar_theme;
    $stpCurrent = array_key_exists($stpSaved, $stpThemes) ? $stpSaved : config('sidebar.default');
@endphp

<style>
  /* Sidebar theme picker — shared by every role's settings page */
  .stp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
  .stp-swatch {
    position: relative; border: 2px solid #e5ebe7; border-radius: 14px; padding: 10px;
    background: #ffffff; cursor: pointer; text-align: left; font-family: inherit;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
  }
  .stp-swatch:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(20,61,35,0.10); }
  .stp-swatch.active { border-color: #8fbf4d; box-shadow: 0 0 0 3px rgba(143,191,77,0.22); }
  .stp-chip {
    height: 56px; border-radius: 10px; display: flex; align-items: flex-start;
    justify-content: flex-start; padding: 8px; margin-bottom: 10px;
  }
  .stp-mini { width: 16px; height: 100%; border-radius: 4px; }
  .stp-name { font-size: 13px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px; }
  .stp-desc { font-size: 11px; color: #94a3b8; line-height: 1.4; margin-top: 2px; }
  .stp-check {
    margin-left: auto; width: 18px; height: 18px; border-radius: 50%; background: #8fbf4d;
    color: #ffffff; display: none; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .stp-swatch.active .stp-check { display: inline-flex; }
  .stp-swatch[aria-disabled="true"] {
    opacity: 0.45; cursor: not-allowed; transform: none !important; box-shadow: none !important;
  }
  .stp-swatch[aria-disabled="true"]:hover { transform: none; box-shadow: none; }
  .stp-hint { font-size: 11px; color: #94a3b8; margin: 10px 2px 0; }
  .stp-darkonly {
    display: none; margin: 10px 2px 0; font-size: 11px; font-weight: 600; color: #94a3b8;
  }
  html[data-theme="dark"] .stp-darkonly { display: block; }
  .stp-msg {
    margin-top: 12px; font-size: 12px; font-weight: 600; color: #15803d;
    opacity: 0; transform: translateY(2px); transition: opacity .2s ease, transform .2s ease;
  }
  .stp-msg.show { opacity: 1; transform: none; }
  .stp-msg.err { color: #dc2626; }
</style>

<div class="stp-grid">
  @foreach ($stpThemes as $key => $t)
    <button type="button" class="stp-swatch {{ $stpCurrent === $key ? 'active' : '' }}"
            data-stp-theme="{{ $key }}" aria-pressed="{{ $stpCurrent === $key ? 'true' : 'false' }}">
      <span class="stp-chip" style="background:{{ $t['chip']['bg'] }}">
        <span class="stp-mini" style="background:{{ $t['chip']['fg'] }}"></span>
      </span>
      <span class="stp-name">
        {{ $t['name'] }}
        <span class="stp-check">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </span>
      </span>
      <span class="stp-desc">{{ $t['desc'] }}</span>
    </button>
  @endforeach
</div>
<p class="stp-hint">Click a color to apply instantly — your choice is saved to your account automatically.</p>
<p class="stp-darkonly">In dark mode the side menu always uses the default color. Switch to Light mode to pick a color.</p>
<p class="stp-msg" id="stpMsg" role="status"></p>

<script>
  (function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.content : '';
    var swatches = Array.prototype.slice.call(document.querySelectorAll('.stp-swatch'));
    var msg = document.getElementById('stpMsg');
    var msgTimer = null;
    var pickerEnabled = true;

    function isDarkMode() {
      return document.documentElement && document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function applyPickerAvailability() {
      pickerEnabled = !isDarkMode();
      swatches.forEach(function (s) {
        s.setAttribute('aria-disabled', pickerEnabled ? 'false' : 'true');
      });
    }

    applyPickerAvailability();
    if ('MutationObserver' in window && document.documentElement) {
      new MutationObserver(applyPickerAvailability).observe(document.documentElement, {
        attributes: true, attributeFilter: ['data-theme']
      });
    }

    function flash(text, isError) {
      if (!msg) return;
      msg.textContent = text;
      msg.classList.toggle('err', !!isError);
      msg.classList.add('show');
      clearTimeout(msgTimer);
      msgTimer = setTimeout(function () { msg.classList.remove('show'); }, 2200);
    }

    function markActive(theme) {
      swatches.forEach(function (s) {
        var on = s.getAttribute('data-stp-theme') === theme;
        s.classList.toggle('active', on);
        s.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
    }

    swatches.forEach(function (s) {
      s.addEventListener('click', function () {
        if (!pickerEnabled) {
          flash('Side menu colors are locked in dark mode — switch to Light first.', true);
          return;
        }
        var theme = s.getAttribute('data-stp-theme');
        if (document.body) document.body.setAttribute('data-sidebar-theme', theme);
        markActive(theme);

        fetch('{{ route('settings.ui') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ theme: theme })
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.ok) flash('Theme saved.');
            else flash('Could not save the theme.', true);
          })
          .catch(function () { flash('Could not save the theme.', true); });
      });
    });
  })();
</script>