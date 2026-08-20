@extends('layouts.sidebar')

@section('title', 'Admin Dashboard')

@section('content')
@php
  $user = auth()->user();
  $now = now();
  $hour = (int) $now->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<style>
  .dash-page { position: relative; }
  .dash-page::before,
  .dash-page::after {
    content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
  }
  .dash-page::before { width: 360px; height: 360px; background: rgba(16,185,129,0.18); top: -90px; right: -90px; }
  .dash-page::after { width: 300px; height: 300px; background: rgba(59,130,246,0.15); bottom: -70px; left: -70px; }
  .dash-content { position: relative; z-index: 1; }

  .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
  .reveal.in { opacity: 1; transform: translateY(0); }

  .hero-band {
    background: rgb(230, 245, 195);
    border-radius: 22px; padding: 30px 32px; color: #143d23; position: relative;
    box-shadow: 0 4px 16px rgba(20,61,35,0.10); margin-bottom: 26px;
  }
  .hero-inner { display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; }
  .hero-eyebrow { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #166534; margin-bottom: 6px; }
  .hero-title { font-size: 28px; font-weight: 800; margin: 0; letter-spacing: -0.01em; color: #14532d; }
  .hero-sub { font-size: 13px; color: #40632d; margin: 6px 0 0 0; }
  .hero-sub strong { color: #14532d; }

  .btn-hero {
    display: inline-flex; align-items: center; gap: 8px;
    background: #ffffff; border: 1px solid rgba(20,61,35,0.10);
    color: #14532d; padding: 11px 20px; border-radius: 12px; font-size: 13px; font-weight: 700;
    text-decoration: none; transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(20,61,35,0.08);
  }
  .btn-hero:hover { background: #f0fdf4; transform: translateY(-1px); }
  .btn-hero svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
  .hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }

  .dash-panel {
    background: rgba(255,255,255,0.84); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.5); border-radius: 18px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    transition: all 0.35s cubic-bezier(0.4,0,0.2,1); overflow: hidden;
  }
  .dash-panel:hover { box-shadow: 0 16px 48px rgba(0,0,0,0.09); }
  .panel-head { padding: 18px 22px; border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; gap: 12px; }
  .panel-head h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.03em; }
  .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
  .panel-body { padding: 18px 22px; }

  .btn-ghost {
    font-size: 12px; font-weight: 700; color: #143d23; text-decoration: none;
    padding: 7px 14px; border-radius: 9px; background: rgba(20,61,35,0.07);
    display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
    transition: all 0.2s ease;
  }
  .btn-ghost:hover { background: rgba(20,61,35,0.13); }
  .btn-ghost svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  /* Dashboard grid */
  .dash-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 22px; align-items: start; }
  .dash-grid.equal { grid-template-columns: repeat(2, minmax(0, 1fr)); }

  /* Stock pills */
  .stock-pill { display: inline-block; padding: 3px 8px; border-radius: 7px; font-size: 10px; font-weight: 700; }
  .stock-ok { background: #f0fdf4; color: #16a34a; }
  .stock-low { background: #fef3c7; color: #d97706; }
  .stock-out { background: #f1f5f9; color: #64748b; }

  /* Table */
  .admin-table { width: 100%; border-collapse: collapse; text-align: left; }
  .admin-table thead tr { background: #f6f8f7; }
  .admin-table th { padding: 11px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
  .admin-table td { padding: 12px 16px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
  .admin-table tbody tr { transition: background 0.15s ease; }
  .admin-table tbody tr:hover { background: #fafbfa; }
  .admin-table tbody tr:last-child td { border-bottom: none; }
  .table-avatar { width: 34px; height: 34px; border-radius: 10px; background: rgb(224, 235, 170); color: #49a750; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; text-transform: uppercase; flex-shrink: 0; }
  .role-badge { display: inline-block; padding: 4px 10px; border-radius: 7px; font-size: 11px; font-weight: 700; }
  .role-admin { background: #e0f2fe; color: #0369a1; }
  .role-owner { background: #fef3c7; color: #d97706; }
  .role-farmer { background: #dcfce7; color: #15803d; }

  /* Timeline */
  .qt-item { position: relative; padding: 13px 0 13px 24px; border-bottom: 1px solid rgba(0,0,0,0.045); }
  .qt-item:last-child { border-bottom: none; }
  .qt-item:hover { transform: translateX(4px); }
  .qt-dot { position: absolute; left: 0; top: 18px; width: 12px; height: 12px; border-radius: 50%; border: 2.5px solid; background: #fff; }

  /* Product rows */
  .prod-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; }
  .prod-row + .prod-row { border-top: 1px solid rgba(0,0,0,0.045); }
  .prod-thumb { width: 42px; height: 42px; border-radius: 11px; background: #f4f7f5; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .prod-thumb img { width: 100%; height: 100%; object-fit: cover; }
  .prod-thumb svg { width: 20px; height: 20px; stroke: #94a3b8; }

  @media (max-width: 980px) {
    .dash-grid, .dash-grid.equal { grid-template-columns: 1fr; }
  }

  @media (max-width: 768px) {
    .hero-band { padding: 22px; }
    .hero-title { font-size: 21px; }
  }
</style>

<div class="dash-page">
  <div class="dash-content">

    <!-- 1. HERO -->
    <section class="hero-band reveal in">
      <div class="hero-inner">
        <div>
          <div class="hero-eyebrow">System Administration</div>
          <h1 class="hero-title"><span data-greeting>{{ $greeting }}</span>, {{ $user->name }}</h1>
          <p class="hero-sub">Here's your <strong>platform overview</strong> for {{ $now->format('l, F j, Y') }}.</p>
        </div>
        <div class="hero-actions">
          <a href="{{ route('admin.users.index') }}" class="btn-hero">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Manage Users
          </a>
          <a href="{{ route('admin.approvals.index') }}" class="btn-hero">
            <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Approvals
          </a>
        </div>
      </div>
    </section>

    <!-- 2. KPI CARDS -->
    <section style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:22px;">
      <x-kpi-card title="Total Users" value="{{ $totalUsers }}" trendValue="{{ $farmersCount }} farmers" trendDirection="{{ $totalUsers > 0 ? 'up' : 'neutral' }}" trendLabel="registered accounts" accent="#2563eb" link="{{ route('admin.users.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Store Owners" value="{{ $storeOwnersCount }}" trendValue="{{ $activeProducts }} active" trendDirection="{{ $activeProducts > 0 ? 'up' : 'neutral' }}" trendLabel="products listed" accent="#d97706" link="{{ route('admin.users.index') }}">
        <x-slot:icon><img src="{{ asset('images/store.png') }}" alt="Store" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Quotations" value="{{ $totalQuotations }}" trendValue="{{ $pendingQuotations }} pending" trendDirection="{{ $pendingQuotations > 0 ? 'up' : 'neutral' }}" trendLabel="platform-wide" accent="#7c3aed" link="{{ route('admin.quotations.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></x-slot:icon>
      </x-kpi-card>
      <x-kpi-card title="Active Products" value="{{ $activeProducts }}" trendValue="{{ $outOfStockProducts }} out of stock" trendDirection="{{ $outOfStockProducts > 0 ? 'down' : 'up' }}" trendLabel="needs restocking" accent="#16a34a" link="{{ route('admin.analytics.index') }}">
        <x-slot:icon><img src="{{ asset('images/product.png') }}" alt="Product" width="22" height="22" style="object-fit:contain;"></x-slot:icon>
      </x-kpi-card>
    </section>

    <!-- 3. RECENT REGISTRATIONS -->
    <section style="margin-bottom:22px;">
      <!-- Recent registrations -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>Recent Registrations</h3>
            <p>Latest accounts created on the platform</p>
          </div>
          <a href="{{ route('admin.users.index') }}" class="btn-ghost">View All <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
          <div class="app-scroll">
            @if($recentUsers->isNotEmpty())
            <table class="admin-table">
              <thead>
                <tr><th>User</th><th>Role</th><th>Joined</th></tr>
              </thead>
              <tbody>
                @foreach($recentUsers as $ru)
                  <tr>
                    <td>
                      <div style="display:flex;align-items:center;gap:11px;">
                        <div class="table-avatar">
                          <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div>
                          <div style="font-weight:600;color:#1e2922;">{{ $ru->name }}</div>
                          <div style="font-size:11px;color:#94a3b8;">{{ $ru->email }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      @if($ru->role === 'admin')
                        <span class="role-badge role-admin">Admin</span>
                      @elseif($ru->role === 'store_owner')
                        <span class="role-badge role-owner">Store Owner</span>
                      @else
                        <span class="role-badge role-farmer">Farmer</span>
                      @endif
                    </td>
                    <td style="color:#718096;font-size:12px;">{{ $ru->created_at->diffForHumans() }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @else
            <div class="panel-body" style="text-align:center;padding:34px 16px;">
              <svg viewBox="0 0 24 24" width="34" height="34" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
              <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No platform registrations yet.</p>
            </div>
          @endif
          </div>
        </div>
      </div>
    </section>

    <!-- 4. MARKETPLACE + QUOTATION ACTIVITY -->
    <section class="dash-grid equal">
      <!-- Marketplace listings -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>Marketplace Listings</h3>
            <p>Most recent products across stores</p>
          </div>
        </div>
        <div class="panel-body">
          @if($recentProducts->isNotEmpty())
            @foreach($recentProducts as $prod)
              @php
                $prodStatusStyle = 'font-size:10px;font-weight:700;color:' . ($prod->status === 'active' ? '#16a34a' : '#d97706') . ';';
                $pQty = (int) ($prod->stock_quantity ?? 0);
                $pTh = (int) ($prod->reorder_threshold ?? 5);
                $pStockClass = $pQty <= 0 ? 'stock-out' : ($pQty <= $pTh ? 'stock-low' : 'stock-ok');
                $pStockLabel = $pQty <= 0 ? 'Out of stock' : ($pQty <= $pTh ? 'Low stock' : 'In stock');
              @endphp
              <div class="prod-row">
                <div class="prod-thumb">
                  @if($prod->mainImage && !empty($prod->mainImage->file_path))
                    <img src="{{ asset('storage/' . $prod->mainImage->file_path) }}" alt="{{ $prod->name }}" loading="lazy">
                  @else
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  @endif
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:13px;font-weight:700;color:#1e2922;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $prod->name }}</div>
                  <div style="font-size:11px;color:#94a3b8;">{{ $prod->storeOwner->store_name ?? $prod->storeOwner->name ?? 'Unknown store' }}</div>
                </div>
                <div style="text-align:right;">
                  <div style="font-size:13px;font-weight:800;color:#0f172a;">₱{{ number_format($prod->price, 2) }}</div>
                  <div style="margin-top:4px;display:flex;gap:5px;justify-content:flex-end;flex-wrap:wrap;">
                    <span class="stock-pill {{ $pStockClass }}">{{ $pStockLabel }}</span>
                    <span data-inline-style="{{ $prodStatusStyle }}">{{ ucwords(str_replace('_', ' ', $prod->status)) }}</span>
                  </div>
                </div>
              </div>
            @endforeach
          @else
            <div style="text-align:center;padding:34px 16px;">
              <svg viewBox="0 0 24 24" width="34" height="34" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              <p style="font-size:13px;color:#64748b;margin:8px 0 0 0;">No products listed yet.</p>
            </div>
          @endif
        </div>
      </div>

      <!-- Quotation activity -->
      <div class="dash-panel reveal" data-reveal>
        <div class="panel-head">
          <div>
            <h3>Quotation Activity</h3>
            <p>Recent requests across the entire platform</p>
          </div>
          <a href="{{ route('admin.quotations.index') }}" class="btn-ghost">View All <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="panel-body">
          @if($recentPlatformQuotations->isNotEmpty())
            <div style="position:relative;column-count:1;">
              @foreach($recentPlatformQuotations as $qt)
                @php
                  $qtDotStyle = 'border-color:' . $qt->status_color . ';';
                  $qtBadgeStyle = 'font-size:11px;font-weight:700;padding:3px 9px;border-radius:7px;background:' . $qt->status_bg . ';color:' . $qt->status_color . ';';
                @endphp
                <div class="qt-item" style="break-inside:avoid;">
                  <div class="qt-dot" data-inline-style="{{ $qtDotStyle }}"></div>
                  @if(!$loop->last)
                    <div style="position:absolute;left:5px;top:30px;bottom:-16px;width:2px;background:rgba(0,0,0,0.06);"></div>
                  @endif
                  <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                    <span style="font-weight:700;font-size:12px;color:#0f172a;font-family:monospace;">{{ $qt->quotation_number }}</span>
                    <span data-inline-style="{{ $qtBadgeStyle }}">{{ $qt->status_label }}</span>
                  </div>
                  <div style="font-size:12px;color:#64748b;margin-top:4px;line-height:1.4;">
                    <strong>{{ $qt->crop_type }}</strong> · {{ ucfirst($qt->season) }} Season · {{ $qt->land_area }} ha
                    @if($qt->farmer)
                      <br><svg viewBox="0 0 24 24" width="12" height="12" stroke="#64748b" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> {{ $qt->farmer->name }}
                      @if($qt->storeOwner)
                        → <svg viewBox="0 0 24 24" width="12" height="12" stroke="#64748b" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:2px;"><path d="M3 9l1.5-4.5h15L21 9"/><path d="M3 9v10a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V9"/><path d="M3 9h18"/><path d="M6 14h4v4H6z"/><path d="M14 14h4v6h-4z"/></svg> {{ $qt->storeOwner->store_name ?? $qt->storeOwner->name }}
                      @endif
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <div style="text-align:center;padding:36px 16px;">
              <svg viewBox="0 0 24 24" width="36" height="36" stroke="#94a3b8" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
              <p style="font-size:13px;color:#64748b;margin:10px 0 0 0;">No quotation activity yet.</p>
            </div>
          @endif
        </div>
      </div>
    </section>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    function setGreeting() {
      var h = new Date().getHours();
      var el = document.querySelector('[data-greeting]');
      if (!el) return;
      el.textContent = h < 12 ? 'Good morning' : (h < 18 ? 'Good afternoon' : 'Good evening');
    }
    setGreeting();
    setInterval(setGreeting, 60000);

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

    // Quotation pipeline fills
    document.querySelectorAll('.pipe-fill').forEach(function (fill, i) {
      if (fill.dataset.fill) fill.style.setProperty('--fill', fill.dataset.fill);
      setTimeout(function () {
        fill.style.width = (fill.dataset.w || '0') + '%';
      }, 250 + i * 90);
    });
  });
</script>
@endsection
