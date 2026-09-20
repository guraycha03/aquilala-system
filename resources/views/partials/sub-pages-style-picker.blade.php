@php
    $sppSaved = (string) optional(auth()->user())->sub_pages_style;
    $sppCurrent = $sppSaved === 'sidebar' ? 'sidebar' : 'top';
@endphp

<style>
  /* Sub-page navigation style picker — shared by every role's settings page */
  .spp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
  .spp-opt {
    position: relative; border: 2px solid #e5ebe7; border-radius: 14px; padding: 10px;
    background: #ffffff; cursor: pointer; text-align: left; font-family: inherit;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
  }
  .spp-opt:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(20,61,35,0.10); }
  .spp-opt.active { border-color: #8fbf4d; box-shadow: 0 0 0 3px rgba(143,191,77,0.22); }
  .spp-preview {
    height: 74px; border-radius: 10px; padding: 10px; margin-bottom: 10px;
    background: #f8fafc; border: 1px solid #eef1f4;
    display: flex; flex-direction: column; gap: 6px; overflow: hidden;
  }
  /* Preview: sub-pages inside a side menu */
  .spp-preview.side { background: #143d23; border-color: #143d23; padding: 9px 8px; gap: 5px; }
  .spp-preview.side i {
    display: block; height: 9px; border-radius: 4px;
    background: rgba(255, 255, 255, 0.22);
  }
  .spp-preview.side i.mid { margin-left: 6px; background: rgba(255, 255, 255, 0.13); }
  .spp-preview.side i.sub { margin-left: 14px; background: rgba(255, 255, 255, 0.30); }
  .spp-preview.side i.sub.on { background: #6aeb2f; }
  /* Preview: pill tabs pinned on top */
  .spp-preview.top i {
    display: block; height: 10px; border-radius: 5px; background: #e2e8f0;
  }
  .spp-preview.top .spp-top-tabs { display: flex; gap: 5px; }
  .spp-preview.top .spp-top-tabs i { flex: 1; }
  .spp-preview.top .spp-top-tabs i.on { background: #609143; }
  .spp-preview.top .spp-top-body i { height: 26px; margin-top: 6px; background: #eef2ef; }
  .spp-name { font-size: 13px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px; }
  .spp-desc { font-size: 11px; color: #94a3b8; line-height: 1.4; margin-top: 2px; }
  .spp-check {
    margin-left: auto; width: 18px; height: 18px; border-radius: 50%; background: #8fbf4d;
    color: #ffffff; display: none; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .spp-opt.active .spp-check { display: inline-flex; }
  .spp-msg {
    margin-top: 12px; font-size: 12px; font-weight: 600; color: #15803d;
    opacity: 0; transform: translateY(2px); transition: opacity .2s ease, transform .2s ease;
  }
  .spp-msg.show { opacity: 1; transform: none; }
  .spp-msg.err { color: #dc2626; }
</style>

<div class="spp-grid">
  <button type="button" class="spp-opt {{ $sppCurrent === 'sidebar' ? 'active' : '' }}"
          data-spp-style="sidebar" aria-pressed="{{ $sppCurrent === 'sidebar' ? 'true' : 'false' }}">
    <span class="spp-preview side">
      <i></i>
      <i class="mid"></i>
      <i class="sub {{ $sppCurrent === 'sidebar' ? 'on' : '' }}"></i>
    </span>
    <span class="spp-name">
      Option 1 · In the side menu
      <span class="spp-check">
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </span>
    </span>
    <span class="spp-desc">Sub-page buttons appear under each menu name inside the sidebar, styled like the side menu.</span>
  </button>

  <button type="button" class="spp-opt {{ $sppCurrent === 'top' ? 'active' : '' }}"
          data-spp-style="top" aria-pressed="{{ $sppCurrent === 'top' ? 'true' : 'false' }}">
    <span class="spp-preview top">
      <span class="spp-top-tabs">
        <i class="{{ $sppCurrent === 'top' ? 'on' : '' }}"></i>
        <i></i>
        <i></i>
      </span>
      <span class="spp-top-body"><i></i></span>
    </span>
    <span class="spp-name">
      Option 2 · Tabs on top
      <span class="spp-check">
        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </span>
    </span>
    <span class="spp-desc">Pill buttons pinned along the top of each page, above the content (the current style).</span>
  </button>
</div>
<p class="spp-hint" style="font-size:11px;color:#94a3b8;margin:10px 2px 0;">Click a style to apply instantly — the page reloads and your choice is saved to your account automatically.</p>
<p class="spp-msg" id="sppMsg" role="status"></p>

<script>
  (function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.content : '';
    var opts = Array.prototype.slice.call(document.querySelectorAll('.spp-opt'));
    var msg = document.getElementById('sppMsg');
    var msgTimer = null;

    function flash(text, isError) {
      if (!msg) return;
      msg.textContent = text;
      msg.classList.toggle('err', !!isError);
      msg.classList.add('show');
      clearTimeout(msgTimer);
      msgTimer = setTimeout(function () { msg.classList.remove('show'); }, 2200);
    }

    opts.forEach(function (o) {
      o.addEventListener('click', function () {
        var style = o.getAttribute('data-spp-style');
        opts.forEach(function (x) {
          var on = x === o;
          x.classList.toggle('active', on);
          x.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        document.querySelectorAll('.spp-preview.side .sub').forEach(function (s) {
          s.classList.toggle('on', style === 'sidebar');
        });
        document.querySelectorAll('.spp-preview.top .spp-top-tabs i').forEach(function (s, idx) {
          s.classList.toggle('on', style === 'top' && idx === 0);
        });

        fetch('{{ route('settings.tabs-style') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ style: style })
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.ok) {
              flash('Sub-page style saved.');
              setTimeout(function () { window.location.reload(); }, 350);
            } else {
              flash('Could not save the style.', true);
            }
          })
          .catch(function () { flash('Could not save the style.', true); });
      });
    });
  })();
</script>