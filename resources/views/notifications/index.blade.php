@extends('layouts.sidebar')

@section('title', 'Notifications')

@section('content')
{{--
    One shared list for every role. Deliberately quiet: a single white card,
    plain rows, one sage dot for what is still unread. No gradients, no glass,
    no coloured icon tiles — the wording is the interface.

    Class names are kept stable (.nf-panel, .nf-item, .nf-title, …) because
    public/css/dark-mode.css themes this page through them.
--}}
<style>
  .nf-page { max-width: 900px; }

  /* One calm pointer to the outside-the-app pop-ups. Hidden by script on a
     device that already has push turned on. */
  .nf-hint {
    display: flex; align-items: flex-start; gap: 9px;
    background: #F3F5F0; border: 1px solid #E0E5DB; border-radius: 12px;
    padding: 10px 14px; margin-bottom: 16px;
    font-size: 12.5px; line-height: 1.55; color: #647263;
  }
  .nf-hint[hidden] { display: none; }
  .nf-hint svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 2px; stroke: #648268; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .nf-hint a { color: #166534; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
  [data-theme="dark"] .nf-hint { background-color: rgba(15, 23, 42, 0.72); border-color: #334155; color: #9fb3a6; }
  [data-theme="dark"] .nf-hint svg { stroke: #6ee7a0; }
  [data-theme="dark"] .nf-hint a { color: #6ee7a0; }

  .nf-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
  .nf-header h1 { font-size: 22px; font-weight: 700; color: #344B38; letter-spacing: -0.01em; margin: 0 0 3px 0; }
  .nf-header p { font-size: 13px; color: #647263; margin: 0; line-height: 1.5; }

  .nf-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .nf-unread-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: #F3F5F0; border: 1px solid #E0E5DB; border-radius: 999px;
    padding: 6px 13px; font-size: 12px; font-weight: 600; color: #647263;
  }
  .nf-unread-pill b { color: #344B38; font-size: 13px; }

  .btn-markread {
    display: inline-flex; align-items: center; gap: 7px; border: none; cursor: pointer;
    background: #14532d; color: #fff; font-size: 12px; font-weight: 700;
    padding: 8px 15px; border-radius: 10px; font-family: inherit;
    transition: background 0.15s ease;
  }
  .btn-markread:hover { background: #166534; }
  .btn-markread:disabled { opacity: 0.5; cursor: wait; }
  .btn-markread svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .nf-panel {
    background: #fff; border: 1px solid #E0E5DB; border-radius: 16px; overflow: hidden;
  }
  .nf-list { padding: 2px 22px 18px; }
  .nf-item { display: flex; align-items: flex-start; gap: 12px; padding: 15px 0; }
  .nf-item + .nf-item { border-top: 1px solid #F0F2F1; }

  /* The one dot that carries "unread" — nothing else on the row shouts. */
  .nf-dot { width: 8px; height: 8px; border-radius: 50%; background: transparent; flex-shrink: 0; margin-top: 7px; }
  .nf-item.is-unread .nf-dot { background: #16a34a; }

  .nf-mid { flex: 1; min-width: 0; }
  .nf-title { font-size: 14px; font-weight: 500; color: #344B38; line-height: 1.5; }
  .nf-item.is-unread .nf-title { font-weight: 600; color: #1f2d24; }

  /* Live status chip: where the notified event sits right now. The state is
     recomputed on every render, so a finished event reads "Done" instead of
     keeping the "Upcoming" wording it was born with. */
  .nf-state {
    display: inline-flex; align-items: center; padding: 1px 8px; border-radius: 999px;
    border: 1px solid #E0E5DB; background: #F3F5F0; color: #647263;
    font-size: 10.5px; font-weight: 700; line-height: 1.6; letter-spacing: 0.02em;
    margin-right: 8px; vertical-align: middle; white-space: nowrap;
  }
  .nf-state--upcoming { background: #F3F5F0; color: #647263; border-color: #E0E5DB; }
  .nf-state--ongoing  { background: #EDF1E9; color: #166534; border-color: #C8D5C5; }
  .nf-state--done     { background: #FAFBFA; color: #94a3b8; border-color: #EDF0EC; }

  .nf-meta {
    display: flex; align-items: center; gap: 7px; flex-wrap: wrap;
    font-size: 12px; color: #94a3b8; font-weight: 500; margin-top: 4px;
  }
  .nf-meta svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  .nf-dismiss {
    border: 1px solid #E0E5DB; background: #fff; cursor: pointer; color: #94a3b8;
    width: 28px; height: 28px; flex-shrink: 0; display: inline-flex; align-items: center;
    justify-content: center; border-radius: 50%; transition: all 0.15s ease; margin-top: 2px;
  }
  .nf-dismiss:hover { background: #EDF1E9; border-color: #C8D5C5; color: #166534; }
  .nf-dismiss svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .nf-empty { text-align: center; padding: 52px 22px; }
  .nf-empty-icon {
    width: 52px; height: 52px; border-radius: 14px; margin: 0 auto 14px;
    background: #EDF1E9; color: #648268; display: flex; align-items: center; justify-content: center;
  }
  .nf-empty-icon svg { width: 24px; height: 24px; stroke: currentColor; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
  .nf-empty h3 { font-size: 15px; font-weight: 700; color: #344B38; margin: 0 0 6px 0; }
  .nf-empty p { font-size: 13px; color: #647263; margin: 0 auto; max-width: 420px; line-height: 1.6; }

  .nf-toast {
    position: fixed; bottom: 26px; left: 50%; transform: translate(-50%, 20px); z-index: 1000;
    background: #143d23; color: #ffffff; font-size: 13px; font-weight: 600;
    padding: 12px 20px; border-radius: 12px; box-shadow: 0 10px 30px rgba(20,61,35,0.35);
    display: flex; align-items: center; gap: 9px; opacity: 0; visibility: hidden;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1); white-space: nowrap; max-width: 90vw;
  }
  .nf-toast.show { opacity: 1; visibility: visible; transform: translate(-50%, 0); }
  .nf-toast svg { width: 16px; height: 16px; stroke: #6aeb2f; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

  @media (max-width: 640px) {
    .nf-header { align-items: flex-start; }
    .nf-header h1 { font-size: 20px; }
    .nf-list { padding: 2px 16px 14px; }
    .nf-item { gap: 10px; padding: 14px 0; }
    .nf-item { flex-direction: row; }
    .nf-dot { margin-top: 6px; }
    .nf-dismiss { align-self: flex-start; }
    .nf-empty { padding: 40px 16px; }
  }
</style>

<div class="nf-page">
  <div class="nf-content">
    <div class="nf-header">
      <div>
        <h1>Notifications</h1>
        <p>{{ $subtitle }}</p>
      </div>
      <div class="nf-actions">
        @if($unreadCount > 0)
          <span class="nf-unread-pill"><b>{{ $unreadCount }}</b> unread</span>
          <form method="POST" action="{{ route($routePrefix . '.read-all') }}">
            @csrf
            <button type="submit" class="btn-markread">Mark all as read</button>
          </form>
        @endif
      </div>
    </div>

    @if(\App\Services\PushService::available())
      <div class="nf-hint" id="nf-push-hint">
        <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span>
          These notices live inside the app. To receive them as pop-ups on this
          device even when the app is closed, turn on
          <a href="{{ route(explode('.', $routePrefix)[0] . '.settings.index') }}">push notifications in Settings</a>.
        </span>
      </div>
    @endif

    <div class="nf-panel">
      @if($notifications->isEmpty())
        <div class="nf-empty">
          <div class="nf-empty-icon">
            <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          </div>
          <h3>No Notifications Yet</h3>
          <p>{{ $emptyText }}</p>
        </div>
      @else
        <div class="nf-list">
          @foreach($notifications as $notification)
            <div class="nf-item {{ $notification->read_at ? '' : 'is-unread' }}">
              <span class="nf-dot"></span>
              <div class="nf-mid">
                <div class="nf-title">
                  @if($notification->event_state)
                    <span class="nf-state nf-state--{{ $notification->event_state }}">{{ $notification->event_state_label }}</span>
                  @endif
                  {{ $notification->display_description }}
                </div>
                <div class="nf-meta">
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                  <span>{{ optional($notification->created_at)->diffForHumans() }}</span>
                  <span>&middot;</span>
                  <span>{{ optional($notification->created_at)->format('M d, Y \a\t g:i A') }}</span>
                  @if($notification->actor_name && $notification->actor_name !== ($notification->user?->name ?? ''))
                    <span>&middot;</span>
                    <span>{{ $notification->actor_name }}</span>
                  @endif
                </div>
              </div>
              @if(!$notification->read_at)
                <form method="POST" action="{{ route($routePrefix . '.read', $notification->id) }}">
                  @csrf
                  <button type="submit" class="nf-dismiss" title="Mark as read" aria-label="Mark as read">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                  </button>
                </form>
              @endif
            </div>
          @endforeach

          {{ $notifications->withQueryString()->links('partials.pagination') }}
        </div>
      @endif
    </div>
  </div>
</div>

<div class="nf-toast" id="nfToast"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><span id="nfToastMsg"></span></div>

<script src="{{ asset('js/push-subscribe.js') }}?v=2" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // The push pointer above only earns its place while this device still
  // needs it: once notifications are on (or the browser cannot do them at
  // all) it disappears, leaving the list clean.
  var hint = document.getElementById('nf-push-hint');
  if (hint && window.AquilalaPush) {
    if (!window.AquilalaPush.supported() && !window.AquilalaPush.needsIosInstall()) {
      hint.hidden = true;
    } else {
      window.AquilalaPush.state().then(function (state) {
        if (state === 'enabled') hint.hidden = true;
      });
    }
  }

  var toastEl = document.getElementById('nfToast');
  var toastMsg = document.getElementById('nfToastMsg');
  @if (session('success'))
    if (toastEl && toastMsg) {
      toastMsg.textContent = @js(session('success'));
      toastEl.classList.add('show');
      setTimeout(function () { toastEl.classList.remove('show'); }, 3500);
    }
  @endif
});
</script>
@endsection
