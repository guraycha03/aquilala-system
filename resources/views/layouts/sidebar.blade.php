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
    .sidebar { 
      width: 260px; 
      background: #143d23; /* Distinct rich agricultural field green */
      color: #ffffff; 
      display: flex; 
      flex-direction: column; 
      position: fixed; 
      top: 0; 
      bottom: 0; 
      left: 0; 
      z-index: 100; 
      box-shadow: none; 
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
    }
    
    .main-content { margin-left: 260px; flex: 1; padding: 32px; min-width: 0; }
    
    /* Brand Section */
    .brand-section { 
      padding: 20px; 
      font-size: 14px; 
      font-weight: 600; 
      color: rgba(255, 255, 255, 0.95); 
      letter-spacing: -0.01em;
      line-height: 1.35;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08); 
      display: flex;
      flex-direction: column;
    }
    .brand-title { font-size: 18px; font-weight: 800; letter-spacing: -0.01em; display: block; margin-bottom: 2px; color: #ffffff; }
    .brand-accent { color: #6aeb2f; }
    
    /* Navigation Body */
    .nav-body { 
      flex: 1; 
      overflow-y: auto; 
      overflow-x: hidden; 
      padding: 20px 0 20px 14px; 
    }
    .menu-group-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #87a992; font-weight: 700; margin: 16px 0 8px 8px; }
    .nav-links { list-style: none; overflow: visible; }
    
    .nav-links li { margin-bottom: 4px; position: relative; overflow: visible; }
    
    .nav-links li a { 
      display: flex; 
      align-items: center; 
      gap: 12px; 
      padding: 14px 16px; 
      color: #b9cfc0; 
      text-decoration: none; 
      border-top-left-radius: 12px; 
      border-bottom-left-radius: 12px; 
      border-top-right-radius: 0px;
      border-bottom-right-radius: 0px;
      font-size: 14px; 
      font-weight: 600; 
      transition: color 0.2s ease, background 0.2s ease; 
    }

    /* Icon Sizing Rule */
    .nav-links li a svg {
      width: 18px;
      height: 18px;
      stroke: #b9cfc0;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
      transition: stroke 0.2s ease;
    }

    .nav-links li a:hover:not(.active) { background: rgba(255, 255, 255, 0.05); color: #ffffff; margin-right: 14px; border-radius: 12px; }
    .nav-links li a:hover:not(.active) svg { stroke: #ffffff; }

    /* Continuous Canvas Seamless Active Hook */
    .nav-links li a.active { 
      background: #f3f6f4; 
      color: #143d23; 
      font-weight: 700;
      position: relative;
      margin-right: -1px; 
      z-index: 10;
    }
    .nav-links li a.active svg {
      stroke: #143d23;
    }

    .nav-links li:has(a.active) {
      z-index: 12;
    }

    /* Flawless Shadow-Based Top Curve */
    .nav-links li:has(a.active)::before {
      content: "";
      position: absolute;
      top: -20px; 
      right: 0;
      height: 20px; 
      width: 20px;  
      background-color: transparent;
      border-bottom-right-radius: 20px; /* Perfectly smooth native corner */
      box-shadow: 10px 10px 0 10px #f3f6f4; /* Fills the rest with your light background color */
      pointer-events: none;
      z-index: 12;
    }

    /* Flawless Shadow-Based Bottom Curve */
    .nav-links li:has(a.active)::after {
      content: "";
      position: absolute;
      bottom: -20px; 
      right: 0;
      height: 20px; 
      width: 20px;  
      background-color: transparent;
      border-top-right-radius: 20px; /* Perfectly smooth native corner */
      box-shadow: 10px -10px 0 10px #f3f6f4; /* Fills the rest with your light background color */
      pointer-events: none;
      z-index: 12;
    }

    /* Fixed Bottom Footer */
    .sidebar-footer { padding: 16px; background: #0d2917; border-top: 1px solid rgba(255, 255, 255, 0.08); position: relative; z-index: 110; }
    .user-profile-badge { display: flex; align-items: center; gap: 12px; padding: 4px 8px 16px 8px; margin-bottom: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
    .avatar { width: 36px; height: 36px; background: #1b4d2e; color: #c8e6c9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; text-transform: uppercase; }
    .user-info { display: flex; flex-direction: column; min-width: 0; }
    .user-name { font-size: 14px; font-weight: 600; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-role { font-size: 11px; color: #87a992; text-transform: capitalize; }
    
    .logout-btn { 
      background: rgba(239, 68, 68, 0.08); 
      color: #fca5a5; 
      border: 1px solid rgba(239, 68, 68, 0.15); 
      padding: 11px 16px; /* Increased side padding slightly */
      width: 100%; 
      border-radius: 8px; 
      font-size: 13px; 
      font-weight: 600; 
      cursor: pointer; 
      display: flex; 
      align-items: center; 
      justify-content: flex-start; /* Changed from center to left-aligned */
      gap: 12px; /* Matches the gap of your main nav links */
      transition: all 0.2s ease; 
    }
    .logout-btn svg { width: 16px; height: 16px; stroke: #fca5a5; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .logout-btn:hover { background: #ef4444; color: #ffffff; border-color: #ef4444; }
    .logout-btn:hover svg { stroke: #ffffff; }

    /* Mobile Header Context override */
    .mobile-header { display: none; background: #143d23; color: white; padding: 14px 16px; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; right: 0; z-index: 90; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .burger-btn { background: none; border: none; color: white; font-size: 24px; cursor: pointer; }

    /* Core Layout Panels */
    .header-panel { margin-bottom: 24px; }
    .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .card { background: white; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .card h3 { font-size: 13px; color: #606d64; margin-bottom: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
    .card .val { font-size: 28px; font-weight: 700; color: #1e2922; }
    
    .data-table-container { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
    .table-header { padding: 20px; font-weight: 700; border-bottom: 1px solid #e2e8f0; font-size: 16px; color: #232f27; }
    table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
    th { background: #fafbfa; padding: 14px; font-weight: 600; color: #4a574e; border-bottom: 1px solid #e2e8f0; }
    td { padding: 14px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; }
    
    .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; }
    .badge.admin { background: #e0f2fe; color: #0369a1; }
    .badge.owner { background: #fef3c7; color: #b45309; }
    .badge.farmer { background: #dcfce7; color: #15803d; }

    .status-alert { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500; }

    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); box-shadow: 4px 0 24px rgba(20, 61, 35, 0.15); } 
      .sidebar.open { transform: translateX(0); }
      .main-content { margin-left: 0; padding: 88px 16px 24px 16px; }
      .mobile-header { display: flex; }
      .sidebar-overlay { display: none; position: fixed; top: 0; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.4); z-index: 95; }
      .sidebar-overlay.open { display: block; }
      .nav-body { padding: 20px 14px; }
      .nav-links li a { border-radius: 12px; }
      .nav-links li:has(a.active)::before, .nav-links li:has(a.active)::after { display: none; }
    }
  </style>
</head>
<body>

  <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

  <!-- Fixed Code (Icon on the left) -->
  <header class="mobile-header" style="justify-content: flex-start; gap: 16px;">
    <button class="burger-btn" onclick="toggleSidebar()">☰</button>
    <div style="font-weight: 700; font-size: 18px; letter-spacing: -0.01em;">
      Aqui<span style="color: #6aeb2f; font-weight: 800;">lala</span>
    </div>
  </header>

  <aside class="sidebar" id="sidebar">
    <div class="brand-section">
      <span class="brand-title">Aqui<span class="brand-accent">lala</span></span>
      Irrigators Association Inc.
    </div>

    <div class="nav-body">
      <div class="menu-group-title">Workspace</div>
      <ul class="nav-links">
        @yield('nav-links')
      </ul>
    </div>

    <div class="sidebar-footer">
      <div class="user-profile-badge">
        <div class="avatar">
          {{ substr(auth()->user()->name, 0, 2) }}
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