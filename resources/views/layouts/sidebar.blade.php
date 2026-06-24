<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title') | Aquilala</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; background: #f3f6f4; color: #2c3530; display: flex; min-height: 100vh; }
    
    /* Layout Framework */
    .sidebar { width: 260px; background: #143d23; color: #ffffff; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; box-shadow: none; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .main-content { margin-left: 260px; flex: 1; padding: 32px; min-width: 0; }
    
    /* Brand Section */
    .brand-section { padding: 20px; font-size: 14px; font-weight: 600; color: rgba(255, 255, 255, 0.95); letter-spacing: -0.01em; line-height: 1.35; border-bottom: 1px solid rgba(255, 255, 255, 0.08); display: flex; flex-direction: column; }
    .brand-title { font-size: 18px; font-weight: 800; letter-spacing: -0.01em; display: block; margin-bottom: 2px; color: #ffffff; }
    .brand-accent { color: #6aeb2f; }
    
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
    .burger-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); } 
      .sidebar.open { transform: translateX(0); }
      .main-content { margin-left: 0; padding: 88px 16px 24px 16px; }
      .mobile-header { display: flex; }
      .sidebar-overlay { display: none; position: fixed; top: 0; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.4); z-index: 95; }
      .sidebar-overlay.open { display: block; }
    }
  </style>
</head>
<body>

  <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

  <header class="mobile-header">
    <button class="burger-btn" onclick="toggleSidebar()">☰</button>
    <div style="font-weight: 700; font-size: 18px;">
      Aqui<span style="color: #6aeb2f; font-weight: 800;">lala</span>
    </div>
  </header>

  <aside class="sidebar" id="sidebar">
    <div class="brand-section">
      <span class="brand-title">Aqui<span class="brand-accent">lala</span></span>
      Irrigators Association Inc.
    </div>

    <div class="nav-body">
      <div class="menu-group-title">
        @if(auth()->user()->role === 'farmer') Farmer Hub @elseif(auth()->user()->role === 'store_owner') Merchant Hub @else Admin Control @endif
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
              <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>
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
            <a href="{{ route('dashboard.store_owner') }}" class="{{ request()->routeIs('dashboard.store_owner') ? 'active' : '' }}">
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
              <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              Product Catalog front
            </a>
          </li>
          <li>
            <a href="{{ route('store.formulas.index') }}" class="{{ request()->routeIs('store.formulas.index') ? 'active' : '' }}">
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
            <a href="{{ route('admin.formulas.index') }}" class="{{ request()->routeIs('admin.formulas.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              Formulas
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

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('overlay').classList.toggle('open');
    }
  </script>
</body>
</html>