@php
    $tmpSaved = (string) optional(auth()->user())->theme_mode;
    $tmpCurrent = in_array($tmpSaved, ['light', 'dark'], true) ? $tmpSaved : 'light';
@endphp

<style>
  /* Display theme picker — shared by every role's settings page */
  .tmp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
  .tmp-card {
    position: relative; border: 2px solid #e5ebe7; border-radius: 14px; padding: 10px;
    background: #ffffff; cursor: pointer; text-align: left; font-family: inherit;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
  }
  .tmp-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(20,61,35,0.10); }
  .tmp-card.active { border-color: #8fbf4d; box-shadow: 0 0 0 3px rgba(143,191,77,0.22); }
  .tmp-preview {
    height: 56px; border-radius: 10px; margin-bottom: 10px; padding: 10px;
    display: flex; flex-direction: column; gap: 5px; justify-content: center;
    border: 1px solid rgba(15,23,42,.08);
  }
  .tmp-preview .tmp-line { height: 6px; border-radius: 3px; width: 70%; }
  .tmp-preview .tmp-line.short { width: 45%; }
  .tmp-prev-light { background: #f6f8f7; }
  .tmp-prev-light .tmp-line { background: #94a3b8; }
  .tmp-prev-light .tmp-line:first-child { background: #334155; }
  .tmp-prev-dark { background: #0b1220; border-color: #334155; }
  .tmp-prev-dark .tmp-line { background: #4b5563; }
  .tmp-prev-dark .tmp-line:first-child { background: #d1fae5; }
  .tmp-name { font-size: 13px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px; }
  .tmp-desc { font-size: 11px; color: #94a3b8; line-height: 1.4; margin-top: 2px; }
  .tmp-check {
    margin-left: auto; width: 18px; height: 18px; border-radius: 50%; background: #8fbf4d;
    color: #ffffff; display: none; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .tmp-card.active .tmp-check { display: inline-flex; }
  .tmp-hint { font-size: 11px; color: #94a3b8; margin: 10px 2px 0; }
  .tmp-msg {
    margin-top: 12px; font-size: 12px; font-weight: 600; color: #15803d;
    opacity: 0; transform: translateY(2px); transition: opacity .2s ease, transform .2s ease;
  }
  .tmp-msg.show { opacity: 1; transform: none; }
  .tmp-msg.err { color: #dc2626; }
</style>

<div class="tmp-grid">
  <button type="button" class="tmp-card {{ $tmpCurrent === 'light' ? 'active' : '' }}"
          data-tmp-mode="light" aria-pressed="{{ $tmpCurrent === 'light' ? 'true' : 'false' }}">
    <span class="tmp-preview tmp-prev-light">
      <span class="tmp-line"></span>
      <span class="tmp-line short"></span>
    </span>
    <span class="tmp-name">
      Light
      <span class="tmp-check">
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </span>
    </span>
    <span class="tmp-desc">Bright white pages — the classic look.</span>
  </button>

  <button type="button" class="tmp-card {{ $tmpCurrent === 'dark' ? 'active' : '' }}"
          data-tmp-mode="dark" aria-pressed="{{ $tmpCurrent === 'dark' ? 'true' : 'false' }}">
    <span class="tmp-preview tmp-prev-dark">
      <span class="tmp-line"></span>
      <span class="tmp-line short"></span>
    </span>
    <span class="tmp-name">
      Dark
      <span class="tmp-check">
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </span>
    </span>
    <span class="tmp-desc">Dimmed pages — easier on the eyes at night.</span>
  </button>
</div>
<p class="tmp-hint">Click a mode to apply instantly — your choice is saved to your account and stays on every page.</p>
<p class="tmp-msg" id="tmpMsg" role="status"></p>

<script>
  (function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.content : '';
    var cards = Array.prototype.slice.call(document.querySelectorAll('.tmp-card'));
    var msg = document.getElementById('tmpMsg');
    var msgTimer = null;

    function flash(text, isError) {
      if (!msg) return;
      msg.textContent = text;
      msg.classList.toggle('err', !!isError);
      msg.classList.add('show');
      clearTimeout(msgTimer);
      msgTimer = setTimeout(function () { msg.classList.remove('show'); }, 2200);
    }

    function markActive(mode) {
      cards.forEach(function (c) {
        var on = c.getAttribute('data-tmp-mode') === mode;
        c.classList.toggle('active', on);
        c.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
    }

    cards.forEach(function (c) {
      c.addEventListener('click', function () {
        var mode = c.getAttribute('data-tmp-mode');
        if (document.documentElement) document.documentElement.setAttribute('data-theme', mode);
        markActive(mode);

        fetch('{{ route('settings.theme') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ mode: mode })
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.ok) flash('Display theme saved.');
            else flash('Could not save the display theme.', true);
          })
          .catch(function () { flash('Could not save the display theme.', true); });
      });
    });
  })();
</script>
