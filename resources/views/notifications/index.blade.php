@extends('layouts.sidebar')

@section('title', 'Notifications')

@section('content')
<style>
  .nf-page { position: relative; }
  .nf-page::before,
  .nf-page::after {
    content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
  }
  .nf-page::before { width: 320px; height: 320px; background: rgba(16,185,129,0.15); top: -90px; right: -90px; }
  .nf-page::after { width: 280px; height: 280px; background: rgba(245,158,11,0.16); bottom: -70px; left: -70px; }
  .nf-content { position: relative; z-index: 1; }

  .nf-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
  .nf-header h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
  .nf-header p { font-size: 13px; color: #64748b; margin: 0; }
  .nf-unread-pill {
    display: inline-flex; align-items: center; gap: 7px; background: rgba(255,255,255,0.85);
    border: 1px solid #eef1ef; border-radius: 999px; padding: 8px 16px; font-size: 12px; font-weight: 700; color: #334155;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
  }
  .nf-unread-pill b { color: #b45309; font-size: 15px; }
  .btn-markread {
    display: inline-flex; align-items: center; gap: 7px; border: 1px solid #143d23; cursor: pointer;
    background: #143d23; color: #fff; font-size: 12px; font-weight: 700; padding: 9px 16px; border-radius: 10px;
    transition: all 0.18s ease; font-family: inherit;
  }
  .btn-markread:hover { background: #0f2f1b; }
  .btn-markread:disabled { opacity: 0.5; cursor: wait; }
  .btn-markread svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .nf-panel {
    background: rgba(255,255,255,0.88); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
  }
  .nf-list { padding: 6px 20px 20px; }
  .nf-item { display: flex; align-items: flex-start; gap: 14px; padding: 16px 8px; }
  .nf-item + .nf-item { border-top: 1px solid #f1f3f2; }
  .nf-item.is-unread { background: linear-gradient(90deg, rgba(23,163,74,0.06), rgba(23,163,74,0.0) 60%); }
  .nf-icon {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
  }
  .nf-icon svg { width: 19px; height: 19px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .nf-mid { flex: 1; min-width: 0; }
  .nf-title { font-size: 14px; font-weight: 600; color: #0f172a; line-height: 1.45; }
  .nf-meta { font-size: 12px; color: #94a3b8; margin-top: 5px; display: flex; align-items: center; gap: 7px; flex-wrap: wrap; font-weight: 500; }
  .nf-meta svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .nf-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; flex-shrink: 0; }
  .nf-dismiss {
    border: none; background: transparent; cursor: pointer; color: #94a3b8; padding: 5px;
    display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.15s ease;
  }
  .nf-dismiss:hover { background: #f0fdf4; color: #16a34a; }
  .nf-dismiss svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .nf-empty { text-align: center; padding: 56px 20px; }
  .nf-empty-icon {
    width: 76px; height: 76px; border-radius: 50%; margin: 0 auto 18px;
    background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center;
    box-shadow: inset 0 0 0 1px rgba(22,163,74,0.15);
  }
  .nf-empty-icon svg { width: 34px; height: 34px; stroke: currentColor; fill: none; stroke-width: 1.75; stroke-linecap: round; stroke-linejoin: round; }
  .nf-empty h3 { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0; }
  .nf-empty p { font-size: 13.5px; color: #64748b; margin: 0 auto; max-width: 420px; line-height: 1.6; }

  .nf-toast {
    position: fixed; bottom: 26px; left: 50%; transform: translate(-50%, 20px); z-index: 1000;
    background: #143d23; color: #ffffff; font-size: 13px; font-weight: 600;
    padding: 12px 20px; border-radius: 12px; box-shadow: 0 10px 30px rgba(20,61,35,0.35);
    display: flex; align-items: center; gap: 9px; opacity: 0; visibility: hidden;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1); white-space: nowrap; max-width: 90vw;
  }
  .nf-toast.show { opacity: 1; visibility: visible; transform: translate(-50%, 0); }
  .nf-toast svg { width: 16px; height: 16px; stroke: #6aeb2f; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

  @media (max-width: 768px) {
    .nf-item { flex-direction: column; gap: 10px; }
    .nf-right { align-items: flex-start; width: 100%; }
  }
</style>

<div class="nf-page">
  <div class="nf-content">
    <div class="nf-header">
      <div>
        <h1>Notifications</h1>
        <p>{{ $subtitle }}</p>
      </div>
      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <span class="nf-unread-pill">
          <svg viewBox="0 0 24 24" width="15" height="15" stroke="#b45309" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <b>{{ $unreadCount }}</b> unread
        </span>
        @if($unreadCount > 0)
          <form method="POST" action="{{ route($routePrefix . '.read-all') }}">
            @csrf
            <button type="submit" class="btn-markread">Mark all as read</button>
          </form>
        @endif
      </div>
    </div>

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
              <div class="nf-icon" style="background: {{ $notification->bg ?? '#f0fdf4' }}; color: {{ $notification->color ?? '#16a34a' }};">
                {!! $notification->icon ?? '' !!}
              </div>
              <div class="nf-mid">
                <div class="nf-title">{{ $notification->description }}</div>
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
              <div class="nf-right">
                @if(!$notification->read_at)
                  <form method="POST" action="{{ route($routePrefix . '.read', $notification->id) }}">
                    @csrf
                    <button type="submit" class="nf-dismiss" title="Mark as read">
                      <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                  </form>
                @endif
              </div>
            </div>
          @endforeach

          {{ $notifications->withQueryString()->links('partials.pagination') }}
        </div>
      @endif
    </div>
  </div>
</div>

<div class="nf-toast" id="nfToast"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><span id="nfToastMsg"></span></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
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
