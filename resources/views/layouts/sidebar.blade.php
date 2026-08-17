<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title') | Aquilala</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar { width: 0; height: 0; display: none; }
    body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; background: #f3f6f4; color: #2c3530; display: flex; min-height: 100vh; }
    
    /* Layout Framework */
    .sidebar { width: 260px; background: #143d23; color: #ffffff; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; box-shadow: none; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .main-content { margin-left: 260px; flex: 1; padding: 88px 32px 32px; min-width: 0; }

    /* Shared overlay horizontal scrollbar (green draggable pill) */
    .app-scroll { overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; padding-bottom: 52px; }
    .app-scroll::-webkit-scrollbar { display: none; }
    .app-scroll-wrap { position: relative; }
    .app-scrollbar {
      position: absolute; left: 40px; right: 40px; bottom: var(--sb-bottom, 18px); height: 10px;
      display: none; cursor: grab; touch-action: none;
    }
    .app-scrollbar.dragging { cursor: grabbing; }
    .app-scrollbar-track {
      position: absolute; left: 0; right: 0; top: 2px; bottom: 2px;
      background: rgba(20,61,35,0.07); border-radius: 999px;
    }
    .app-scrollbar-thumb {
      position: absolute; top: 0; bottom: 0; left: 0; border-radius: 999px;
      background: linear-gradient(90deg, #8fcf98, #6ab375);
      box-shadow: 0 1px 4px rgba(20,61,35,0.25);
      min-width: 48px; max-width: 42%; transition: background 0.15s ease;
    }
    .app-scrollbar-thumb:hover { background: linear-gradient(90deg, #6ab375, #559a61); }
    .app-scrollbar.dragging .app-scrollbar-thumb { background: linear-gradient(90deg, #559a61, #43824f); }
    
    /* Brand Section */
    .brand-section { padding: 20px; font-size: 14px; font-weight: 600; color: rgba(255, 255, 255, 0.95); letter-spacing: -0.01em; line-height: 1.35; border-bottom: 1px solid rgba(255, 255, 255, 0.08); display: flex; flex-direction: column; }
    .brand-title { font-size: 18px; font-weight: 800; letter-spacing: -0.01em; display: block; margin-bottom: 2px; color: #ffffff; }
    .brand-accent { color: #6aeb2f; }

    /* Season Widget Icon */
    .season-icon { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0; }
    .season-icon svg { width: 17px; height: 17px; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
    .season-icon-wet { background: rgba(14, 165, 233, 0.16); border: 1px solid rgba(56, 189, 248, 0.35); }
    .season-icon-wet svg { stroke: #38bdf8; }
    .season-icon-dry { background: rgba(251, 191, 36, 0.16); border: 1px solid rgba(251, 191, 36, 0.35); }
    .season-icon-dry svg { stroke: #fbbf24; }

    /* Climate widget card — compensated for nav-body's asymmetric padding (14px left / 0 right) */
    .climate-card { margin: 4px 22px 20px 8px; }
    @media (max-width: 768px) {
      .climate-card { margin: 4px 8px 20px 8px; }
    }
    
    /* Navigation Body */
    .nav-body { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 20px 0 20px 14px; scrollbar-width: none; }
    .nav-body::-webkit-scrollbar { display: none; }
    .menu-group-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #87a992; font-weight: 700; margin: 16px 0 8px 8px; }
    .nav-links { list-style: none; overflow: visible; }
    .nav-links li { margin-bottom: 4px; position: relative; overflow: visible; }
    
    .nav-links li a { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: #b9cfc0; text-decoration: none; border-top-left-radius: 12px; border-bottom-left-radius: 12px; border-top-right-radius: 0px; border-bottom-right-radius: 0px; font-size: 14px; font-weight: 600; transition: color 0.2s ease, background 0.2s ease, margin 0.2s ease; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-links li a svg { width: 18px; height: 18px; stroke: #b9cfc0; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: stroke 0.2s ease; flex-shrink: 0; }
    .nav-links li a:hover:not(.active) { background: rgba(255, 255, 255, 0.05); color: #ffffff; margin-right: 14px; border-radius: 12px; }
    .nav-links li a:hover:not(.active) svg { stroke: #ffffff; }

    /* Continuous Active Hook */
    .nav-links li a.active { background: #f3f6f4; color: #143d23; font-weight: 700; position: relative; margin-right: -1px; z-index: 10; }
    .nav-links li a.active svg { stroke: #143d23; }
    .nav-links li:has(a.active) { z-index: 12; }
    .nav-links li:has(a.active)::before { content: ""; position: absolute; top: -20px; right: 0; height: 20px; width: 20px; background-color: transparent; border-bottom-right-radius: 20px; box-shadow: 10px 10px 0 10px #f3f6f4; pointer-events: none; z-index: 12; }
    .nav-links li:has(a.active)::after { content: ""; position: absolute; bottom: -20px; right: 0; height: 20px; width: 20px; background-color: transparent; border-top-right-radius: 20px; box-shadow: 10px -10px 0 10px #f3f6f4; pointer-events: none; z-index: 12; }

    /* Footer badge and logout */
    .sidebar-footer { padding: 16px; background: #0d2917; border-top: 1px solid rgba(255, 255, 255, 0.08); position: relative; z-index: 110; }
    .user-profile-badge { display: flex; align-items: center; gap: 12px; padding: 4px 8px 16px 8px; margin-bottom: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
    .avatar { width: 36px; height: 36px; background: #1b4d2e; color: #c8e6c9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; text-transform: uppercase; }
    .user-info { display: flex; flex-direction: column; min-width: 0; }
    .user-name { font-size: 14px; font-weight: 600; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-role { font-size: 11px; color: #87a992; text-transform: capitalize; }
    .logout-btn { background: rgba(239, 68, 68, 0.08); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.15); padding: 11px 16px; width: 100%; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: flex-start; gap: 12px; transition: all 0.2s ease; }
    .logout-btn svg { width: 16px; height: 16px; stroke: #fca5a5; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .logout-btn:hover { background: #ef4444; color: #ffffff; border-color: #ef4444; }

    .mobile-header { display: none; background: #143d23; color: white; padding: 14px 16px; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; right: 0; z-index: 90; }
    .burger-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; line-height: 1; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; }
    .burger-btn:active { background: rgba(255, 255, 255, 0.08); }

    .desktop-notif-wrap { position: fixed; top: 14px; right: 20px; z-index: 200; transition: transform 0.3s ease; }
    .desktop-notif-wrap.hidden { transform: translateY(-80px); }
    .desktop-notif {
      width: 40px; height: 40px; border-radius: 50%;
      background: #ffffff; border: 1px solid #eef1f4;
      box-shadow: 0 2px 10px rgba(16, 24, 40, 0.10);
      display: flex; align-items: center; justify-content: center;
      color: #6d7d46; text-decoration: none; cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .desktop-notif:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(16, 24, 40, 0.14); }
    .desktop-notif svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .notif-dropdown {
      position: absolute; top: 48px; right: 0; width: 300px; max-width: calc(100vw - 40px);
      background: #ffffff; border: 1px solid #eef1f4; border-radius: 14px;
      box-shadow: 0 12px 32px rgba(16, 24, 40, 0.12);
      opacity: 0; visibility: hidden; transform: translateY(-6px);
      transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
      overflow: hidden;
    }
    .notif-dropdown.open { opacity: 1; visibility: visible; transform: translateY(0); }
    .notif-head { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
    .notif-head b { font-size: 13px; color: #0f172a; }
    .notif-head a { font-size: 12px; font-weight: 700; color: #15803d; text-decoration: none; }
    .notif-body { padding: 14px 16px; }
    .notif-empty { text-align: center; padding: 18px 8px; }
    .notif-empty svg { width: 30px; height: 30px; stroke: #cbd5e1; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
    .notif-empty p { font-size: 13px; color: #94a3b8; margin: 8px 0 0 0; }

    @media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .main-content { margin-left: 0; padding: 88px 16px 24px 16px; }
  .mobile-header { display: flex; }
  .sidebar-overlay { display: none; position: fixed; top: 0; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.4); z-index: 95; }
  .sidebar-overlay.open { display: block; }

  .desktop-notif { background: transparent; border: none; box-shadow: none; color: #ffffff; }
  .desktop-notif:hover { transform: none; box-shadow: none; background: rgba(255, 255, 255, 0.08); }

  /* --- UPDATE HERE --- */
  /* 1. Add right padding to nav-body so space matches the left (14px on both sides) */
  .nav-body {
    padding-right: 14px;
  }

  /* 2. Standardize mobile pill buttons */
  .nav-links li a,
  .nav-links li a.active {
    border-radius: 12px;
  }
  .nav-links li a.active {
    margin-right: 0;
  }
  .nav-links li:has(a.active)::before,
  .nav-links li:has(a.active)::after {
    display: none;
  }
}
  </style>
</head>
<body>

  <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

  <header class="mobile-header">
    <div style="display: flex; align-items: center; gap: 4px;">
      <button class="burger-btn" onclick="toggleSidebar()" aria-label="Open menu">☰</button>
      <div style="font-weight: 700; font-size: 18px; line-height: 1;">
        Aqui<span style="color: #6aeb2f; font-weight: 800;">lala</span>
      </div>
    </div>
  </header>

  <aside class="sidebar" id="sidebar">
    <div class="brand-section">
      <span class="brand-title">Aqui<span class="brand-accent">lala</span></span>
      Irrigators Association Inc.
    </div>

    <div class="nav-body">
      <!-- Live Season/Climate Widget -->
@php 
  $climate = $globalClimateInfo ?? (class_exists('\App\Helpers\SeasonHelper') ? \App\Helpers\SeasonHelper::getClimateInfo() : ['season' => 'dry', 'label' => 'Dry']); 
  $isWet = ($climate['season'] ?? 'dry') === 'wet';
  
  $weather = class_exists('\App\Services\WeatherService') ? \App\Services\WeatherService::getCurrentWeather() : ['condition' => 'Partly Cloudy', 'temperature' => '28'];
@endphp

<div class="climate-card" style="padding: 14px 14px 13px 14px; border-radius: 12px; background: linear-gradient(160deg, rgba(255,255,255,0.07), rgba(255,255,255,0.02)); border: 1px solid rgba(255,255,255,0.09); box-shadow: inset 0 1px 0 rgba(255,255,255,0.05);">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 11px; border-bottom: 1px dashed rgba(255,255,255,0.10);">
    <span id="today-date" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #87a992; font-weight: 700;"></span>
    <span id="live-time" style="font-size: 11px; color: #b9cfc0; font-family: ui-monospace, monospace; font-weight: 600;"></span>
  </div>
  <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center;">
    <span class="season-icon {{ $isWet ? 'season-icon-wet' : 'season-icon-dry' }}">
      @if($isWet)
        <svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/></svg>
      @else
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
      @endif
    </span>
    <div style="width: 100%; min-width: 0;">
      <div style="font-size: 13px; font-weight: 800; color: #ffffff; letter-spacing: -0.01em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $climate['label'] ?? 'Dry Season' }}</div>
      <div style="font-size: 11px; color: #87a992; margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $weather['condition'] ?? 'Partly Cloudy' }}</div>
    </div>
    <div style="display: flex; align-items: baseline; gap: 6px;">
      <span style="font-size: 16px; font-weight: 800; color: #ffffff; font-family: ui-monospace, monospace; letter-spacing: -0.02em; line-height: 1;">{{ $weather['temperature'] ?? '28' }}°C</span>
      <span style="font-size: 9px; color: #87a992; text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700;">{{ $climate['months'] ?? ($isWet ? 'June – October' : 'November – May') }}</span>
    </div>
  </div>
</div>


      <div class="menu-group-title">
        @if(auth()->user()->role === 'farmer') Farmer Hub @elseif(auth()->user()->role === 'store_owner') My Store @else Admin Control @endif
      </div>
      <ul class="nav-links">
        @if(auth()->user()->role === 'farmer')
          <li>
            <a href="{{ route('dashboard.farmer') }}" class="{{ request()->routeIs('dashboard.farmer') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
              Dashboard
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.crops.index') }}" class="{{ request()->is('farmer/crops*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
              My Crops
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.recommendations.index') }}" class="{{ request()->is('farmer/recommendations*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              Recommendations
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.products.browse') }}" class="{{ request()->is('farmer/products/browse*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              Browse Products
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.quotations.request') }}" class="{{ request()->is('farmer/quotations/request*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              Request Quotation
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.quotations.index') }}" class="{{ request()->routeIs('farmer.quotations.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              My Quotations
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.stores.index') }}" class="{{ request()->is('farmer/stores*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Stores Directory
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.notifications.index') }}" class="{{ request()->is('farmer/notifications*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              Notifications
            </a>
          </li>

        @elseif(auth()->user()->role === 'store_owner')
          <li>
          <a href="{{ route('store.dashboard') }}" class="{{ request()->routeIs('store.dashboard') ? 'active' : '' }}">
            
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
              Store Dashboard
            </a>
          </li>
          <li>
            <a href="{{ route('store.inventory.index') }}" class="{{ request()->routeIs('store.inventory.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
              Inventory Management
            </a>
          </li>
          <li>
            <a href="{{ route('store.quotations.index') }}" class="{{ request()->routeIs('store.quotations.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              Cost Quotations
            </a>
          </li>
          
          
          <li>
  <a href="{{ route('store.storefront.index') }}" class="{{ request()->routeIs('store.storefront.index') ? 'active' : '' }}">
    <svg 
      xmlns="http://w3.org" 
      viewBox="0 0 24 24" 
      fill="none" 
      stroke="currentColor" 
      stroke-width="2" 
      stroke-linecap="round" 
      stroke-linejoin="round"
    >
      <!-- Main Building Structure -->
      <path d="M3 9v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9" />
      
      <!-- Commercial Awning / Canopy -->
      <path d="M2 9h20l-1-4H3L2 9z" />
      <path d="M7 9V5" />
      <path d="M12 9V5" />
      <path d="M17 9V5" />
      
      <!-- Storefront Window & Door -->
      <path d="M6 14h4v4H6v-4z" />
      <path d="M14 14h4v8h-4v-8z" />
    </svg>
    My Store
  </a>
</li>


          <li>
            <a href="{{ route('store.formulas.index') }}" class="{{ request()->is('store/formulas*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              Input Formulas
            </a>
          </li>

        @elseif(auth()->user()->role === 'admin')
          <li>
            <a href="{{ route('dashboard.admin') }}" class="{{ request()->routeIs('dashboard.admin') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
              Admin Dashboard
            </a>
          </li>
          <li>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Users
            </a>
          </li>
          <li>
            <a href="{{ route('admin.approvals.index') }}" class="{{ request()->routeIs('admin.approvals.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              Approvals
            </a>
          </li>
          <li>
            <a href="{{ route('admin.quotations.index') }}" class="{{ request()->routeIs('admin.quotations.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              Quotations
            </a>
          </li>
          <li>
            <a href="{{ route('admin.analytics.index') }}" class="{{ request()->routeIs('admin.analytics.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              Analytics
            </a>
          </li>
          <li>
            <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Settings
            </a>
          </li>
          <li>
            <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Audit
            </a>
          </li>
        @endif

        @yield('nav-links')
      </ul>
    </div>

    <div class="sidebar-footer">
      <div class="user-profile-badge">
        <div class="avatar">
          @php
            $words = explode(' ', trim(auth()->user()->name));
            if (count($words) >= 2) {
                echo mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1);
            } else {
                echo mb_substr(auth()->user()->name, 0, 2);
            }
          @endphp
        </div>
        <div class="user-info">
          <span class="user-name">{{ auth()->user()->name }}</span>
          <span class="user-role">{{ str_replace('_', ' ', auth()->user()->role) }}</span>
        </div>
      </div>
      
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="logout-btn">
          <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          Log Out
        </button>
      </form>
    </div>
  </aside>

  <main class="main-content">
    @yield('content')
  </main>

  <div class="desktop-notif-wrap">
    <button class="desktop-notif" id="desktopNotifBtn" aria-label="Notifications" title="Notifications">
      <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    </button>
    <div class="notif-dropdown" id="notifDropdown">
      <div class="notif-head">
        <b>Notifications</b>
        @if(auth()->user()->role === 'farmer')
          <a href="{{ route('farmer.notifications.index') }}">View all</a>
        @endif
      </div>
      <div class="notif-body">
        <div class="notif-empty">
          <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <p>No new notifications</p>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Apply dynamic styles from data-inline-style (keeps Blade interpolation out of style="" attributes)
    function applyDataInlineStyles() {
      document.querySelectorAll('[data-inline-style]').forEach(function (el) {
        el.style.cssText = el.getAttribute('data-inline-style');
      });
      // For elements whose width is animated by JS (bars), only set the --bg custom property
      document.querySelectorAll('[data-bg]').forEach(function (el) {
        el.style.setProperty('--bg', el.getAttribute('data-bg'));
      });
    }
    applyDataInlineStyles();
    document.addEventListener('DOMContentLoaded', applyDataInlineStyles);

    // Shared overlay scrollbar pill (green) — wires every .app-scrollbar to its sibling .app-scroll
    function initAppScrollbars() {
      document.querySelectorAll('.app-scrollbar').forEach(function (bar) {
        if (bar.dataset.ready) return;
        bar.dataset.ready = '1';
        var wrap = bar.parentElement;
        var scroller = wrap ? wrap.querySelector('.app-scroll') : null;
        if (!scroller) return;
        var thumb = bar.querySelector('.app-scrollbar-thumb');
        if (!thumb) return;

        function update() {
          var sw = scroller.scrollWidth;
          var cw = scroller.clientWidth;
          var canScroll = sw > cw + 1;
          bar.style.display = canScroll ? 'block' : 'none';
          if (!canScroll) return;
          var bw = bar.clientWidth;
          var tw = Math.max(48, Math.min(bw * 0.42, (cw / sw) * bw * 0.72));
          thumb.style.width = tw + 'px';
          var maxScroll = sw - cw;
          var maxLeft = bw - tw;
          thumb.style.left = maxScroll > 0 ? ((scroller.scrollLeft / maxScroll) * maxLeft) + 'px' : '0px';
        }

        scroller.addEventListener('scroll', update);
        window.addEventListener('resize', update);

        var dragging = false;
        var startX = 0;
        var startLeft = 0;
        thumb.addEventListener('mousedown', function (e) {
          dragging = true;
          startX = e.clientX;
          startLeft = parseFloat(thumb.style.left) || 0;
          bar.classList.add('dragging');
          e.preventDefault();
        });
        document.addEventListener('mousemove', function (e) {
          if (!dragging) return;
          var bw = bar.clientWidth;
          var tw = thumb.offsetWidth;
          var maxLeft = bw - tw;
          var left = Math.max(0, Math.min(maxLeft, startLeft + (e.clientX - startX)));
          var maxScroll = scroller.scrollWidth - scroller.clientWidth;
          scroller.scrollLeft = maxLeft > 0 ? (left / maxLeft) * maxScroll : 0;
        });
        document.addEventListener('mouseup', function () {
          dragging = false;
          bar.classList.remove('dragging');
        });

        update();
      });
    }
    initAppScrollbars();
    document.addEventListener('DOMContentLoaded', initAppScrollbars);

    // Live date + time clock — updates every second
    function updateLiveTime() {
      const now = new Date();
      const dateEl = document.getElementById('today-date');
      const timeEl = document.getElementById('live-time');
      if (dateEl) {
        dateEl.textContent = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric' }).format(now);
      }
      if (timeEl) {
        timeEl.textContent = new Intl.DateTimeFormat('en-PH', { timeZone: 'Asia/Manila', hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(now);
      }
    }
updateLiveTime();
    setInterval(updateLiveTime, 1000);

    // Auto-scroll the active nav link into the center of the scrollable sidebar
    function centerActiveNav() {
      const navBody = document.querySelector('.nav-body');
      const activeLink = document.querySelector('.nav-links li a.active');
      if (!navBody || !activeLink) return;

      const navRect = navBody.getBoundingClientRect();
      const linkRect = activeLink.getBoundingClientRect();

      // If the active link is already fully visible, do nothing
      const isVisible =
        linkRect.top >= navRect.top &&
        linkRect.bottom <= navRect.bottom;
      if (isVisible) return;

      // Center the active link within the scrollable area
      const linkCenter = linkRect.top - navRect.top + activeLink.offsetHeight / 2;
      const scrollTarget = navBody.scrollTop + linkCenter - navRect.height / 2;
      navBody.scrollTo({ top: Math.max(0, scrollTarget), behavior: 'smooth' });
    }

    // Run on page load (after full render) and after the sidebar opens on mobile
    window.addEventListener('load', centerActiveNav);
    document.addEventListener('DOMContentLoaded', centerActiveNav);
    setTimeout(centerActiveNav, 100);
    setTimeout(centerActiveNav, 350);

    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('overlay').classList.toggle('open');
      // Re-center when the mobile sidebar is opened
      setTimeout(centerActiveNav, 250);
    }

    // Desktop notification bell toggle
    document.addEventListener('DOMContentLoaded', function () {
      var btn = document.getElementById('desktopNotifBtn');
      var dropdown = document.getElementById('notifDropdown');
      var wrap = document.querySelector('.desktop-notif-wrap');
      if (!btn || !dropdown || !wrap) return;
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
      });
      document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
          dropdown.classList.remove('open');
        }
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') dropdown.classList.remove('open');
      });

      // Hide on scroll down, show on scroll up (desktop only)
      var lastY = window.scrollY;
      window.addEventListener('scroll', function () {
        var y = window.scrollY;
        if (window.innerWidth > 768) {
          if (y > lastY && y > 80) {
            wrap.classList.add('hidden');
            dropdown.classList.remove('open');
          } else if (y < lastY || y <= 80) {
            wrap.classList.remove('hidden');
          }
        }
        lastY = y;
      }, { passive: true });
    });
  </script>
</body>
</html>