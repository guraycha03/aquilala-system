{{--
    Notifications settings — shown on every role's Settings page.

    1) Push notifications: opt THIS device into Web Push (works outside the
       app, even closed; tapping opens the right page). The card renders
       only when the server has VAPID keys configured AND the browser can
       do push — otherwise the feature is simply not offered and nothing
       breaks. iOS Safari additionally needs the app installed to the home
       screen, so it shows an install hint instead of a broken toggle.

    2) Activity history: the full action log lives here under Settings —
       the notification page itself is important notices only.
--}}
@php
    // The toggle card shows only when the server has VAPID keys —
    // otherwise the feature is simply not offered and nothing breaks.
    $pushConfigured = \App\Services\PushService::available();
    $activityRoute  = match (auth()->user()?->role) {
        'admin'       => 'admin.activity.index',
        'store_owner' => 'store.activity.index',
        default       => 'farmer.activity.index',
    };
@endphp

@if($pushConfigured)
<div class="settings-card">
    <div class="settings-card-label">Notifications</div>
    <h2 class="settings-card-title">Push notifications</h2>
    <p class="settings-card-desc">
        Get important notices on this device even when the app is closed —
        association events, planting decisions and demand signals. Opening a
        notification takes you straight to the page it is about.
    </p>

    <div id="push-ios-hint" class="push-hint" hidden>
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>On iPhone/iPad, open this site from your Home Screen
        (<b>Safari → Share → Add to Home Screen</b>) to receive push notifications.</span>
    </div>

    <div id="push-unsupported" class="push-hint" hidden>
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Push notifications are not available in this browser.</span>
    </div>

    <div id="push-row" class="push-row" hidden>
        <button type="button" id="push-toggle" class="push-toggle">
            <span class="push-toggle-track"><span class="push-toggle-thumb"></span></span>
            <span class="push-toggle-label">Enable on this device</span>
        </button>
        <p id="push-status" class="push-status"></p>
    </div>
</div>
@endif

<div class="settings-card">
    <div class="settings-card-label">History</div>
    <h2 class="settings-card-title">Activity history</h2>
    <p class="settings-card-desc">
        Your full record of actions on the platform — sign-ins, quotations,
        planting and harvest activity. Notifications stay on the bell page;
        the complete log lives here.
    </p>
    <a href="{{ route($activityRoute) }}" class="push-hint settings-link">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span>Open activity history</span>
    </a>
</div>

<style>
  .settings-card {
    background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    padding: 22px; margin-top: 18px;
  }
  .settings-card-label {
    font-size: 11px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 4px;
  }
  .settings-card-title { font-size: 15px; font-weight: 800; color: #1e293b; margin: 0 0 4px; }
  .settings-card-desc { font-size: 12px; color: #64748b; margin: 0 0 14px; line-height: 1.6; }

  .push-hint {
    display: flex; align-items: flex-start; gap: 9px;
    background: #fffbeb; border: 1px solid #fde68a; color: #92400e;
    border-radius: 12px; padding: 11px 14px; font-size: 12.5px; line-height: 1.55;
    margin-top: 4px;
  }
  /* display:flex outranks the UA [hidden] rule — re-hide explicitly. */
  .push-hint[hidden], .push-row[hidden] { display: none; }
  .push-hint svg { flex-shrink: 0; margin-top: 1px; }
  a.push-hint { text-decoration: none; margin-top: 14px; width: fit-content; }
  a.push-hint:hover { background: #fef3c7; }
  .settings-link span { text-decoration: underline; text-underline-offset: 2px; }

  .push-row { margin-top: 14px; }
  .push-toggle {
    display: inline-flex; align-items: center; gap: 11px;
    background: none; border: none; padding: 4px 0; cursor: pointer;
    font: inherit; color: #1e293b;
  }
  .push-toggle:disabled { cursor: default; opacity: 0.6; }
  .push-toggle-track {
    width: 42px; height: 24px; border-radius: 999px; background: #cbd5e1;
    position: relative; transition: background 0.2s ease; flex-shrink: 0;
  }
  .push-toggle-thumb {
    position: absolute; top: 3px; left: 3px; width: 18px; height: 18px;
    border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    transition: transform 0.2s ease;
  }
  .push-toggle.is-on .push-toggle-track { background: #16a34a; }
  .push-toggle.is-on .push-toggle-thumb { transform: translateX(18px); }
  .push-toggle-label { font-size: 13.5px; font-weight: 600; }
  .push-status { font-size: 12px; color: #64748b; margin: 6px 0 0; min-height: 1em; }
  .push-status.is-error { color: #b91c1c; }
</style>

<script src="{{ asset('js/push-subscribe.js') }}?v=2" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var row         = document.getElementById('push-row');
    var iosHint     = document.getElementById('push-ios-hint');
    var unsupported = document.getElementById('push-unsupported');
    var toggle      = document.getElementById('push-toggle');
    var label       = toggle ? toggle.querySelector('.push-toggle-label') : null;
    var status      = document.getElementById('push-status');

    if (!row || !window.AquilalaPush) return;

    function setStatus(text, isError) {
        if (!status) return;
        status.textContent = text || '';
        status.className = 'push-status' + (isError ? ' is-error' : '');
    }

    function paint(state) {
        if (!toggle) return;
        toggle.classList.toggle('is-on', state === 'enabled');
        if (label) {
            label.textContent = state === 'enabled' ? 'Notifications on' : 'Enable on this device';
        }
    }

    if (!window.AquilalaPush.supported()) {
        if (iosHint && window.AquilalaPush.needsIosInstall()) iosHint.hidden = false;
        else if (unsupported) unsupported.hidden = false;
        return;
    }

    if (row) row.hidden = false;

    // Load current state; if already granted, silently keep the server-side
    // subscription fresh (endpoints rotate).
    window.AquilalaPush.state().then(function (state) {
        paint(state);
        if (state === 'enabled') {
            setStatus('This device receives important notices.');
            window.AquilalaPush.sync();
        }
    });

    if (!toggle) return;
    toggle.addEventListener('click', function () {
        var isOn = toggle.classList.contains('is-on');
        toggle.disabled = true;
        setStatus(isOn ? 'Turning off…' : 'Requesting permission…');

        var action = isOn ? window.AquilalaPush.disable() : window.AquilalaPush.enable();

        action.then(function () {
            paint(isOn ? 'off' : 'enabled');
            setStatus(isOn ? 'Notifications are off for this device.' : 'This device receives important notices.');
        }).catch(function (err) {
            var denied = err && err.message === 'denied';
            setStatus(
                denied
                    ? 'Permission was declined. Allow notifications in your browser settings to turn this on.'
                    : 'Could not turn on notifications right now.',
                true
            );
            window.AquilalaPush.state().then(paint);
        }).finally(function () {
            toggle.disabled = false;
        });
    });
});
</script>
