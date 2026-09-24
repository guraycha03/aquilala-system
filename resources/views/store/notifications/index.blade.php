@extends('layouts.sidebar')

@section('title', 'Notifications')

@section('content')
<style>
  .nf-page { position: relative; }
  .nf-page::before,
  .nf-page::after {
    content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
  }
  .nf-page::before { width: 320px; height: 320px; background: rgba(245,158,11,0.16); top: -90px; right: -90px; }
  .nf-page::after { width: 280px; height: 280px; background: rgba(16,185,129,0.15); bottom: -70px; left: -70px; }
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
  .nf-item.is-unread { background: linear-gradient(90deg, rgba(245,158,11,0.05), rgba(245,158,11,0.0) 60%); }
  .nf-icon {
    width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; color: #fff;
  }
  .nf-icon svg { width: 19px; height: 19px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .nf-mid { flex: 1; min-width: 0; }
  .nf-top { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
  .nf-title { font-size: 14px; font-weight: 700; color: #0f172a; line-height: 1.45; }
  .nf-stars { display: inline-flex; align-items: center; gap: 2px; }
  .nf-stars svg { width: 13px; height: 13px; fill: #f59e0b; }
  .nf-stars svg.empty { fill: #e2e8f0; }
  .nf-meta { font-size: 12px; color: #94a3b8; margin-top: 5px; display: flex; align-items: center; gap: 7px; flex-wrap: wrap; font-weight: 500; }
  .nf-comment { margin-top: 8px; font-size: 12.5px; color: #475569; background: #f8faf9; border: 1px solid #eef1ef; border-radius: 9px; padding: 8px 12px; line-height: 1.5; }
  .nf-comment.blank { font-style: italic; color: #94a3b8; }
  .nf-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; flex-shrink: 0; }
  .nf-status { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
  .nf-actions { display: flex; gap: 8px; align-items: center; }
  .nf-link {
    font-size: 12px; font-weight: 700; color: #143d23; text-decoration: none;
    padding: 7px 12px; border-radius: 9px; background: rgba(20,61,35,0.07); display: inline-flex; align-items: center; gap: 6px;
    transition: all 0.18s ease;
  }
  .nf-link:hover { background: rgba(20,61,35,0.14); }
  .nf-link svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
  .nf-dismiss {
    border: none; background: transparent; cursor: pointer; color: #94a3b8; padding: 5px;
    display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.15s ease;
  }
  .nf-dismiss:hover { background: #faf2f1; color: #b0403a; }
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
    .nf-actions { width: 100%; }
    .nf-link { flex: 1; justify-content: center; }
  }
</style>

<div class="nf-page">
  <div class="nf-content">
    <div class="nf-header">
      <div>
        <h1>Notifications</h1>
        <p>Real-time alerts when an approved farmer review is published on your products.</p>
      </div>
      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <span class="nf-unread-pill">
          <svg viewBox="0 0 24 24" width="15" height="15" stroke="#b45309" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <b>{{ $unreadCount }}</b> unread
        </span>
        @if($unreadCount > 0)
          <form method="POST" action="{{ route('store.notifications.read-all') }}">
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
          <h3>No Review Notifications</h3>
          <p>When a farmer's review of your product is approved by the admin and published, you'll be notified here.</p>
        </div>
      @else
        <div class="nf-list">
          @foreach($notifications as $notification)
            @php
              $meta = $notification->metadata ?? [];
              $st = match ($meta['status'] ?? $notification->type) {
                'approved' => ['Approved', '#eef3ef', '#63856c'],
                'rejected' => ['Disapproved', '#f6ecec', '#a86464'],
                'deleted'  => ['Removed', '#f1f5f9', '#64748b'],
                default    => ['Pending', '#f6f0e6', '#a57f43'],
              };
              $iconColor = match ($meta['status'] ?? $notification->type) {
                'approved' => '#16a34a',
                'rejected' => '#dc2626',
                'deleted'  => '#64748b',
                default    => '#d97706',
              };
              $productName = $meta['product_name'] ?? 'a product';
              $reviewer    = $meta['reviewer'] ?? 'A farmer';
              $rating      = (int) ($meta['rating'] ?? 0);
              $comment     = $meta['comment'] ?? '';
              $productId   = $meta['product_id'] ?? null;
            @endphp
            <div class="nf-item {{ $notification->read_at ? '' : 'is-unread' }}">
              <div class="nf-icon" style="background: {{ $iconColor }};">
                @if(str_ends_with($notification->type, 'approved'))
                  <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                @elseif(str_ends_with($notification->type, 'rejected') || str_ends_with($notification->type, 'deleted'))
                  <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                @else
                  <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                @endif
              </div>
              <div class="nf-mid">
                <div class="nf-top">
                  <span class="nf-title">{{ $notification->description }}</span>
                </div>
                <div class="nf-stars">
                  @for($i = 1; $i <= 5; $i++)
                    <svg viewBox="0 0 24 24" class="{{ $i <= $rating ? '' : 'empty' }}"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                  @endfor
                  @if($rating) <span style="font-size:11px;font-weight:800;color:#b45309;margin-left:2px;">{{ $rating }}/5</span> @endif
                </div>
                @if($comment)
                  <div class="nf-comment">{{ $comment }}</div>
                @endif
                <div class="nf-meta">
                  <span>by {{ $reviewer }}</span>
                  <span>&middot;</span>
                  <span>{{ optional($notification->created_at)->diffForHumans() }}</span>
                  <span>&middot;</span>
                  <span>{{ optional($notification->created_at)->format('M d, Y \a\t g:i A') }}</span>
                </div>
              </div>
              <div class="nf-right">
                <span class="nf-status" style="background:{{ $st[1] }};color:{{ $st[2] }};">{{ $st[0] }}</span>
                <div class="nf-actions">
                  @if($productId && $productId > 0)
                    <a href="{{ route('store.products.show', $productId) }}" class="nf-link">
                      <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                      View product
                    </a>
                  @endif
                  @if(!$notification->read_at)
                    <form method="POST" action="{{ route('store.notifications.read', $notification->id) }}">
                      @csrf
                      <button type="submit" class="nf-dismiss" title="Mark as read">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                      </button>
                    </form>
                  @endif
                </div>
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