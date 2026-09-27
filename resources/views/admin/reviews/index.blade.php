@extends('layouts.sidebar')

@section('title', 'Product Reviews Moderation')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
  .rv-page { position: relative; }
  .rv-page::before,
  .rv-page::after {
    content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
  }
  .rv-page::before { width: 340px; height: 340px; background: rgba(245,158,11,0.14); top: -90px; right: -90px; }
  .rv-page::after { width: 280px; height: 280px; background: rgba(16,185,129,0.14); bottom: -70px; left: -70px; }
  .rv-content { position: relative; z-index: 1; }

  .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
  .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
  .header-row p { font-size: 13px; color: #64748b; margin: 0; }

  .rv-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 22px; }
  .rv-kpi {
    padding: 16px 18px; border-radius: 14px; background: rgba(255,255,255,0.84);
    backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    text-decoration: none; display: block; transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .rv-kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
  .rv-kpi .ak-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #64748b; margin-bottom: 6px; }
  .rv-kpi .ak-value { font-size: 24px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
  .rv-kpi .ak-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }
  .rv-kpi.kpi-active { border-color: #fbbf24; box-shadow: 0 4px 20px rgba(20,61,35,0.08); }
  .rv-kpi.kpi-green { border-left: 4px solid #16a34a; }
  .rv-kpi.kpi-amber { border-left: 4px solid #f59e0b; }
  .rv-kpi.kpi-red { border-left: 4px solid #dc2626; }
  .rv-kpi.kpi-slate { border-left: 4px solid #64748b; }

  .rv-panel {
    background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
    position: relative;
  }
  .rv-toolbar {
    display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
    padding: 16px 20px; border-bottom: 1px solid #eef1ef;
  }
  .status-seg { display: inline-flex; background: #f6f8f7; border: 1px solid #e6ebe8; border-radius: 12px; padding: 4px; gap: 2px; flex-wrap: wrap; }
  .status-seg a {
    border: none; background: transparent; font-size: 12px; font-weight: 700; color: #64748b;
    padding: 8px 14px; border-radius: 9px; cursor: pointer; transition: all 0.2s ease; font-family: inherit;
    display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
  }
  .status-seg a .c { font-size: 10px; font-weight: 800; background: #e6ebe8; color: #64748b; border-radius: 999px; padding: 1px 7px; }
  .status-seg a.active { background: #143d23; color: #ffffff; box-shadow: 0 2px 8px rgba(20,61,35,0.22); }
  .status-seg a.active .c { background: rgba(255,255,255,0.2); color: #ffffff; }

  .rv-list { padding: 6px 20px 20px; }
  .rv-card { display: flex; align-items: flex-start; gap: 16px; padding: 16px 8px; }
  .rv-card + .rv-card { border-top: 1px solid #f1f3f2; }
  .rv-avatar {
    width: 46px; height: 46px; border-radius: 13px; background: rgb(205, 245, 160); color: #14532d;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    font-weight: 800; font-size: 16px; text-transform: uppercase;
  }
  .rv-mid { flex: 1; min-width: 0; }
  .rv-head { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
  .rv-name { font-size: 14px; font-weight: 700; color: #1e293b; }
  .rv-stars { display: inline-flex; align-items: center; gap: 2px; }
  .rv-stars svg { width: 14px; height: 14px; fill: #f59e0b; }
  .rv-stars svg.empty { fill: #e2e8f0; }
  .rv-rating-num { font-size: 12px; font-weight: 800; color: #b45309; }
  .rv-date { font-size: 11px; color: #94a3b8; }
  .rv-product { font-size: 12px; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 5px; }
  .rv-product a { color: #143d23; font-weight: 700; text-decoration: none; }
  .rv-product a:hover { text-decoration: underline; }
  .rv-store { color: #94a3b8; }
  .rv-comment {
    margin-top: 8px; font-size: 13px; color: #334155; line-height: 1.55;
    background: #f8faf9; border: 1px solid #eef1ef; border-radius: 10px; padding: 10px 14px;
  }
  .rv-comment.blank { font-style: italic; color: #94a3b8; }

  .rv-right { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; flex-shrink: 0; }
  .status-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: capitalize; }
  .rv-actions { display: flex; gap: 8px; }

  .btn-app {
    display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; cursor: pointer;
    font-size: 12px; font-weight: 700; padding: 8px 14px; border-radius: 9px; transition: all 0.18s ease;
    font-family: inherit;
  }
  .btn-app svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
  .btn-ok { background: #eef3ef; color: #63856c; border-color: #dbe5dd; }
  .btn-ok:hover { background: #63856c; color: #ffffff; }
  .btn-no { background: #f6ecec; color: #a86464; border-color: #efdcdc; }
  .btn-no:hover { background: #a86464; color: #ffffff; }
  .btn-app:disabled { opacity: 0.55; cursor: wait; }

  .rv-empty { text-align: center; padding: 48px 20px; color: #94a3b8; }
  .rv-empty svg { width: 38px; height: 38px; stroke: #cbd5e1; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
  .rv-empty p { font-size: 13px; color: #64748b; margin: 10px 0 0 0; }

  .rv-toast {
    position: fixed; bottom: 26px; left: 50%; transform: translate(-50%, 20px); z-index: 1000;
    background: #ffffff; color: #1f2a24; font-size: 13px; font-weight: 600;
    padding: 12px 20px; border-radius: 12px; border: 1px solid #e5ebe7;
    box-shadow: 0 10px 30px rgba(20,61,35,0.12);
    display: flex; align-items: center; gap: 9px; opacity: 0; visibility: hidden;
    transition: all 0.25s cubic-bezier(0.4,0,0.2,1); white-space: nowrap; max-width: 90vw;
  }
  .rv-toast.show { opacity: 1; visibility: visible; transform: translate(-50%, 0); }
  .rv-toast svg { width: 16px; height: 16px; stroke: #16a34a; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

  @media (max-width: 768px) {
    .rv-card { flex-direction: column; gap: 12px; }
    .rv-right { align-items: flex-start; width: 100%; }
    .rv-actions { width: 100%; }
    .btn-app { flex: 1; justify-content: center; }
  }
</style>

<div class="rv-page">
  <div class="rv-content">
    <div class="header-row">
      <div>
        <h1>Product Reviews Moderation</h1>
        <p>Approve or disapprove farmer reviews before they appear publicly on products.</p>
      </div>
    </div>

    <section class="rv-kpis">
      <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="rv-kpi kpi-amber {{ $status === 'pending' ? 'kpi-active' : '' }}">
        <div class="ak-label">Needs Review</div>
        <div class="ak-value">{{ $counts['pending'] }}</div>
        <div class="ak-sub">Waiting for approval</div>
      </a>
      <a href="{{ route('admin.reviews.index', ['status' => 'approved']) }}" class="rv-kpi kpi-green {{ $status === 'approved' ? 'kpi-active' : '' }}">
        <div class="ak-label">Approved</div>
        <div class="ak-value">{{ $counts['approved'] }}</div>
        <div class="ak-sub">Public on all interfaces</div>
      </a>
      <a href="{{ route('admin.reviews.index', ['status' => 'rejected']) }}" class="rv-kpi kpi-red {{ $status === 'rejected' ? 'kpi-active' : '' }}">
        <div class="ak-label">Disapproved</div>
        <div class="ak-value">{{ $counts['rejected'] }}</div>
        <div class="ak-sub">Hidden from public views</div>
      </a>
      <a href="{{ route('admin.reviews.index', ['status' => 'all']) }}" class="rv-kpi kpi-slate {{ $status === 'all' ? 'kpi-active' : '' }}">
        <div class="ak-label">Total Reviews</div>
        <div class="ak-value">{{ array_sum($counts) }}</div>
        <div class="ak-sub">All submissions</div>
      </a>
    </section>

    <div class="rv-panel">
      <div class="rv-toolbar">
        <div class="status-seg">
          <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="{{ $status === 'pending' ? 'active' : '' }}">Pending <span class="c">{{ $counts['pending'] }}</span></a>
          <a href="{{ route('admin.reviews.index', ['status' => 'approved']) }}" class="{{ $status === 'approved' ? 'active' : '' }}">Approved <span class="c">{{ $counts['approved'] }}</span></a>
          <a href="{{ route('admin.reviews.index', ['status' => 'rejected']) }}" class="{{ $status === 'rejected' ? 'active' : '' }}">Disapproved <span class="c">{{ $counts['rejected'] }}</span></a>
          <a href="{{ route('admin.reviews.index', ['status' => 'all']) }}" class="{{ $status === 'all' ? 'active' : '' }}">All <span class="c">{{ array_sum($counts) }}</span></a>
        </div>
      </div>

      @if($reviews->isEmpty())
        <div class="rv-empty">
          <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <p>
            @if($status === 'pending')
              No reviews waiting for approval. New farmer reviews will appear here automatically.
            @else
              No reviews with this status.
            @endif
          </p>
        </div>
      @else
        <div class="rv-list">
          @foreach($reviews as $review)
            @php
              $badge = match ($review->status) {
                'approved' => ['Approved', '#eef3ef', '#63856c'],
                'rejected' => ['Disapproved', '#f6ecec', '#a86464'],
                default    => ['Pending', '#f6f0e6', '#a57f43'],
              };
              $storeName = $review->product?->storeOwner?->store_name ?? $review->product?->storeOwner?->name ?? 'Unknown merchant';
            @endphp
            <div class="rv-card">
              <div class="rv-avatar">{{ strtoupper(substr($review->farmer?->name ?? 'F', 0, 1)) }}</div>
              <div class="rv-mid">
                <div class="rv-head">
                  <span class="rv-name">{{ $review->farmer?->name ?? 'Unknown Farmer' }}</span>
                  <span class="rv-stars">
                    @for($i = 1; $i <= 5; $i++)
                      <svg viewBox="0 0 24 24" class="{{ $i <= $review->rating ? '' : 'empty' }}"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                  </span>
                  <span class="rv-rating-num">{{ $review->rating }}/5</span>
                  <span class="rv-date">{{ $review->created_at->format('M d, Y · h:i A') }}</span>
                </div>
                <div class="rv-product">
                  <svg viewBox="0 0 24 24" width="12" height="12" stroke="#94a3b8" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                  <a href="{{ route('farmer.products.show', $review->product_id) }}" target="_blank">{{ $review->product?->display_name ?? 'Deleted product' }}</a>
                  <span class="rv-store">· {{ $storeName }}</span>
                </div>
                <div class="rv-comment {{ $review->comment ? '' : 'blank' }}">
                  {{ $review->comment ?: 'No comment — rating only.' }}
                </div>
              </div>
              <div class="rv-right">
                <span class="status-badge" style="background:{{ $badge[1] }};color:{{ $badge[2] }};">{{ $badge[0] }}</span>
                <div class="rv-actions">
                  @if($review->status !== 'approved')
                    <form method="POST" action="{{ route('admin.reviews.approve', $review->id) }}" class="rv-form">
                      @csrf
                      <button type="submit" class="btn-app btn-ok" data-aqui-confirm="Approve this review?" data-aqui-tone="info" data-aqui-confirm-label="Approve">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                        Approve
                      </button>
                    </form>
                  @endif
                  @if($review->status !== 'rejected')
                    <form method="POST" action="{{ route('admin.reviews.disapprove', $review->id) }}" class="rv-form">
                      @csrf
                      <button type="submit" class="btn-app btn-no" data-aqui-confirm="Disapprove and hide this review?" data-aqui-tone="warn" data-aqui-confirm-label="Disapprove">
                        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Disapprove
                      </button>
                    </form>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</div>

<div class="rv-toast" id="rvToast"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg><span id="toastMsg"></span></div>

<script>
(document).ready || document.addEventListener('DOMContentLoaded', function () {
  var toastEl = document.getElementById('rvToast');
  var toastMsg = document.getElementById('toastMsg');
  var toastTimer = null;

  function showToast(msg) {
    if (!msg) return;
    toastMsg.textContent = msg;
    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 3500);
  }

  @if (session('success'))
    showToast(@js(session('success')));
  @endif
  @if (session('error'))
    showToast(@js(session('error')));
  @endif
});
</script>
@endsection