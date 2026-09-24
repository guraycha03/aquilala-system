<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title') | Aquilala</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@1.47.0/dist/umd/lucide.min.js"></script>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar { width: 0; height: 0; display: none; }
    body { font-family: 'DM Sans', ui-sans-serif, system-ui, -apple-system, sans-serif; background: #f3f6f4; color: #2c3530; display: flex; min-height: 100vh; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

    /* Global: consistent, readable letter-spacing on all validation / feedback text */
    .field-error, .field-feedback, .form-error, .form-error-message,
    .form-errors, .form-errors li, .form-errors ul, .alert-error, .alert-error li,
    [id$="-feedback"], [id$="-error"], [id$="Feedback"], [id$="Error"] {
      letter-spacing: 0.05em;
    }

    /* Global readability: readable letter-spacing across headings, labels and controls */
    h1, h2, h3, h4, h5, h6, button, select, th,
    label, .form-label, .menu-group-title, .user-role,
    .aqua-tab, .aqua-tabs {
      letter-spacing: 0.03em;
    }
    .nav-links li a, .nav-dropdown-btn, .sub-nav-links li a { letter-spacing: 0.02em; }

    /* Global select / dropdown styling — matches the Aquilala white + green theme */
    select {
      color-scheme: light;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      border: 1px solid #d1d5db;
      border-radius: 0.375rem;
      padding: 0.5rem 2.5rem 0.5rem 0.75rem;
      background-color: #ffffff;
      outline: none;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23143423' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
      background-position: right 0.5rem center;
      background-repeat: no-repeat;
      background-size: 1.5em 1.5em;
      font-family: inherit;
    }
    select:not([disabled]) { cursor: pointer; }
    select:focus {
      border-color: #16a34a;
      box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
    }

    /* Layout Framework */
    .sidebar { width: 260px; background: #143d23; color: #ffffff; display: flex; flex-direction: column; position: fixed; top: 0; bottom: 0; left: 0; z-index: 100; box-shadow: none; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .main-content { margin-left: 260px; flex: 1; padding: 64px 32px 32px; min-width: 0; }

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

    /* Weather Widget Icon (real condition icons from public/images/weather) */
    .season-icon { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; flex-shrink: 0; }
    .season-icon img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .weather-unavailable img { filter: grayscale(1); opacity: 0.45; }

    /* Climate widget card — one single card; compact row by default, expands in place on hover (desktop) or click/tap */
    .climate-card {
      margin: 4px 22px 20px 8px;
      cursor: pointer; user-select: none; -webkit-tap-highlight-color: transparent;
      padding: 10px 12px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--sb-card-bg-a, rgba(255,255,255,0.16)), var(--sb-card-bg-b, rgba(255,255,255,0.04)));
      border: 1px solid var(--sb-card-border, rgba(255,255,255,0.14));
      box-shadow: 0 6px 18px rgba(20, 40, 25, 0.12), var(--sb-card-shadow, inset 0 1px 0 rgba(255,255,255,0.08));
      -webkit-backdrop-filter: blur(8px);
      backdrop-filter: blur(8px);
      display: flex; flex-direction: column;
      transition: background 0.2s ease, border-color 0.2s ease;
    }
    @media (max-width: 768px) {
      .climate-card { margin: 4px 8px 20px 8px; }
    }
    .climate-card:focus-visible { outline: 2px solid #6aeb2f; outline-offset: 2px; }
    .climate-card:hover,
    .climate-card.pinned { background: linear-gradient(135deg, var(--sb-card-bg-a, rgba(255,255,255,0.22)), var(--sb-card-bg-b, rgba(255,255,255,0.06))); border-color: var(--sb-card-border-hover, rgba(255,255,255,0.26)); }
    .climate-row { display: flex; align-items: center; gap: 10px; min-height: 40px; }
    .climate-mini-info { flex: 1; min-width: 0; }
    .climate-mini-temp { font-size: 16px; font-weight: 800; color: var(--sb-fg); font-family: ui-monospace, monospace; letter-spacing: -0.02em; line-height: 1.1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .climate-mini-sub { font-size: 10px; color: var(--sb-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .climate-chevron { width: 16px; height: 16px; stroke: var(--sb-muted); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; transition: transform 0.25s ease; }
    .climate-card.pinned .climate-chevron { transform: rotate(180deg); }
    .climate-details {
      display: none; margin-top: 10px; padding-top: 11px;
      border-top: 1px dashed var(--sb-border-soft);
      animation: climateIn 0.2s ease;
    }
    .climate-card:hover .climate-details,
    .climate-card.pinned .climate-details { display: block; }
    .climate-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .climate-meta .climate-updated { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--sb-muted); font-weight: 700; }
    .climate-meta .climate-clock { font-size: 11px; color: var(--sb-fg); font-family: ui-monospace, monospace; font-weight: 600; }
    .climate-season { display: flex; align-items: center; gap: 10px; }
    .season-badge {
      display: flex; align-items: center; gap: 9px;
      width: 100%;
      padding: 7px 12px;
      border-radius: 10px;
      background: var(--sb-badge-bg);
      box-shadow: inset 0 1px 2px rgba(0,0,0,0.15);
      max-width: 100%; box-sizing: border-box;
    }
    .season-badge img { width: 20px; height: 20px; object-fit: contain; display: block; flex-shrink: 0; background: rgba(255, 255, 255, 0.55); border-radius: 50%; padding: 4px; box-sizing: content-box; box-shadow: 0 1px 3px rgba(0,0,0,0.25); }
    .climate-season-info { display: flex; flex-direction: column; min-width: 0; flex: 1; }
    .climate-season-label { display: block; font-size: 13px; font-weight: 800; color: var(--sb-fg); white-space: nowrap; }
    .climate-season-sub { display: block; font-size: 10px; color: var(--sb-muted); text-transform: uppercase; letter-spacing: 0.08em; font-weight: 700; margin-top: 2px; white-space: normal; line-height: 1.35; }
    @media (hover: none) {
      .climate-card:hover .climate-details { display: none; }
      .climate-card.pinned .climate-details, .climate-card.pinned .climate-chevron { display: block; }
    }
    @keyframes climateIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Navigation Body */
    .nav-body { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 20px 0 20px 14px; scrollbar-width: none; }
    .nav-body::-webkit-scrollbar { display: none; }
    .menu-group-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #87a992; font-weight: 700; margin: 16px 0 8px 8px; }
    .nav-links { list-style: none; overflow: visible; }
    .nav-links li { margin-bottom: 4px; position: relative; overflow: visible; }
    
    .nav-links li a { display: flex; align-items: center; gap: 12px; padding: 14px 16px; color: #b9cfc0; text-decoration: none; border-top-left-radius: 12px; border-bottom-left-radius: 12px; border-top-right-radius: 0px; border-bottom-right-radius: 0px; font-size: 14px; font-weight: 600; transition: color 0.2s ease, background 0.2s ease, margin 0.2s ease; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .nav-links li a svg { width: 18px; height: 18px; stroke: #b9cfc0; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: stroke 0.2s ease; flex-shrink: 0; }
    .nav-links li a img { width: 18px; height: 18px; flex-shrink: 0; transition: content 0.2s ease; }
    .nav-links .nav-count { margin-left: auto; font-size: 10px; font-weight: 800; background: #dc2626; color: #ffffff; border-radius: 999px; padding: 1px 7px; line-height: 1.6; flex-shrink: 0; }
    .nav-links li a.active .nav-count { background: #dc2626; color: #ffffff; }
    .nav-links li a:hover:not(.active) img { content: url('{{ asset("images/store-%23ffffff.png") }}'); }
    .nav-links li a.active img { content: url('{{ asset("images/store-%23143d23.png") }}'); }
    .nav-links li a:hover:not(.active) { background: rgba(255, 255, 255, 0.05); color: #ffffff; margin-right: 14px; border-radius: 12px; }
    .nav-links li a:hover:not(.active) svg { stroke: #ffffff; }

    /* Continuous Active Hook */
    .nav-links li a.active { background: #f3f6f4; color: #143d23; font-weight: 700; position: relative; margin-right: -1px; z-index: 10; }
    .nav-links li a.active svg { stroke: #143d23; }
    .nav-links li:not(.nav-dropdown):has(a.active) { z-index: 12; }
    .nav-links li:not(.nav-dropdown):has(a.active)::before { content: ""; position: absolute; top: -20px; right: 0; height: 20px; width: 20px; background-color: transparent; border-bottom-right-radius: 20px; box-shadow: 10px 10px 0 10px #f3f6f4; pointer-events: none; z-index: 12; }
    .nav-links li:not(.nav-dropdown):has(a.active)::after { content: ""; position: absolute; bottom: -20px; right: 0; height: 20px; width: 20px; background-color: transparent; border-top-right-radius: 20px; box-shadow: 10px -10px 0 10px #f3f6f4; pointer-events: none; z-index: 12; }

    /* Dropdown Sub-Navigation */
    .nav-dropdown { display: flex; flex-direction: column; }
    .nav-dropdown-btn {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 16px;
      color: #b9cfc0;
      background: transparent;
      border: none;
      border-top-left-radius: 12px;
      border-bottom-left-radius: 12px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: color 0.2s ease, background 0.2s ease;
      font-family: inherit;
    }
    .nav-dropdown-btn:hover { background: rgba(255, 255, 255, 0.05); color: #ffffff; }
    .nav-dropdown-title { display: flex; align-items: center; gap: 12px; }
    .nav-dropdown-title svg { width: 18px; height: 18px; stroke: #b9cfc0; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .nav-dropdown-btn:hover .nav-dropdown-title svg { stroke: #ffffff; }
    .dropdown-chevron { width: 16px; height: 16px; stroke: #87a992; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; transition: transform 0.25s ease; }
    .nav-dropdown.open .dropdown-chevron { transform: rotate(180deg); }
    .sub-nav-links { list-style: none; display: none; padding-left: 32px; margin-top: 2px; }
    .nav-dropdown.open .sub-nav-links { display: block; }
    .sub-nav-links li a { padding: 10px 14px; font-size: 13px; color: #87a992; }
    .sub-nav-links li a svg { width: 16px; height: 16px; }

    /* Footer badge and logout */
    .sidebar-footer { padding: 16px; background: #0d2917; border-top: 1px solid rgba(255, 255, 255, 0.08); position: relative; z-index: 110; }
    .profile-card { margin: 0 0 12px; }
    .user-profile-badge {
      display: flex; align-items: center; gap: 12px;
      width: 100%; padding: 6px 8px 12px; margin: 0;
      background: transparent; border: none; border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 12px; cursor: pointer; font-family: inherit; text-align: left; color: inherit;
      text-decoration: none;
      transition: background 0.18s ease, border-color 0.18s ease;
    }
    .user-profile-badge:hover { background: rgba(255, 255, 255, 0.06); border-bottom-color: rgba(255, 255, 255, 0.12); }
    .profile-chevron { margin-left: auto; width: 15px; height: 15px; stroke: var(--sb-muted); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
    .avatar { width: 36px; height: 36px; background: #1b4d2e; color: #c8e6c9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; text-transform: uppercase; }
    .user-info { display: flex; flex-direction: column; min-width: 0; }
    .user-name { font-size: 14px; font-weight: 600; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-role { font-size: 11px; color: #87a992; text-transform: capitalize; }
    .logout-btn { background: var(--sb-logout-bg, rgba(239, 68, 68, 0.08)); color: var(--sb-logout-fg, #fca5a5); border: 1px solid var(--sb-logout-border, rgba(239, 68, 68, 0.15)); padding: 11px 16px; width: 100%; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: flex-start; gap: 12px; transition: all 0.2s ease; }
    .logout-btn svg { width: 16px; height: 16px; stroke: var(--sb-logout-fg, #fca5a5); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .logout-btn:hover { background: var(--sb-logout-hover-bg, #b0403a); color: var(--sb-logout-hover-fg, #ffffff); border-color: var(--sb-logout-hover-bg, #b0403a); }
    .logout-btn:hover svg { stroke: var(--sb-logout-hover-fg, #ffffff); }

    /* Hide page-description sub-texts under titles; keep clear space below each title */
    .page-subtitle, .hero-sub { display: none !important; }
    .main-content h1 + p:not(.keep-sub) { display: none !important; }
    .main-content .keep-sub { display: block !important; }
    .main-content > :first-child h1:first-child { margin-bottom: 18px !important; }
    .page-header { margin-bottom: 36px !important; }

    .mobile-header { display: none; background: #143d23; color: white; padding: 14px 16px; align-items: center; justify-content: space-between; position: fixed; top: 0; left: 0; right: 0; z-index: 90; }
    .burger-btn { background: none; border: none; color: var(--sb-fg); font-size: 24px; cursor: pointer; padding: 0; line-height: 1; display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; }
    .burger-btn:active { background: rgba(255, 255, 255, 0.08); }

    .desktop-notif-wrap { position: fixed; top: 26px; right: 20px; z-index: 200; transition: transform 0.3s ease; }
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

    .desktop-notif { position: relative; }
    .notif-badge {
      position: absolute; top: -3px; right: -3px; min-width: 18px; height: 18px; padding: 0 5px;
      border-radius: 999px; background: #dc2626; color: #ffffff;
      font-size: 10px; font-weight: 800; line-height: 18px; text-align: center;
      display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    }
    .notif-body { padding: 8px 10px 10px; max-height: 320px; overflow-y: auto; scrollbar-width: thin; }
    .notif-item { padding: 10px 12px; border-radius: 10px; }
    .notif-item + .notif-item { border-top: 1px solid #f1f5f9; border-radius: 0; }
    .notif-item:hover { background: #f8fafc; }
    .notif-item-stars { display: inline-flex; align-items: center; gap: 2px; margin-bottom: 4px; }
    .notif-item-stars svg { width: 12px; height: 12px; fill: #f59e0b; }
    .notif-item-stars svg.empty { fill: #e2e8f0; }
    .notif-item-text { font-size: 12px; color: #334155; line-height: 1.45; font-weight: 500; }
    .notif-item-meta { display: flex; align-items: center; gap: 8px; margin-top: 5px; font-size: 11px; color: #94a3b8; flex-wrap: wrap; }
    .notif-item-meta a { color: #15803d; font-weight: 700; text-decoration: none; }
    .notif-item-meta a:hover { text-decoration: underline; }
    .notif-item-pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: capitalize; }
    .notif-foot { padding: 10px 12px 2px; border-top: 1px solid #f1f5f9; }
    .notif-foot button {
      width: 100%; border: none; background: #f0fdf4; color: #15803d; font-size: 12px; font-weight: 700;
      padding: 9px; border-radius: 9px; cursor: pointer; font-family: inherit; transition: background 0.15s ease;
    }
    .notif-foot button:hover { background: #dcfce7; }

    /* Sub-page pill tab bar (partials/admin-tabs).
       Floats at the very top of the screen, mirrored to the notification bell
       (bell = top-right, tabs = top-left). */
    .aqua-tabs {
      position: fixed;
      top: 26px;
      left: 280px;
      z-index: 200;
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 6px;
      background: #ffffff;
      border: 1px solid #eef1f4;
      border-radius: 999px;
      box-shadow: 0 2px 12px rgba(16, 24, 40, 0.10);
      max-width: calc(100vw - 340px);
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      transition: transform 0.3s ease;
    }
    .aqua-tabs::-webkit-scrollbar { display: none; }
    .aqua-tabs.hidden { transform: translateY(-90px); }
    .aqua-tab {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 12px 26px;
      border-radius: 999px;
      background: #ffffff;
      color: #5b6b60;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
      white-space: nowrap;
      transition: all 0.2s ease;
    }
    .aqua-tab:hover { background: #f1f5f9; color: #143d23; }
    .aqua-tab.active {
      background: #14532d;
      color: #ffffff;
      box-shadow: 0 2px 8px rgba(20, 83, 45, 0.30);
    }
    .aqua-tabs-spacer { height: 18px; }

    @media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .main-content { margin-left: 0; padding: 88px 16px 24px 16px; }
  .mobile-header { display: flex; }
  .sidebar-overlay { display: none; position: fixed; top: 0; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.4); z-index: 95; }
  .sidebar-overlay.open { display: block; }

  .desktop-notif { background: transparent; border: none; box-shadow: none; color: var(--sb-fg); }
  .desktop-notif:hover { transform: none; box-shadow: none; background: rgba(255, 255, 255, 0.08); }
  .desktop-notif-wrap { top: 11px; right: 16px; }

  .aqua-tabs {
    top: 68px;
    left: 16px;
    right: 16px;
    max-width: none;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .aqua-tabs::-webkit-scrollbar { display: none; }
  .aqua-tab { padding: 12px 20px; flex: 0 0 auto; }
  .aqua-tabs-spacer { height: 48px; }

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

    /* -- Shared table toolbar: search + styled dropdowns (ncb-select) + sort + clear -- */
    .table-toolbar {
      position: relative; z-index: 5;
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 12px 14px; margin-bottom: 22px;
      background: #ffffff;
      border: 1px solid #e2e8f0; border-radius: 14px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.04), 0 6px 18px rgba(15,23,42,0.07);
    }
    .ctl-search {
      position: relative; flex: 1; min-width: 240px;
    }
    .ctl-search svg {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      width: 17px; height: 17px; stroke: #94a3b8; fill: none;
    }
    .ctl-search input {
      width: 100%; height: 42px; padding: 0 16px 0 40px;
      border: 1.5px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; color: #0f172a; outline: none; background: #f8fafc;
      font-weight: 500; font-family: inherit; box-sizing: border-box;
      transition: all .2s ease;
    }
    .ctl-search input:focus {
      border-color: #16a34a; background: #ffffff;
      box-shadow: 0 0 0 3px rgba(22,163,74,.08);
    }
    .ctl-search input::placeholder { color: #94a3b8; }
    .ncb-group-inline { position: relative; min-width: 170px; }
    .ncb-select-trigger {
      width: 100%; height: 42px; padding: 0 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; color: #1e293b; background-color: #ffffff; transition: all 0.2s ease;
      box-sizing: border-box; font-family: inherit; cursor: pointer;
      display: flex; justify-content: space-between; align-items: center; user-select: none;
      text-align: left; gap: 8px; white-space: nowrap;
    }
    .ncb-select-trigger.placeholder { color: #94a3b8; }
    .ncb-select-trigger:hover { border-color: #cbd5e1; }
    .ncb-select-trigger.active { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,0.08); outline: none; }
    .ncb-select-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ncb-select-arrow { transition: transform 0.2s ease; color: #64748b; flex-shrink: 0; }
    .ncb-select-arrow.rotate { transform: rotate(180deg); }
    .ncb-select-menu {
      display: none;
      position: absolute; top: calc(100% + 4px); left: 0; right: 0;
      background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 10px rgba(0,0,0,0.05);
      max-height: 240px; overflow-y: auto; z-index: 600; padding: 4px;
      scrollbar-width: thin;
    }
    .ncb-select-option {
      padding: 8px 12px; font-size: 13px; color: #1e293b; border-radius: 6px;
      cursor: pointer; transition: background 0.15s ease, color 0.15s ease;
    }
    .ncb-select-option:hover { background: #f1f5f9; color: #16a34a; }
    .ncb-select-option.selected { background: #f0fdf4; color: #16a34a; font-weight: 600; }
    .btn-clear {
      display: inline-flex; align-items: center; gap: 6px;
      height: 42px; padding: 0 14px; border: 1.5px solid #bfe3c7; border-radius: 10px;
      background: #f3faf4; color: #166534; font-size: 13px; font-weight: 600;
      cursor: pointer; font-family: inherit; transition: all 0.2s ease;
      box-shadow: 0 1px 2px rgba(22,101,52,0.06); flex-shrink: 0;
    }
    .btn-clear:hover { border-color: #a3d4ad; background: #eaf6ee; box-shadow: 0 2px 8px rgba(22,101,52,0.10); }
    .btn-clear[hidden] { display: none; }
    .ttb-sort { min-width: 210px; }
    .btn-filter {
      position: relative; width: 42px; height: 42px; flex-shrink: 0;
      display: inline-flex; align-items: center; justify-content: center;
      border: 1.5px solid #e2e8f0; border-radius: 10px;
      background: #ffffff; color: #64748b; cursor: pointer;
      font-family: inherit; transition: all 0.2s ease;
    }
    .btn-filter svg { width: 18px; height: 18px; }
    .btn-filter:hover { border-color: #cbd5e1; color: #334155; }
    .btn-filter.active { border-color: #16a34a; background: #f0fdf4; color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,0.08); }
    .btn-filter-dot {
      position: absolute; top: -3px; right: -3px; width: 9px; height: 9px;
      border-radius: 50%; background: #dc2626; border: 2px solid #ffffff;
    }
    .btn-filter-dot[hidden] { display: none; }
    .ttb-filters { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; flex: 1 1 100%; }
    .ttb-filters[hidden] { display: none; }
    .ttb-pagination {
      display: flex; align-items: center; gap: 10px;
      padding: 4px 0; flex-wrap: wrap; margin-left: auto;
    }
    .ttb-pagination .pagination-info { font-size: 13px; color: #64748b; font-weight: 500; }
    .ttb-pagination .pagination-controls { display: flex; align-items: center; gap: 6px; }
    .ttb-pagination .page-btn {
      min-width: 34px; height: 34px; padding: 0 10px; border: 1.5px solid #e2e8f0;
      border-radius: 8px; background: #ffffff; color: #475569; font-size: 13px;
      font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s;
      display: inline-flex; align-items: center; justify-content: center;
    }
    .ttb-pagination .page-btn:hover:not(:disabled) { border-color: #16a34a; color: #16a34a; }
    .ttb-pagination .page-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    .ttb-pagination .page-btn.active { background: #16a34a; border-color: #16a34a; color: #ffffff; font-weight: 700; }
    .ttb-pagination .page-ellipsis { color: #94a3b8; font-size: 13px; padding: 0 2px; }

    /* -- Bottom-of-table pagination bar (client-side tables) -- */
    .ttb-bottom-bar {
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px; padding: 14px 20px; border-top: 1px solid #f1f5f9;
      background: #fafdfb; flex-wrap: wrap;
    }
    .ttb-bottom-bar .pagination-info { font-size: 13px; color: #64748b; font-weight: 500; }
    .ttb-bottom-bar .pagination-controls { display: flex; align-items: center; gap: 6px; }
    .ttb-bottom-bar .page-btn {
      min-width: 34px; height: 34px; padding: 0 10px; border: 1.5px solid #e2e8f0;
      border-radius: 8px; background: #ffffff; color: #475569; font-size: 13px;
      font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s;
      display: inline-flex; align-items: center; justify-content: center;
    }
    .ttb-bottom-bar .page-btn:hover:not(:disabled) { border-color: #16a34a; color: #16a34a; }
    .ttb-bottom-bar .page-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    .ttb-bottom-bar .page-btn.active { background: #16a34a; border-color: #16a34a; color: #ffffff; font-weight: 700; }
    .ttb-bottom-bar .page-ellipsis { color: #94a3b8; font-size: 13px; padding: 0 2px; }

    /* -- Smooth page navigation: fade in fresh pages, softly fade out clicks -- */
    body { animation: aqua-page-in .28s ease; transition: opacity .18s ease; }
    @keyframes aqua-page-in {
      from { opacity: 0; transform: translateY(4px); }
      to   { opacity: 1; transform: none; }
    }
    body.aqua-leaving { opacity: .6; }

    /* Themed top progress bar shown while a page is loading */
    #aqua-progress {
      position: fixed; top: 0; left: 0; height: 3px; width: 0;
      background: linear-gradient(90deg, #6aeb2f, #2f9e54);
      box-shadow: 0 0 8px rgba(52, 129, 16, 0.55);
      z-index: 9999; border-radius: 0 3px 3px 0;
      opacity: 0; pointer-events: none;
      transition: width .18s ease, opacity .25s ease;
    }
    #aqua-progress.active { opacity: 1; }

    /* ------------------------------------------------------------
       Sidebar themes — switch the side menu color per user.
       The palettes below are generated from config/sidebar.php and
       applied through a data-sidebar-theme attribute on the body element.
    ------------------------------------------------------------ */
    @php
      $stThemes = config('sidebar.themes');
      $stDefault = config('sidebar.default');
    @endphp
    @foreach ($stThemes as $stKey => $stTheme)
      @php $stC = $stTheme['colors']; @endphp
      @if ($stKey === $stDefault) :root, @endif body[data-sidebar-theme="{{ $stKey }}"] {
      --sb-bg:{{ $stC['bg'] }}; --sb-deep:{{ $stC['deep'] }};
      --sb-fg:{{ $stC['fg'] }}; --sb-nav:{{ $stC['nav'] }}; --sb-muted:{{ $stC['muted'] }}; --sb-nav-hover:{{ $stC['nav_hover'] }};
      --sb-hover-bg:{{ $stC['hover_bg'] }}; --sb-hover-strong:{{ $stC['hover_strong'] }};
      --sb-active-bg:{{ $stC['active_bg'] }}; --sb-active-fg:{{ $stC['active_fg'] }};
      --sb-accent:{{ $stC['accent'] }};
      --sb-avatar-bg:{{ $stC['avatar_bg'] }}; --sb-avatar-fg:{{ $stC['avatar_fg'] }};
      --sb-border:{{ $stC['border'] }}; --sb-border-soft:{{ $stC['border_soft'] }};
      --sb-card-bg-a:{{ $stC['card_a'] }}; --sb-card-bg-b:{{ $stC['card_b'] }};
      --sb-card-border:{{ $stC['card_border'] }}; --sb-card-border-hover:{{ $stC['card_border_hover'] }};
      --sb-card-shadow:{{ $stC['card_shadow'] }};
      --sb-badge-bg:{{ $stC['badge_bg'] }};
      @if (!empty($stC['menu_ico_dark']))
      --sb-menu-ico-url:url('{{ asset("images/store-%23143d23.png") }}');
      @else
      --sb-menu-ico-url:url('{{ asset("images/store-%23ffffff.png") }}');
      @endif
      --sb-logout-bg:{{ $stC['logout_bg'] }}; --sb-logout-fg:{{ $stC['logout_fg'] }}; --sb-logout-border:{{ $stC['logout_border'] }};
      --sb-logout-hover-bg:{{ $stC['logout_hover_bg'] }}; --sb-logout-hover-fg:{{ $stC['logout_hover_fg'] }};
    }
    @endforeach

    /* Theme-aware sidebar overrides (defaults recreate the original emerald design) */
    .sidebar { background: var(--sb-bg); color: var(--sb-fg); }
    .mobile-header { background: var(--sb-bg); color: var(--sb-fg); }
    .sidebar-footer { background: var(--sb-deep); color: var(--sb-fg); border-top-color: var(--sb-border); }
    .brand-section { color: var(--sb-fg); border-bottom-color: var(--sb-border); }
    .brand-title { color: var(--sb-fg); }
    .brand-accent { color: var(--sb-accent); }
    .menu-group-title { color: var(--sb-muted); }
    .nav-links li a { color: var(--sb-nav); }
    .nav-links li a svg { stroke: var(--sb-nav); }
    .nav-links li a:hover:not(.active) { background: var(--sb-hover-bg); color: var(--sb-nav-hover); }
    .nav-links li a:hover:not(.active) svg { stroke: var(--sb-nav-hover); }
    .nav-links li a.active { background: var(--sb-active-bg); color: var(--sb-active-fg); }
    .nav-links li a.active svg { stroke: var(--sb-active-fg); }
    .nav-links li:not(.nav-dropdown):has(a.active)::before { box-shadow: 10px 10px 0 10px var(--sb-active-bg); }
    .nav-links li:not(.nav-dropdown):has(a.active)::after { box-shadow: 10px -10px 0 10px var(--sb-active-bg); }
    .nav-dropdown-btn { color: var(--sb-nav); }
    .nav-dropdown-btn:hover { background: var(--sb-hover-bg); color: var(--sb-nav-hover); }
    .nav-dropdown-title svg { stroke: var(--sb-nav); }
    .nav-dropdown-btn:hover .nav-dropdown-title svg { stroke: var(--sb-nav-hover); }
    .dropdown-chevron { stroke: var(--sb-muted); }
    .sub-nav-links li a { color: var(--sb-muted); }
    .user-profile-badge { border-bottom-color: var(--sb-border-soft); }
    .avatar { background: var(--sb-avatar-bg); color: var(--sb-avatar-fg); }
    .user-name { color: var(--sb-fg); }
    .user-role { color: var(--sb-muted); }
    /* Weather card uses the active sidebar theme's translucent "glass" colors */
    .climate-card { background: linear-gradient(135deg, var(--sb-card-bg-a), var(--sb-card-bg-b)); border: 1px solid var(--sb-card-border); box-shadow: 0 6px 18px rgba(20, 40, 25, 0.12), var(--sb-card-shadow); }
    .climate-card:hover, .climate-card.pinned { background: linear-gradient(135deg, var(--sb-card-bg-a), var(--sb-card-bg-b)); border: 1px solid var(--sb-card-border-hover); }
    .climate-card:focus-visible { outline-color: var(--sb-accent); }
    .climate-mini-temp { color: var(--sb-fg); }
    .climate-mini-sub { color: var(--sb-muted); }
    .climate-chevron { stroke: var(--sb-muted); }
    .climate-details { border-top-color: var(--sb-border-soft); }
    .climate-meta .climate-updated { color: var(--sb-muted); }
    .climate-meta .climate-clock { color: var(--sb-fg); }
    .season-badge { background: var(--sb-badge-bg); }
    .climate-season-label { color: var(--sb-fg); }
    .climate-season-sub { color: var(--sb-muted); }
    .nav-links li a:hover:not(.active) img { content: var(--sb-menu-ico-url); }

/* -- Sub-page navigation inside the side menu ("Side menu" UI style):
   each main menu name becomes a dropdown and only the sub-page link
   carries the active state, styled with the same continuous active
   pill and curve hooks as the side menu's main active item. */
    .sub-nav-links { position: relative; }
    .sub-nav-links::before {
      content: "";
      position: absolute; left: 12px; top: 8px; bottom: 8px;
      width: 2px; border-radius: 2px;
      background: var(--sb-muted); opacity: 0.35;
      pointer-events: none;
    }
    .sub-nav-links li { position: relative; }
    .sub-nav-links li a { border-radius: 8px; transition: color 0.18s ease, background 0.18s ease; }
    .sub-nav-links li a:hover { color: var(--sb-nav-hover); background: var(--sb-hover-bg); }
    .sub-nav-links li a.active {
      background: var(--sb-active-bg); color: var(--sb-active-fg);
      font-weight: 700; position: relative;
      margin-right: -1px;
      border-top-left-radius: 12px; border-bottom-left-radius: 12px;
      border-top-right-radius: 0; border-bottom-right-radius: 0;
    }
    .sub-nav-links li:has(a.active) { z-index: 12; }
    .sub-nav-links li:has(a.active)::before {
      content: ""; position: absolute; top: -20px; right: 0;
      height: 20px; width: 20px; background-color: transparent;
      border-bottom-right-radius: 20px;
      box-shadow: 10px 10px 0 10px var(--sb-active-bg);
      pointer-events: none; z-index: 12;
    }
    .sub-nav-links li:has(a.active)::after {
      content: ""; position: absolute; bottom: -20px; right: 0;
      height: 20px; width: 20px; background-color: transparent;
      border-top-right-radius: 20px;
      box-shadow: 10px -10px 0 10px var(--sb-active-bg);
      pointer-events: none; z-index: 12;
    }
  </style>
</head>
@php $stActive = in_array((string) optional(auth()->user())->sidebar_theme, array_keys(config('sidebar.themes')), true) ? auth()->user()->sidebar_theme : config('sidebar.default'); @endphp
@php
  $subPagesStyle = (string) optional(auth()->user())->sub_pages_style;
  $subPagesStyle = $subPagesStyle === 'top' ? 'top' : 'sidebar';
  $adminNav = $subPagesStyle === 'sidebar' ? (config('admin-nav') ?? []) : [];
  $activeAdminSection = null;
  if ($adminNav) {
    foreach ($adminNav as $ai => $section) {
      foreach ($section['items'] as $item) {
        if (request()->routeIs(...$item['routes'])) { $activeAdminSection = $ai; break 2; }
      }
    }
  }
  $storeNav = $subPagesStyle === 'sidebar' ? (config('store-nav') ?? []) : [];
  $activeStoreSection = null;
  if ($storeNav) {
    foreach ($storeNav as $si => $section) {
      foreach ($section['items'] as $item) {
        if (request()->routeIs(...$item['routes'])) { $activeStoreSection = $si; break 2; }
      }
    }
  }
  $farmerNav = $subPagesStyle === 'sidebar' ? (config('farmer-nav') ?? []) : [];
  $activeFarmerSection = null;
  if ($farmerNav) {
    foreach ($farmerNav as $fi => $section) {
      foreach ($section['items'] as $item) {
        if (request()->routeIs(...$item['routes'])) { $activeFarmerSection = $fi; break 2; }
      }
    }
  }
@endphp
<body data-sidebar-theme="{{ $stActive }}">

  @if(auth()->check() && auth()->user()->role === 'store_owner')
  @php
    $storeNotifQuery = \App\Models\ActivityLog::forUser(auth()->id())->reviewNotifications();
    $storeUnreadCount = $storeNotifQuery->unread()->count();
    $storeUnreadList  = $storeNotifQuery->unread()->latest()->limit(5)->get();
  @endphp
  @endif

  <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

  <header class="mobile-header">
    <div style="display: flex; align-items: center; gap: 4px;">
      <button class="burger-btn" onclick="toggleSidebar()" aria-label="Open menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div style="font-weight: 700; font-size: 18px; line-height: 1;">
        Aqui<span style="color: var(--sb-accent, #6aeb2f); font-weight: 800;">lala</span>
      </div>
    </div>
  </header>

  <aside class="sidebar" id="sidebar">
    <div class="brand-section">
      <span class="brand-title">Aqui<span class="brand-accent">lala</span></span>
      Irrigators Association, Inc.
    </div>

    <div class="nav-body">
      <!-- Live Season/Climate Widget -->
@php 
  $climate = $globalClimateInfo ?? (class_exists('\App\Helpers\SeasonHelper') ? \App\Helpers\SeasonHelper::getClimateInfo() : ['season' => 'dry', 'label' => 'Dry']); 
  $isWet = ($climate['season'] ?? 'dry') === 'wet';
  
  $weather = class_exists('\App\Services\WeatherService') ? \App\Services\WeatherService::getCurrentWeather() : ['temperature' => null, 'condition' => 'Weather unavailable', 'is_available' => false];

  $weatherIconMap = [
    'sun'             => 'sunny',
    'cloud-sun'       => 'cloud-sun',
    'cloud'           => 'cloudy',
    'cloud-rain'      => 'rain',
    'cloud-lightning' => 'cloud-lightning',
    'cloud-drizzle'   => 'drizzle',
  ];
  $weatherAvailable = !empty($weather['is_available']) && !empty($weather['icon']);
  $weatherIconFile = $weatherAvailable ? ($weatherIconMap[$weather['icon']] ?? 'cloudy') : 'cloudy';
  $seasonIconFile = $isWet ? 'wet-season' : 'dry-season';
@endphp

<div class="climate-card" id="climate-card" tabindex="0" role="button" aria-expanded="false" aria-label="Weather details">
  <div class="climate-row">
    <span class="season-icon {{ $weatherAvailable ? 'weather-available' : 'weather-unavailable' }}" style="width: 34px; height: 34px;">
      <img src="{{ asset('images/weather/' . $weatherIconFile . '.png') }}" alt="{{ $weatherAvailable ? ($weather['condition'] ?? 'Weather') : 'Weather unavailable' }}">
    </span>
    <div class="climate-mini-info">
      <div class="climate-mini-temp">{{ $weather['is_available'] && $weather['temperature'] !== null ? $weather['temperature'] . '°C' : '—' }}</div>
      <div class="climate-mini-sub">{{ $weather['condition'] ?? 'Weather unavailable' }}</div>
    </div>
    <svg class="climate-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
  </div>
  <div class="climate-details">
    <div class="climate-meta">
      <span id="today-date" class="climate-updated"></span>
      <span id="live-time" class="climate-clock"></span>
    </div>
    <div class="climate-season">
      <span class="season-badge">
        <img src="{{ asset('images/weather/' . $seasonIconFile . '.png') }}" alt="{{ $climate['label'] ?? ($isWet ? 'Wet Season' : 'Dry Season') }}">
        <span class="climate-season-info">
          <span class="climate-season-label">{{ $climate['label'] ?? 'Dry Season' }}</span>
          <span class="climate-season-sub">{{ $climate['months'] ?? ($isWet ? 'June – January' : 'February – May') }}</span>
        </span>
      </span>
    </div>
  </div>
</div>


      <div class="menu-group-title">
        @if(auth()->user()->role === 'farmer') Farmer Dashboard @elseif(auth()->user()->role === 'store_owner') My Store @else Admin Control @endif
      </div>
      <ul class="nav-links">
        @if(auth()->user()->role === 'farmer')
          @if($subPagesStyle === 'sidebar' && $farmerNav)
            @foreach($farmerNav as $fi => $section)
              @php $isFarmerOpen = ($activeFarmerSection === $fi); @endphp
              <li class="nav-dropdown {{ $isFarmerOpen ? 'open' : '' }}">
                <button type="button" class="nav-dropdown-btn" aria-expanded="{{ $isFarmerOpen ? 'true' : 'false' }}">
                  <span class="nav-dropdown-title">
                    {!! $section['icon'] !!}
                    <span>{{ $section['label'] }}</span>
                  </span>
                  <svg class="dropdown-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <ul class="sub-nav-links">
                  @foreach($section['items'] as $item)
                    <li>
                      <a href="{{ route($item['routes'][0]) }}" class="{{ request()->routeIs(...$item['routes']) ? 'active' : '' }}">
                        {{ $item['label'] }}
                      </a>
                    </li>
                  @endforeach
                </ul>
              </li>
            @endforeach
            <li>
              <a href="{{ route('farmer.settings.index') }}" class="{{ request()->routeIs('farmer.settings.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Settings
              </a>
            </li>
          @else
          <li>
            <a href="{{ route('dashboard.farmer') }}" class="{{ request()->routeIs('dashboard.farmer') || request()->routeIs('farmer.dashboard') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
              Dashboard
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.crops.index') }}" class="{{ request()->routeIs('farmer.crops.index', 'farmer.crops.show') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
              My Crops
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.harvests.index') }}" class="{{ request()->routeIs('farmer.harvests.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
              Harvest Reports
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.recommendations.index') }}" class="{{ request()->routeIs('farmer.recommendations.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              Recommendations
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.crops-directory.index') }}" class="{{ request()->routeIs('farmer.crops-directory.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
              Crop Directory
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.ai-chat.index') }}" class="{{ request()->routeIs('farmer.ai-chat.*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7l.9 2.4L15.3 10l-2.4.9L12 13.3l-.9-2.4L8.7 10l2.4-.6z"/></svg>
              AI Assistant
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.products.browse') }}" class="{{ request()->routeIs('farmer.products.browse', 'farmer.products.show') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              Browse Products
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.reviews.index') }}" class="{{ request()->routeIs('farmer.reviews.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              Top Rated Products
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.stores.index') }}" class="{{ request()->routeIs('farmer.stores.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              Stores Directory
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.quotations.request') }}" class="{{ request()->routeIs('farmer.quotations.request', 'farmer.quotations.products') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              Generate Quotation
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.quotations.index') }}" class="{{ request()->routeIs('farmer.quotations.index', 'farmer.quotations.show', 'farmer.quotations.breakdown') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
              My Cost Quotations
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.notifications.index') }}" class="{{ request()->routeIs('farmer.notifications.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              Notifications
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.activity.index') }}" class="{{ request()->routeIs('farmer.activity.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Activity Log
            </a>
          </li>
          <li>
            <a href="{{ route('farmer.settings.index') }}" class="{{ request()->routeIs('farmer.settings.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Settings
            </a>
          </li>
          @endif

        @elseif(auth()->user()->role === 'store_owner')
          @if($subPagesStyle === 'sidebar' && $storeNav)
            <li>
              <a href="{{ route('store.dashboard') }}" class="{{ request()->routeIs('store.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                Store Dashboard
              </a>
            </li>
            @foreach($storeNav as $si => $section)
              @php $isStoreOpen = ($activeStoreSection === $si); @endphp
              <li class="nav-dropdown {{ $isStoreOpen ? 'open' : '' }}">
                <button type="button" class="nav-dropdown-btn" aria-expanded="{{ $isStoreOpen ? 'true' : 'false' }}">
                  <span class="nav-dropdown-title">
                    {!! $section['icon'] !!}
                    <span>{{ $section['label'] }}@if($si === 'notifications' && ($storeUnreadCount ?? 0) > 0)<span class="nav-count" id="storeNavBadge">{{ $storeUnreadCount }}</span>@endif</span>
                  </span>
                  <svg class="dropdown-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <ul class="sub-nav-links">
                  @foreach($section['items'] as $item)
                    <li>
                      <a href="{{ route($item['routes'][0]) }}" class="{{ request()->routeIs(...$item['routes']) ? 'active' : '' }}">
                        {{ $item['label'] }}
                      </a>
                    </li>
                  @endforeach
                </ul>
              </li>
            @endforeach
            <li>
              <a href="{{ route('store.settings.index') }}" class="{{ request()->routeIs('store.settings.index') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Settings
              </a>
            </li>
          @else
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
              Demand Intelligence
            </a>
          </li>
          <li>
            <a href="{{ route('store.ai-chat.index') }}" class="{{ request()->routeIs('store.ai-chat.*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7l.9 2.4L15.3 10l-2.4.9L12 13.3l-.9-2.4L8.7 10l2.4-.6z"/></svg>
              AI Assistant
            </a>
          </li>
          <li>
            <a href="{{ route('store.storefront.index') }}" class="{{ request()->routeIs('store.storefront.index') ? 'active' : '' }}">
              <img src="{{ asset('images/store-%23b9cfc0.png') }}" alt="" />
              My Store
            </a>
          </li>
<li>
            <a href="{{ route('store.formulas.index') }}" class="{{ request()->is('store/formulas*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              Input Formulas
            </a>
          </li>
          <li>
            <a href="{{ route('store.notifications.index') }}" class="{{ request()->is('store/notifications*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              Notifications
              @if(($storeUnreadCount ?? 0) > 0)
                <span class="nav-count" id="storeNavBadge">{{ $storeUnreadCount }}</span>
              @endif
            </a>
          </li>
          <li>
            <a href="{{ route('store.activity.index') }}" class="{{ request()->routeIs('store.activity.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
              Activity Log
            </a>
          </li>
          <li>
            <a href="{{ route('store.settings.index') }}" class="{{ request()->routeIs('store.settings.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Settings
            </a>
          </li>
          @endif

        @elseif(auth()->user()->role === 'admin')
          @if($subPagesStyle === 'sidebar' && $adminNav)
            @foreach($adminNav as $ai => $section)
              @php $isOpen = ($activeAdminSection === $ai); @endphp
              <li class="nav-dropdown {{ $isOpen ? 'open' : '' }}">
                <button type="button" class="nav-dropdown-btn" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                  <span class="nav-dropdown-title">
                    {!! $section['icon'] !!}
                    <span>{{ $section['label'] }}</span>
                  </span>
                  <svg class="dropdown-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <ul class="sub-nav-links">
                  @foreach($section['items'] as $item)
                    <li>
                      <a href="{{ route($item['routes'][0]) }}" class="{{ request()->routeIs(...$item['routes']) ? 'active' : '' }}">
                        {{ $item['label'] }}
                      </a>
                    </li>
                  @endforeach
                </ul>
              </li>
            @endforeach
          @else
          <li>
            <a href="{{ route('dashboard.admin') }}" class="{{ request()->routeIs('dashboard.admin') || request()->routeIs('admin.activity.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
              Dashboard
            </a>
          </li>
          @yield('sidebar-tabs-nav')
          <li>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->is('admin/users*') || request()->is('admin/approvals*') || request()->is('admin/stores*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Users
            </a>
          </li>
          @yield('sidebar-tabs-nav')
          <li>
            <a href="{{ route('admin.operations.formulas') }}" class="{{ request()->is('admin/operations*') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
              Crops &amp; Inputs
            </a>
          </li>
          @yield('sidebar-tabs-nav')
          <li>
            <a href="{{ route('admin.quotations.index') }}" class="{{ request()->is('admin/analytics*') || request()->is('admin/quotations*') || request()->routeIs('admin.harvests.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
              Analytics
            </a>
          </li>
          @yield('sidebar-tabs-nav')
          <li>
            <a href="{{ route('admin.settings.index') }}" class="{{ request()->is('admin/settings*') || request()->routeIs('admin.audit.index') ? 'active' : '' }}">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
              Settings
            </a>
          </li>
          @yield('sidebar-tabs-nav')
          @endif
        @endif

        @yield('nav-links')
      </ul>
    </div>

    <div class="sidebar-footer">
      <div class="profile-card">
        <a href="{{ route('profile.index') }}" class="user-profile-badge" title="View my profile">
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
          <svg class="profile-chevron" viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"/></svg>
        </a>
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

  @if(auth()->user()->role !== 'farmer')
  <div class="desktop-notif-wrap">
    <button class="desktop-notif" id="desktopNotifBtn" aria-label="Notifications" title="Notifications">
      <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      @if(auth()->user()->role === 'store_owner' && ($storeUnreadCount ?? 0) > 0)
        <span class="notif-badge" id="desktopNotifBadge">{{ $storeUnreadCount }}</span>
      @endif
    </button>
    <div class="notif-dropdown" id="notifDropdown">
      <div class="notif-head">
        <b>Notifications</b>
        @if(auth()->user()->role === 'store_owner')
          <a href="{{ route('store.notifications.index') }}">View all</a>
        @elseif(auth()->user()->role === 'farmer')
          <a href="{{ route('farmer.notifications.index') }}">View all</a>
        @endif
      </div>
      <div class="notif-body">
        @if(auth()->user()->role === 'store_owner')
          @if(($storeUnreadList ?? collect())->isNotEmpty())
            @foreach($storeUnreadList as $n)
              @php
                $nMeta = $n->metadata ?? [];
                $nRating = (int) ($nMeta['rating'] ?? 0);
                $nStatus = $nMeta['status'] ?? '';
                $nPill = match ($nStatus) {
                  'approved' => ['#eef3ef', '#63856c'],
                  'rejected' => ['#f6ecec', '#a86464'],
                  'deleted'  => ['#f1f5f9', '#64748b'],
                  default    => ['#f6f0e6', '#a57f43'],
                };
              @endphp
              <div class="notif-item">
                <div class="notif-item-stars">
                  @for($i = 1; $i <= 5; $i++)
                    <svg viewBox="0 0 24 24" class="{{ $i <= $nRating ? '' : 'empty' }}"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                  @endfor
                </div>
                <div class="notif-item-text">{{ $n->description }}</div>
                <div class="notif-item-meta">
                  <span class="notif-item-pill" style="background:{{ $nPill[0] }};color:{{ $nPill[1] }};">{{ ucfirst($nStatus ?: 'new') }}</span>
                  <span>{{ optional($n->created_at)->diffForHumans() }}</span>
                  @if(($nMeta['product_id'] ?? null))
                    <a href="{{ route('store.products.show', $nMeta['product_id']) }}">View</a>
                  @endif
                </div>
              </div>
            @endforeach
            <form method="POST" action="{{ route('store.notifications.read-all') }}" class="notif-foot">
              @csrf
              <button type="submit">Mark all as read</button>
            </form>
          @else
            <div class="notif-empty">
              <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              <p>No new notifications</p>
            </div>
          @endif
        @else
          <div class="notif-empty">
            <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <p>No new notifications</p>
          </div>
        @endif
      </div>
    </div>
  </div>
  @endif

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

    function toggleNavDropdown(btn) {
      var li = btn.closest('.nav-dropdown');
      if (!li) return;
      var open = li.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.nav-dropdown-btn').forEach(function (btn) {
        btn.addEventListener('click', function () { toggleNavDropdown(btn); });
      });
    });

    // Climate widget: click/tap toggles open/closed (pins it; hover also expands on desktop)
    function toggleClimate() {
      const card = document.getElementById('climate-card');
      if (!card) return;
      const pinned = card.classList.contains('pinned');
      card.classList.toggle('pinned', !pinned);
      card.setAttribute('aria-expanded', String(!pinned));
    }
    document.addEventListener('DOMContentLoaded', function () {
      const card = document.getElementById('climate-card');
      if (!card) return;
      card.addEventListener('click', function (e) {
        e.stopPropagation();
        toggleClimate();
      });
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggleClimate();
        }
      });
      // Close the pinned card when pressing Escape
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && card.classList.contains('pinned')) {
          card.classList.remove('pinned');
          card.setAttribute('aria-expanded', 'false');
        }
      });
    });

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
      var tabsBar = document.querySelector('.aqua-tabs');
      window.addEventListener('scroll', function () {
        var y = window.scrollY;
        if (window.innerWidth > 768) {
          if (y > lastY && y > 80) {
            wrap.classList.add('hidden');
            dropdown.classList.remove('open');
            if (tabsBar) tabsBar.classList.add('hidden');
          } else if (y < lastY || y <= 80) {
            wrap.classList.remove('hidden');
            if (tabsBar) tabsBar.classList.remove('hidden');
          }
        }
        lastY = y;
      }, { passive: true });
    });
  </script>

  <style>
    /* -- Global notice modal (replaces native alert()/confirm()) -- */
    body.aqn-lock { overflow: hidden; }
    .aqn-overlay {
      position: fixed; inset: 0; z-index: 12000;
      display: flex; align-items: center; justify-content: center; padding: 20px;
      background: rgba(243, 246, 244, 0.30);
      opacity: 0; visibility: hidden; transition: opacity 0.18s ease;
    }
    .aqn-overlay.open { opacity: 1; visibility: visible; }
    .aqn-card {
      width: 100%; max-width: 400px; background: #ffffff; border: 1px solid #e5ebe7;
      border-radius: 18px; padding: 28px 26px 22px; text-align: center;
      box-shadow: 0 18px 48px rgba(20, 61, 35, 0.16), 0 2px 8px rgba(20, 61, 35, 0.06);
      transform: translateY(8px) scale(0.97); opacity: 0;
      transition: transform 0.18s ease, opacity 0.18s ease;
    }
    .aqn-overlay.open .aqn-card { transform: translateY(0) scale(1); opacity: 1; }
    .aqn-icon {
      width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }
    .aqn-icon svg { width: 27px; height: 27px; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .aqn-icon.help   { background: #eef7ef; color: #15803d; }
    .aqn-icon.info   { background: #eef4ff; color: #2563eb; }
    .aqn-icon.warn   { background: #fdf3e7; color: #c2620b; }
    .aqn-icon.danger { background: #fdeeee; color: #c0392b; }
    .aqn-title { font-size: 17px; font-weight: 700; color: #1f2a24; margin-bottom: 6px; }
    .aqn-message { font-size: 14px; line-height: 1.55; color: #55625b; word-break: break-word; }
    .aqn-actions { display: flex; gap: 10px; margin-top: 22px; }
    .aqn-btn {
      flex: 1; border: none; border-radius: 999px; padding: 10px 16px;
      font-size: 14px; font-weight: 600; font-family: inherit; cursor: pointer;
      transition: background 0.15s ease, transform 0.05s ease;
    }
    .aqn-btn:active { transform: scale(0.98); }
    .aqn-btn.primary { background: #16a34a; color: #ffffff; }
    .aqn-btn.primary:hover { background: #15803d; }
    .aqn-btn.danger { background: #c0392b; color: #ffffff; }
    .aqn-btn.danger:hover { background: #a93226; }
    .aqn-btn.ghost { background: #eef2ef; color: #33413a; }
    .aqn-btn.ghost:hover { background: #e2e8e4; }
  </style>

  <div class="aqn-overlay" id="aqnOverlay" aria-hidden="true">
    <div class="aqn-card" role="dialog" aria-modal="true" aria-labelledby="aqnTitle" aria-describedby="aqnMsg">
      <div class="aqn-icon" id="aqnIcon"></div>
      <div class="aqn-title" id="aqnTitle"></div>
      <p class="aqn-message" id="aqnMsg"></p>
      <div class="aqn-actions" id="aqnActions"></div>
    </div>
  </div>

  <script>
    (function () {
      'use strict';
      var overlay = document.getElementById('aqnOverlay');
      var icon = document.getElementById('aqnIcon');
      var titleEl = document.getElementById('aqnTitle');
      var msgEl = document.getElementById('aqnMsg');
      var actionsEl = document.getElementById('aqnActions');
      var resolver = null;
      var hideTimer = null;

      var ICONS = {
        help: '<svg viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        warn: '<svg viewBox="0 0 24 24" stroke="currentColor"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        danger: '<svg viewBox="0 0 24 24" stroke="currentColor"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        success: '<svg viewBox="0 0 24 24" stroke="currentColor"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
      };

      function settle(value) {
        if (resolver) { var r = resolver; resolver = null; r(value); }
      }

      function dismiss() {
        overlay.classList.remove('open');
        document.body.classList.remove('aqn-lock');
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () { overlay.setAttribute('aria-hidden', 'true'); }, 200);
      }

      function open(options) {
        var tone = options.tone || (options.danger ? 'danger' : 'help');
        icon.className = 'aqn-icon ' + tone;
        icon.innerHTML = ICONS[tone] || ICONS.help;
        titleEl.textContent = options.title || '';
        titleEl.style.display = options.title ? '' : 'none';
        msgEl.textContent = options.message || '';
        actionsEl.innerHTML = '';

        var addBtn = function (label, cls, value) {
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'aqn-btn ' + cls;
          b.textContent = label;
          b.addEventListener('click', function () { settle(value); dismiss(); });
          actionsEl.appendChild(b);
          return b;
        };

        if (options.showCancel !== false) {
          addBtn(options.cancelLabel || 'Cancel', 'ghost', false);
        }
        var ok = addBtn(
          options.confirmLabel || (options.danger ? 'Delete' : 'Confirm'),
          options.danger ? 'danger' : 'primary',
          true
        );
        ok.focus();

        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('aqn-lock');
      }

      function confirm(options) {
        return new Promise(function (resolve) {
          resolver = resolve;
          open(options);
        });
      }

      function notice(options) {
        return new Promise(function (resolve) {
          resolver = resolve;
          open(Object.assign({}, options, {
            showCancel: false,
            confirmLabel: options.confirmLabel || 'OK',
            tone: options.tone || 'success'
          }));
        });
      }

      window.AquiNotice = { confirm: confirm, notice: notice };

      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) { settle(false); dismiss(); }
      });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { settle(false); dismiss(); }
      });

      // Clicking buttons/links that carry data-aqui-confirm.
      document.addEventListener('click', function (e) {
        var el = e.target.closest ? e.target.closest('[data-aqui-confirm]') : null;
        if (!el || e.defaultPrevented || e.button !== 0) return;
        if (el.tagName === 'FORM') return; // forms are handled on submit
        e.preventDefault();
        var tone = el.getAttribute('data-aqui-tone');
        var danger = el.hasAttribute('data-aqui-danger') || tone === 'danger';
        confirm({
          message: el.getAttribute('data-aqui-confirm'),
          title: el.getAttribute('data-aqui-title'),
          tone: tone,
          danger: danger,
          confirmLabel: el.getAttribute('data-aqui-confirm-label') || (danger ? 'Delete' : 'Continue')
        }).then(function (ok) {
          if (!ok) return;
          if (el.tagName === 'A') {
            window.location.href = el.href;
          } else if (el.form) {
            el.form.setAttribute('data-aqui-confirm-ok', '1');
            if (el.form.requestSubmit) el.form.requestSubmit(el); else el.form.submit();
          }
        });
      });

      // Forms whose submitter (or the form itself) carries data-aqui-confirm.
      document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.matches || !form.matches('form')) return;
        if (form.hasAttribute('data-aqui-confirm-ok')) {
          form.removeAttribute('data-aqui-confirm-ok');
          return;
        }
        var sub = e.submitter || null;
        var el = sub && sub.hasAttribute('data-aqui-confirm') ? sub : (form.hasAttribute('data-aqui-confirm') ? form : null);
        if (!el) return;
        e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
        if (sub) sub.disabled = true;
        var tone = el.getAttribute('data-aqui-tone');
        var danger = el.hasAttribute('data-aqui-danger') || tone === 'danger';
        confirm({
          message: el.getAttribute('data-aqui-confirm'),
          title: el.getAttribute('data-aqui-title'),
          tone: tone,
          danger: danger,
          confirmLabel: el.getAttribute('data-aqui-confirm-label') || (danger ? 'Delete' : 'Continue')
        }).then(function (ok) {
          if (!ok) { if (sub) sub.disabled = false; return; }
          form.setAttribute('data-aqui-confirm-ok', '1');
          if (form.requestSubmit && sub) form.requestSubmit(sub);
          else form.submit();
        });
      });
    })();
  </script>
  @stack('scripts')

  <script>
    // -- Smooth page navigation layer -----------------------------
    // 1) Top progress bar while a page loads 2) fade the old page out
    // on click 3) prefetch internal links on hover/long-press so the
    // next page is already in the browser cache.
    (function () {
      var progress = document.createElement('div');
      progress.id = 'aqua-progress';
      document.body.appendChild(progress);

      function isInternalLink(a) {
        if (!a || !a.href || a.href.indexOf('http') === -1) return false;
        if (a.hasAttribute('download') || a.target === '_blank') return false;
        if (a.getAttribute('href') === '#' || /^#/.test(a.getAttribute('href') || '')) return false;
        try {
          return a.origin === window.location.origin;
        } catch (e) {
          return false;
        }
      }

      function startProgress() {
        progress.classList.add('active');
        progress.style.width = '0%';
        // Fake-advance so it feels alive even on slow loads.
        var width = 0;
        var tick = setInterval(function () {
          width = Math.min(92, width + Math.random() * 14);
          progress.style.width = width + '%';
        }, 180);
        window.aquaProgressTick = tick;
      }

      function finishProgress() {
        clearInterval(window.aquaProgressTick);
        progress.style.width = '100%';
        setTimeout(function () {
          progress.style.width = '0%';
          progress.classList.remove('active');
        }, 260);
      }

      // Fade the current page slightly as the browser starts navigating.
      document.addEventListener('click', function (e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (e.defaultPrevented || e.button !== 0 || !isInternalLink(a)) return;
        // Let the browser keep default navigation; we only enhance it.
        document.body.classList.add('aqua-leaving');
        startProgress();
      });

      // New page finished loading (also fires on back/forward cache).
      window.addEventListener('pageshow', function () {
        document.body.classList.remove('aqua-leaving');
        finishProgress();
      });
      window.addEventListener('load', finishProgress);

      // Prefetch internal pages into the browser's low-priority cache.
      var prefetched = {};
      function prefetch(url) {
        if (prefetched[url]) return;
        if (window.location.href === url) return;
        prefetched[url] = true;
        var link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
      }

      // Desktop: hover near a link warms the cache.
      document.addEventListener('mouseover', function (e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (isInternalLink(a)) prefetch(a.href);
      }, { passive: true });

      // Mobile: press-and-hold (or tap) starts fetching a moment early.
      document.addEventListener('touchstart', function (e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (isInternalLink(a)) prefetch(a.href);
      }, { passive: true });
    })();
  </script>
</body>
</html>

