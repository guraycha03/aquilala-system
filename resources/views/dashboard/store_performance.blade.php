@extends('layouts.sidebar')

@section('title', 'Top Products')

@section('content')

<style>
  .dash-page { position: relative; }

  .dash-content { position: relative; z-index: 1; }
  @media (min-width: 769px) {
    .dash-page > .dash-content { margin-top: 57px; }
  }

  .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
  .reveal.in { opacity: 1; transform: translateY(0); }

  /* ── Panels ─────────────────────────────────────────────────── */
  .dash-panel {
    background: rgba(255,255,255,0.82); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.5); border-radius: 18px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
    overflow: hidden;
  }
  .dash-panel:hover { box-shadow: 0 16px 48px rgba(0,0,0,0.09); }
  .panel-head { padding: 18px 22px; border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; gap: 12px; }
  .panel-head h3 { font-size: 15px; font-weight: 800; color: #0f172a; margin: 0; }
  .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
  .panel-body { padding: 18px 22px; }

  /* ── Top products (Most Viewed / Most Quoted) ─────────────────────── */
  .top-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; }
  .top-grid .dash-panel { display: flex; flex-direction: column; height: 100%; }
  .top-grid .dash-panel .panel-body { flex: 1; }
  .top-panel-head { display: flex; align-items: center; gap: 10px; }
  .top-panel-head .top-ic {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .top-panel-head .top-ic svg { width: 17px; height: 17px; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
  .top-row {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 0; border-bottom: 1px dashed #eef2ef;
    text-decoration: none; color: inherit; min-width: 0;
  }
  .top-row:last-child { border-bottom: none; }
  .top-row:hover .top-name { color: #15803d; }
  .top-rank {
    width: 22px; height: 22px; border-radius: 7px; flex-shrink: 0;
    background: #eef2ef; color: #475569; font-size: 10px; font-weight: 800;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .top-row:nth-child(2) .top-rank { background: #fef9ec; color: #b45309; }
  .top-row:nth-child(3) .top-rank { background: #fef2f2; color: #b91c1c; }
  .top-thumb {
    width: 38px; height: 38px; border-radius: 10px; object-fit: cover; flex-shrink: 0;
    background: #f4f7f5; display: flex; align-items: center; justify-content: center;
  }
  .top-thumb svg { width: 20px; height: 20px; stroke: #cbd5e1; stroke-width: 1.5; fill: none; stroke-linecap: round; stroke-linejoin: round; }
  .top-name {
    flex: 1; min-width: 0; font-size: 12.5px; font-weight: 700; color: #1e2922;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.35;
  }
  .top-stat { text-align: right; flex-shrink: 0; line-height: 1.15; }
  .top-stat b { display: block; font-size: 13px; font-weight: 800; color: #14532d; }
  .top-stat i { font-style: normal; font-size: 9.5px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.03em; }
  .top-stat .top-stars { display: flex; align-items: center; justify-content: flex-end; gap: 2px; }
  .top-stat .top-stars svg { width: 12px; height: 12px; fill: #f59e0b; }
  .top-empty { text-align: center; padding: 26px 12px; }
  .top-empty svg { width: 30px; height: 30px; stroke: #cbd5e1; stroke-width: 1.5; fill: none; }
  .top-empty p { font-size: 12.5px; color: #94a3b8; margin: 8px 0 0 0; line-height: 1.5; }

  @media (max-width: 960px) {
    .top-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="dash-page">
  <div class="dash-content">

    <div class="header-panel" style="margin-bottom: 20px;">
      <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Top Products</h1>
      <p style="color: #606d64; font-size: 14px; margin: 0;">How your listings are doing — most viewed, most quoted and highest rated by farmers.</p>
    </div>

    <section class="top-grid reveal" data-reveal>

      <!-- MOST VIEWED -->
      <div class="dash-panel">
        <div class="panel-head">
          <div class="top-panel-head">
            <span class="top-ic" style="background:#dbeafe;color:#2563eb;"><i data-lucide="eye" width="17" height="17"></i></span>
            <div><h3>Most Viewed</h3><p>Product pages opened by farmers</p></div>
          </div>
        </div>
        <div class="panel-body">
          @forelse($topViewed as $idx => $product)
            <a class="top-row" href="{{ route('store.products.show', $product->id) }}">
              <span class="top-rank">#{{ $idx + 1 }}</span>
              @if($product->mainImage && !empty($product->mainImage->file_path))
                <img class="top-thumb" src="{{ $product->mainImage->thumb_url }}" alt="{{ $product->display_name }}" loading="lazy">
              @else
                <span class="top-thumb"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>
              @endif
              <span class="top-name">{{ $product->display_name }}</span>
              <span class="top-stat"><b>{{ $product->views_count }}</b><i>views</i></span>
            </a>
          @empty
            <div class="top-empty">
              <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <p>No farmer views yet. When farmers open your product pages, they'll be ranked here.</p>
            </div>
          @endforelse
        </div>
      </div>

      <!-- MOST QUOTED -->
      <div class="dash-panel">
        <div class="panel-head">
          <div class="top-panel-head">
            <span class="top-ic" style="background:#f3e8ff;color:#7c3aed;"><i data-lucide="file-text" width="17" height="17"></i></span>
            <div><h3>Most Quoted</h3><p>Products requested in generated quotations</p></div>
          </div>
        </div>
        <div class="panel-body">
          @forelse($topQuoted as $idx => $row)
            @php $product = $row->product; @endphp
            <a class="top-row" href="{{ route('store.products.show', $product->id) }}">
              <span class="top-rank">#{{ $idx + 1 }}</span>
              @if($product->mainImage && !empty($product->mainImage->file_path))
                <img class="top-thumb" src="{{ $product->mainImage->thumb_url }}" alt="{{ $product->display_name }}" loading="lazy">
              @else
                <span class="top-thumb"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span>
              @endif
              <span class="top-name">{{ $product->display_name }}</span>
              <span class="top-stat"><b>{{ $row->count }}</b><i>quotes</i></span>
            </a>
          @empty
            <div class="top-empty">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
              <p>No quotations generated yet. Products farmers request will be ranked here.</p>
            </div>
          @endforelse
        </div>
      </div>
    </section>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Render Lucide official icons
    if (window.lucide) lucide.createIcons();

    // Reveal on scroll
    var revealEls = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window) {
      var ro = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            ro.unobserve(entry.target);
          }
        });
      }, { threshold: 0.08 });
      revealEls.forEach(function (el) { ro.observe(el); });
    } else {
      revealEls.forEach(function (el) { el.classList.add('in'); });
    }
  });
</script>
@endsection
