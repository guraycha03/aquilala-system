@extends('layouts.sidebar')

@section('title', 'System Settings')

@section('content')
  @php
    // SAMPLE defaults — persist to a settings table later.
    $defaults = [
        'org_name'       => 'Aquilala Irrigators Association Inc.',
        'system_name'    => 'Aquilala Farm Supply Management System',
        'address'        => 'Bulan, Sorsogon',
        'official_address' => 'Bulan, Sorsogon, Philippines',
        'contact'        => '0917 000 0000',
        'website'        => 'facebook.com/aquilala',
        'currency'       => 'Philippine Peso (PHP)',
        'timezone'       => 'Asia/Manila (UTC+8)',

        'wet_start'      => 'June',
        'wet_end'        => 'January',
        'dry_start'      => 'February',
        'dry_end'        => 'May',
        'active_season'  => 'wet',
        'quotation_days' => 5,
        'auto_expire'    => true,
        'apply_current'  => true,

        'enable_reco'    => true,
        'enable_llm'     => true,
        'llm_key'        => 'sk-groq-••••••••••••••••••••••••',
        'llm_fallback'   => true,
        'reorder_default'=> 5,

        'session_min'    => 120,
        'min_pass_len'   => 8,
        'verify_farmers' => true,
        'offline_cache'  => true,
        'maintenance'    => false,
    ];
    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    // Live interface settings (persisted via admin.settings.interface)
    $curUnit = \App\Helpers\SettingsHelper::yieldUnit();
    $curDec  = \App\Helpers\SettingsHelper::yieldDecimals();
    $curSep  = \App\Helpers\SettingsHelper::yieldSeparator();
    $curZero = \App\Helpers\SettingsHelper::yieldZeroDash();
  @endphp

  <style>
    .set-page { position: relative; }
    .set-page::before,
    .set-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .set-page::before { width: 320px; height: 320px; background: rgba(16,185,129,0.15); top: -80px; right: -80px; }
    .set-page::after { width: 280px; height: 280px; background: rgba(59,130,246,0.12); bottom: -60px; left: -60px; }
    .set-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }
    .header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-set {
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border: 1px solid transparent;
      background: rgb(172, 242, 121); border-color: rgba(20,61,35,0.12);
      color: #14532d; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700;
      transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(20,61,35,0.08); cursor: pointer;
    }
    .btn-set:hover { background: rgb(160, 232, 108); transform: translateY(-1px); }
    .btn-set svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .btn-set.ghost { background: #ffffff; border-color: rgba(20,61,35,0.10); box-shadow: none; }
    .btn-set.ghost:hover { background: #f0fdf4; }
    .btn-set:disabled { background: #f1f5f9; border-color: #e2e8f0; color: #94a3b8; cursor: not-allowed; box-shadow: none; transform: none; }

    .set-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
    }

    .set-tabs { display: flex; gap: 4px; padding: 12px 16px 0; border-bottom: 1px solid #eef1ef; flex-wrap: wrap; }
    .set-tab {
      display: inline-flex; align-items: center; gap: 7px; padding: 10px 16px; border-radius: 10px 10px 0 0;
      font-size: 13px; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer;
      border-bottom: 2px solid transparent; margin-bottom: -1px; transition: all 0.15s ease;
    }
    .set-tab svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .set-tab:hover { color: #334155; background: #f6f8f7; }
    .set-tab.active { color: #14532d; border-bottom-color: rgb(172, 242, 121); font-weight: 700; }

    .set-body { padding: 24px; }
    .set-section { max-width: 820px; }
    .set-section + .set-section { margin-top: 28px; }
    .set-section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #94a3b8; margin: 0 0 14px 0; }
    .set-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 14px; }
    .set-card {
      background: #fbfcfb; border: 1px solid #eef1ef; border-radius: 14px; padding: 18px;
    }
    .set-field + .set-field { margin-top: 16px; }
    .set-field label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; }
    .set-field .hint { font-size: 11px; color: #94a3b8; margin-top: 5px; line-height: 1.45; }
    .set-field input[type="text"],
    .set-field input[type="number"],
    .set-field select {
      width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; color: #334155; background: #ffffff; outline: none; transition: border-color 0.15s ease;
    }
    .set-field input:focus, .set-field select:focus { border-color: #8fbf4d; }
    .set-field input[type="password"] {
      width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; color: #334155; background: #ffffff; outline: none;
    }
    .set-field .mono { font-family: ui-monospace, monospace; letter-spacing: 0.02em; }

    .set-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
    .set-row .info { flex: 1; min-width: 0; }
    .set-row .info b { display: block; font-size: 13px; font-weight: 600; color: #334155; }
    .set-row .info span { display: block; font-size: 11px; color: #94a3b8; margin-top: 3px; line-height: 1.45; }

    .switch { position: relative; width: 44px; height: 24px; flex-shrink: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .slider {
      position: absolute; inset: 0; border-radius: 999px; background: #d3dbd5; cursor: pointer; transition: background 0.2s ease;
    }
    .switch .slider::before {
      content: ""; position: absolute; width: 18px; height: 18px; border-radius: 50%;
      left: 3px; top: 3px; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: transform 0.2s ease;
    }
    .switch input:checked + .slider { background: #143d23; }
    .switch input:checked + .slider::before { transform: translateX(20px); }

    .season-dates { display: grid; grid-template-columns: 1fr auto 1fr; gap: 8px; align-items: end; }
    .season-dates .sep { padding-bottom: 10px; color: #94a3b8; font-size: 12px; }

    .set-tab-panel { display: none; }
    .set-tab-panel.active { display: block; animation: setFade 0.25s ease; }
    @keyframes setFade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

    .set-toast {
      position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; align-items: center; gap: 10px;
      background: #143d23; color: #e2f3e6; padding: 13px 18px; border-radius: 12px; font-size: 13px; font-weight: 600;
      box-shadow: 0 12px 32px rgba(0,0,0,0.25); opacity: 0; transform: translateY(12px); pointer-events: none;
      transition: all 0.25s ease;
    }
    .set-toast.show { opacity: 1; transform: translateY(0); }
    .set-toast svg { width: 16px; height: 16px; stroke: #6aeb2f; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

    /* Interface tab — radio unit selector */
    .unit-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .unit-opt { position: relative; }
    .unit-opt input { position: absolute; opacity: 0; pointer-events: none; }
    .unit-opt .uo-box {
      position: relative; display: flex; flex-direction: column; gap: 4px;
      padding: 14px 44px 14px 14px; min-height: 92px;
      border: 1.5px solid #e2e8f0; border-radius: 12px; background: #ffffff;
      cursor: pointer; transition: all 0.15s ease;
    }
    .unit-opt .uo-box::after {
      content: ""; position: absolute; top: 14px; right: 14px; width: 16px; height: 16px;
      border-radius: 50%; border: 2px solid #cbd5e1; background: #ffffff;
      box-sizing: border-box; transition: all 0.15s ease;
    }
    .unit-opt .uo-name { font-size: 13px; font-weight: 700; color: #334155; }
    .unit-opt .uo-desc { font-size: 11px; color: #94a3b8; line-height: 1.4; }
    .unit-opt .uo-tag {
      width: fit-content; margin-top: 4px; font-size: 10px; font-weight: 700;
      padding: 3px 8px; border-radius: 999px; background: #f1f5f9; color: #64748b;
      font-variant-numeric: tabular-nums;
    }
    .unit-opt input:checked + .uo-box { border-color: #8fbf4d; background: #f7fbef; box-shadow: 0 0 0 3px rgba(143,191,77,0.14); }
    .unit-opt input:checked + .uo-box .uo-name { color: #14532d; }
    .unit-opt input:checked + .uo-box::after { border-color: #4d7c0f; border-width: 5px; }
    .unit-opt:hover .uo-box { border-color: #cce5a3; }

    .iface-preview {
      margin-top: 16px; padding: 14px 16px; border-radius: 12px;
      background: linear-gradient(135deg, #f7fbef, #eef6e3); border: 1px dashed #cbe3a8;
    }
    .iface-preview .pv-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #65a30d; margin-bottom: 8px; }
    .iface-preview .pv-line { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
    .iface-preview .pv-line + .pv-line { margin-top: 6px; }
    .iface-preview .pv-name { font-size: 12px; color: #64748b; }
    .iface-preview .pv-val { font-size: 16px; font-weight: 700; color: #14532d; font-variant-numeric: tabular-nums; }

    /* Map card */
    .map-card { padding: 0; overflow: hidden; }
    .map-wrap { position: relative; height: 300px; background: #eef1ef; }
    .map-wrap .map-fallback {
      position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center;
      gap: 8px; color: #94a3b8; font-size: 13px; text-align: center; padding: 20px;
    }
    .map-wrap .map-fallback svg { width: 28px; height: 28px; stroke: #c3cec7; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }
    #orgMap { width: 100%; height: 100%; z-index: 1; }
    .org-pin { background: transparent; border: none; }
    .org-pin svg { display: block; filter: drop-shadow(0 2px 4px rgba(20,61,35,0.35)); }
    .map-footer {
      display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 14px 18px; border-top: 1px solid #eef1ef;
    }
    .map-footer .loc { display: flex; align-items: center; gap: 9px; color: #334155; font-size: 13px; font-weight: 600; min-width: 0; }
    .map-footer .loc svg { width: 15px; height: 15px; stroke: #143d23; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
    .map-footer .loc span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .leaflet-container { font-family: inherit; }

    /* Locked official address field */
    .locked-input { position: relative; }
    .locked-input input[readonly] {
      width: 100%; padding: 10px 40px 10px 12px; border: 1px solid #e2e8f0; border-radius: 10px;
      font-size: 13px; color: #475569; background: #f6f8f7; cursor: not-allowed; outline: none;
    }
    .locked-input .lock {
      position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
      width: 15px; height: 15px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
    }
    .map-tip {
      display: inline-flex; align-items: center; gap: 7px; font-size: 11px; color: #94a3b8; margin-top: 10px;
    }
    .map-tip svg { width: 13px; height: 13px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    /* Map editing state */
    .map-edit-badge {
      position: absolute; top: 12px; left: 50%; transform: translateX(-50%); z-index: 400;
      display: inline-flex; align-items: center; gap: 7px;
      background: #143d23; color: #ffffff; font-size: 11px; font-weight: 700;
      padding: 6px 13px; border-radius: 999px; box-shadow: 0 4px 14px rgba(20,61,35,0.3);
      animation: mapPulse 1.8s ease-in-out infinite;
    }
    .map-edit-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: #6aeb2f; }
    @keyframes mapPulse { 0%,100% { transform: translateX(-50%) scale(1); } 50% { transform: translateX(-50%) scale(1.04); } }
    .map-editing #orgMap { cursor: crosshair; }
    .btn-set.editing {
      background: #143d23; border-color: #143d23; color: #ffffff; box-shadow: none;
    }
    .btn-set.editing:hover { background: #1a4a2a; transform: none; }

    /* Place search autocomplete */
    .place-search { display: flex; gap: 8px; }
    .place-search input { flex: 1; min-width: 0; }
    .place-search .btn-set { padding: 10px 14px; font-size: 13px; }
    .place-results {
      position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 500;
      background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 30px rgba(15,23,42,0.14);
      overflow: hidden; display: none;
    }
    .place-results.show { display: block; }
    .place-result {
      display: flex; align-items: center; gap: 10px; padding: 10px 14px; cursor: pointer;
      font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; transition: background 0.12s ease;
    }
    .place-result:last-child { border-bottom: none; }
    .place-result:hover { background: #f6f8f7; }
    .place-result svg { width: 14px; height: 14px; stroke: #94a3b8; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
    .place-result .pr-name { font-weight: 600; color: #1e293b; }
    .place-result .pr-sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }

    @media (max-width: 768px) {
      .set-body { padding: 16px; }
      .set-tab { padding: 9px 12px; font-size: 12px; }
    }
  </style>

  <div class="set-page">
    <div class="set-content">
      <div class="header-row">
        <div>
          <h1>System Settings</h1>
          <p>Configure platform identity, seasonal rules, decision-support services, and security policies.</p>
        </div>
        <div class="header-actions">
          <button id="resetBtn" class="btn-set ghost">
            <svg viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
            Reset
          </button>
          <button id="saveBtn" class="btn-set">
            <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
        </div>
      </div>

      <div class="set-panel">
        <div class="set-tabs">
          <button class="set-tab active" data-tab="general">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            General
          </button>
          <button class="set-tab" data-tab="seasons">
            <svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/></svg>
            Seasons &amp; Quotation
          </button>
          <button class="set-tab" data-tab="recommendations">
            <svg viewBox="0 0 24 24"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 1 7 7c0 2.38-1.19 4.47-3 5.74V17a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1v-2.26C6.19 13.47 5 11.38 5 9a7 7 0 0 1 7-7z"/></svg>
            Recommendations &amp; AI
          </button>
          <button class="set-tab" data-tab="security">
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Security &amp; System
          </button>
          <button class="set-tab" data-tab="interface">
            <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
            Interface
          </button>
        </div>

        <div class="set-body">

          <!-- GENERAL -->
          <div class="set-tab-panel active" id="tab-general">
            <div class="set-section">
              <div class="set-section-title">Platform Identity</div>
              <div class="set-grid">
                <div class="set-card">
                  <div class="set-field">
                    <label>Organization Name</label>
                    <input type="text" name="org_name" value="{{ $defaults['org_name'] }}">
                  </div>
                  <div class="set-field">
                    <label>System Name</label>
                    <input type="text" name="system_name" value="{{ $defaults['system_name'] }}">
                  </div>
                </div>
                <div class="set-card">
                  <div class="set-field">
                    <label>Contact Number</label>
                    <input type="text" name="contact" value="{{ $defaults['contact'] }}">
                  </div>
                  <div class="set-field">
                    <label>Website / Page</label>
                    <input type="text" name="website" value="facebook.com/aquilala">
                  </div>
                </div>
              </div>
            </div>

            <div class="set-section">
              <div class="set-section-title">Regional Formatting</div>
              <div class="set-grid">
                <div class="set-card">
                  <div class="set-field">
                    <label>Currency</label>
                    <select name="currency">
                      <option selected>{{ $defaults['currency'] }}</option>
                      <option>US Dollar (USD)</option>
                    </select>
                    <div class="hint">Used for product prices and cost quotations across the platform.</div>
                  </div>
                </div>
                <div class="set-card">
                  <div class="set-field">
                    <label>Timezone</label>
                    <select name="timezone">
                      <option selected>{{ $defaults['timezone'] }}</option>
                      <option>UTC</option>
                    </select>
                    <div class="hint">Applies to timestamps, quotation validity, and scheduled alerts.</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="set-section">
              <div class="set-section-title">Organization Location</div>
              <div class="set-card map-card">
                <div class="map-wrap" id="mapWrap">
                  <div class="map-fallback" id="mapFallback">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Loading map… (requires internet)</span>
                  </div>
                  <div id="orgMap" style="display:none;"></div>
                  <div class="map-edit-badge" id="mapEditBadge" style="display:none;">
                    <span class="dot"></span> Editing — click the map to set the pin
                  </div>
                </div>
                <div class="map-footer">
                  <div class="loc">
                    <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span id="mapAddress">Aquilala Irrigators Association Inc., Bulan, Sorsogon</span>
                  </div>
                  <button id="mapEditBtn" class="btn-set ghost">
                    <svg id="mapEditIcon" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                    <span id="mapEditLabel">Edit Location</span>
                  </button>
                </div>
              </div>
              <div class="map-tip">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                The map opens in preview (zoomed to the official location). Click “Edit Location” to drop a pin or search for a new one.
              </div>
              <div class="set-grid" style="margin-top:14px;">
                <div class="set-card">
                  <div class="set-field">
                    <label>Official Confirmed Address</label>
                    <div class="locked-input">
                      <input type="text" name="official_address" value="{{ $defaults['official_address'] }}" readonly>
                      <svg class="lock" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div class="hint">Locked — only updated by dropping a pin or choosing a searched place. It cannot be typed.</div>
                  </div>
                </div>
                <div class="set-card">
                  <div class="set-field" style="position:relative;">
                    <label>Search Location</label>
                    <div class="place-search">
                      <input type="text" id="placeInput" name="address" value="{{ $defaults['address'] }}" placeholder="Type a barangay, street, or landmark…" autocomplete="off">
                      <button type="button" id="placeSearchBtn" class="btn-set ghost" style="padding:10px 14px; font-size:13px;">Search</button>
                    </div>
                    <div class="place-results" id="placeResults"></div>
                    <div class="hint">Type a place and pick a match — the pin moves there and it is confirmed as the official address.</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SEASONS & QUOTATION -->
          <div class="set-tab-panel" id="tab-seasons">
            <div class="set-section">
              <div class="set-section-title">Cropping Seasons</div>
              <div class="set-grid">
                <div class="set-card">
                  <div class="set-field">
                    <label>Wet Season Window</label>
                    <div class="season-dates">
                      <select name="wet_start">
                        @foreach($months as $m)
                          <option value="{{ $m }}" @selected($m === $defaults['wet_start'])>{{ $m }}</option>
                        @endforeach
                      </select>
                      <span class="sep">to</span>
                      <select name="wet_end">
                        @foreach($months as $m)
                          <option value="{{ $m }}" @selected($m === $defaults['wet_end'])>{{ $m }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="hint">Flood-tolerant rice varieties are recommended during this window.</div>
                  </div>
                  <div class="set-field">
                    <label>Dry Season Window</label>
                    <div class="season-dates">
                      <select name="dry_start">
                        @foreach($months as $m)
                          <option value="{{ $m }}" @selected($m === $defaults['dry_start'])>{{ $m }}</option>
                        @endforeach
                      </select>
                      <span class="sep">to</span>
                      <select name="dry_end">
                        @foreach($months as $m)
                          <option value="{{ $m }}" @selected($m === $defaults['dry_end'])>{{ $m }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
                <div class="set-card">
                  <div class="set-field">
                    <label>Active Season</label>
                    <select name="active_season">
                      <option value="auto">Auto-detect from current date</option>
                      <option value="wet" @selected($defaults['active_season'] === 'wet')>Wet Season</option>
                      <option value="dry" @selected($defaults['active_season'] === 'dry')>Dry Season</option>
                    </select>
                    <div class="hint">Determines season-specific seed and fertilizer recommendations.</div>
                  </div>
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Recommend seasonal varieties automatically</b>
                        <span>Suggest flood-tolerant seeds for wet season and drought-tolerant for dry.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="season_reco" checked><span class="slider"></span></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="set-section">
              <div class="set-section-title">Quotation Rules</div>
              <div class="set-grid">
                <div class="set-card">
                  <div class="set-field">
                    <label>Quotation Validity (days)</label>
                    <input type="number" name="quotation_days" value="{{ $defaults['quotation_days'] }}" min="1" max="30">
                    <div class="hint">Typical window is 3–7 days to protect against price fluctuations.</div>
                  </div>
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Auto-expire quotations</b>
                        <span>Mark quotations as expired once past the validity window.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="auto_expire" checked><span class="slider"></span></label>
                    </div>
                  </div>
                </div>
                <div class="set-card">
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Use current prices on generation</b>
                        <span>Quotations reflect live store pricing and stock availability.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="apply_current" checked><span class="slider"></span></label>
                    </div>
                  </div>
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Record season on quotation</b>
                        <span>Attach Wet or Dry season to each quotation for future reference.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="quote_season" checked><span class="slider"></span></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- RECOMMENDATIONS & AI -->
          <div class="set-tab-panel" id="tab-recommendations">
            <div class="set-section">
              <div class="set-section-title">Decision Support</div>
              <div class="set-grid">
                <div class="set-card">
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Rule-based recommendations</b>
                        <span>Compute farm input quantities from crop type, land area, and season.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="enable_reco" checked><span class="slider"></span></label>
                    </div>
                  </div>
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Default low-stock reorder point</b>
                        <span>Applied when a Store Owner adds a product without a custom threshold.</span>
                      </div>
                      <div style="width: 90px;">
                        <input type="number" name="reorder_default" value="{{ $defaults['reorder_default'] }}" min="0">
                      </div>
                    </div>
                  </div>
                </div>
                <div class="set-card">
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>LLM explanation (Groq Cloud)</b>
                        <span>Convert structured outputs into natural, friendly explanations for users.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="enable_llm" checked><span class="slider"></span></label>
                    </div>
                  </div>
                  <div class="set-field">
                    <label>Groq API Key</label>
                    <input type="password" class="mono" name="llm_key" value="{{ $defaults['llm_key'] }}">
                    <div class="hint">Stored encrypted. Keys are never shown in full.</div>
                  </div>
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Plain-text fallback</b>
                        <span>Show basic formatted text when the API is unreachable.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="llm_fallback" checked><span class="slider"></span></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SECURITY & SYSTEM -->
          <div class="set-tab-panel" id="tab-security">
            <div class="set-section">
              <div class="set-section-title">Session &amp; Authentication</div>
              <div class="set-grid">
                <div class="set-card">
                  <div class="set-field">
                    <label>Session Expiration (minutes)</label>
                    <input type="number" name="session_min" value="{{ $defaults['session_min'] }}" min="5" step="5">
                    <div class="hint">Users are signed out automatically after inactivity.</div>
                  </div>
                  <div class="set-field">
                    <label>Minimum Password Length</label>
                    <input type="number" name="min_pass_len" value="{{ $defaults['min_pass_len'] }}" min="6" max="32">
                  </div>
                </div>
                <div class="set-card">
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Verify farmer registrations</b>
                        <span>New farmer accounts require Admin approval before they can sign in.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="verify_farmers" checked><span class="slider"></span></label>
                    </div>
                  </div>
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Offline caching</b>
                        <span>Allow browsers to cache quotations and reports using local storage.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="offline_cache" checked><span class="slider"></span></label>
                    </div>
                  </div>
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Maintenance mode</b>
                        <span>Temporarily block non-admin access during system updates.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="maintenance"><span class="slider"></span></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- INTERFACE & DISPLAY -->
          <div class="set-tab-panel" id="tab-interface">
            <div class="set-section">
              <div class="set-section-title">Harvest Report Summary Display</div>
              <p style="font-size:12px;color:#64748b;margin:-8px 0 14px 0;max-width:760px;">
                Controls how actual yields and expenses are presented across the dashboard.
                More interface options will appear here over time.
              </p>
              <div class="set-grid">
                <div class="set-card" style="grid-column:1 / -1;">
                  <div class="set-field">
                    <label>Yield quantity unit</label>
                    <div class="unit-options">
                      <label class="unit-opt">
                        <input type="radio" name="yield_unit" value="mt" @checked($curUnit === 'mt')>
                        <span class="uo-box">
                          <span class="uo-name">Metric Tons</span>
                          <span class="uo-desc">Standard Philippine farming unit. 1 MT = 1,000 kg.</span>
                          <span class="uo-tag">e.g. 2.4 MT</span>
                        </span>
                      </label>
                      <label class="unit-opt">
                        <input type="radio" name="yield_unit" value="kg" @checked($curUnit === 'kg')>
                        <span class="uo-box">
                          <span class="uo-name">Kilograms</span>
                          <span class="uo-desc">Fine-grained detail, ideal for smaller harvests.</span>
                          <span class="uo-tag">e.g. 2,400 kg</span>
                        </span>
                      </label>
                      <label class="unit-opt">
                        <input type="radio" name="yield_unit" value="cavan" @checked($curUnit === 'cavan')>
                        <span class="uo-box">
                          <span class="uo-name">Cavan / Bag</span>
                          <span class="uo-desc">Everyday trading unit farmers use. 1 cavan ≈ 50 kg.</span>
                          <span class="uo-tag">e.g. 48 cavan</span>
                        </span>
                      </label>
                      <label class="unit-opt">
                        <input type="radio" name="yield_unit" value="auto" @checked($curUnit === 'auto')>
                        <span class="uo-box">
                          <span class="uo-name">Automatic</span>
                          <span class="uo-desc">Show small yields in kg and large yields in MT.</span>
                          <span class="uo-tag">switches below 1 MT</span>
                        </span>
                      </label>
                    </div>
                  </div>
                </div>

                <div class="set-card">
                  <div class="set-field">
                    <label>Decimal places</label>
                    <input type="number" name="yield_decimals" value="{{ $curDec }}" min="0" max="2">
                    <div class="hint">Digits after the decimal point (0–2).</div>
                  </div>
                  <div class="set-field">
                    <label>Thousands separator</label>
                    <select name="yield_number_separator">
                      <option value="comma" @selected($curSep === ',')>Comma — e.g. 2,400</option>
                      <option value="period" @selected($curSep === '.')>Period — e.g. 2.400</option>
                      <option value="none" @selected($curSep === '')>None — e.g. 2400</option>
                    </select>
                    <div class="hint">How large numbers are grouped. e.g. <b id="sepExample">2,400</b></div>
                  </div>
                </div>

                <div class="set-card">
                  <div class="set-field">
                    <div class="set-row">
                      <div class="info">
                        <b>Dash when there are no reports</b>
                        <span>Show — on the Total Yield row instead of 0 when no harvest reports exist.</span>
                      </div>
                      <label class="switch"><input type="checkbox" name="yield_zero_dash" @checked($curZero)><span class="slider"></span></label>
                    </div>
                  </div>
                  <div class="iface-preview">
                    <div class="pv-label">Live preview — Harvest Report Summary</div>
                    <div class="pv-line"><span class="pv-name">Total Yield</span><span class="pv-val" id="pvTotal">2.4 MT</span></div>
                    <div class="pv-line"><span class="pv-name">Rice</span><span class="pv-val" id="pvRice">1.6 MT</span></div>
                    <div class="pv-line"><span class="pv-name">Sweet Corn</span><span class="pv-val" id="pvCorn">0.85 MT</span></div>
                    <div class="pv-line" style="opacity:0.7;"><span class="pv-name">No reports yet</span><span class="pv-val" id="pvZero">—</span></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="set-toast" id="setToast">
    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="setToastMsg">Settings saved successfully.</span>
  </div>

  <form id="csrfForm" style="display:none;">@csrf</form>

  <script>
    (function () {
      // Tab switching
      var tabs = Array.prototype.slice.call(document.querySelectorAll('.set-tab'));
      var panels = Array.prototype.slice.call(document.querySelectorAll('.set-tab-panel'));
      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          tabs.forEach(function (t) { t.classList.remove('active'); });
          panels.forEach(function (p) { p.classList.remove('active'); });
          tab.classList.add('active');
          document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
        });
      });

      // Reset to defaults
      function csrfToken() {
        var el = document.getElementById('csrfForm').querySelector('input[name=_token]');
        return el ? el.value : '';
      }

      function fmtNum(n, dec, sepKey) {
        var sep = sepKey === 'none' ? '' : (sepKey === 'period' ? '.' : ',');
        var decPoint = sep === '.' ? ',' : '.';
        var f = Number(n).toFixed(dec);
        var parts = f.split('.');
        return parts[1]
          ? parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep) + decPoint + parts[1]
          : parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, sep);
      }

      function fmtYield(mt, unit, dec, sepKey) {
        if (mt === 0) return '—';
        if (unit === 'auto') unit = mt < 1 ? 'kg' : 'mt';
        var num = unit === 'kg' ? mt * 1000 : (unit === 'cavan' ? mt * 20 : mt);
        var suffix = unit === 'kg' ? 'kg' : (unit === 'cavan' ? 'cavan' : 'MT');
        return fmtNum(num, dec, sepKey) + ' ' + suffix;
      }

      function updatePreview() {
        var unit = document.querySelector('input[name=yield_unit]:checked');
        if (!unit) return;
        var dec = parseInt(document.querySelector('input[name=yield_decimals]').value, 10) || 0;
        var sepKey = document.querySelector('select[name=yield_number_separator]').value;
        var zeroDash = document.querySelector('input[name=yield_zero_dash]').checked;
        document.getElementById('pvTotal').textContent = fmtYield(2.37, unit.value, dec, sepKey);
        document.getElementById('pvRice').textContent = fmtYield(1.6, unit.value, dec, sepKey);
        document.getElementById('pvCorn').textContent = fmtYield(0.85, unit.value, dec, sepKey);
        document.getElementById('pvZero').textContent = zeroDash ? '—' : fmtYield(0, unit.value, dec, sepKey);
        document.getElementById('sepExample').textContent = fmtNum(2400, 0, sepKey);
      }

      function setDefaults() {
        document.querySelectorAll('[name]').forEach(function (el) {
          var map = {
            org_name: '{{ $defaults["org_name"] }}',
            system_name: '{{ $defaults["system_name"] }}',
            address: '{{ $defaults["address"] }}',
            official_address: '{{ $defaults["official_address"] }}',
            contact: '{{ $defaults["contact"] }}',
            website: '{{ $defaults["website"] }}',
            currency: '{{ $defaults["currency"] }}',
            timezone: '{{ $defaults["timezone"] }}',
            wet_start: '{{ $defaults["wet_start"] }}',
            wet_end: '{{ $defaults["wet_end"] }}',
            dry_start: '{{ $defaults["dry_start"] }}',
            dry_end: '{{ $defaults["dry_end"] }}',
            active_season: '{{ $defaults["active_season"] }}',
            quotation_days: '{{ $defaults["quotation_days"] }}',
            reorder_default: '{{ $defaults["reorder_default"] }}',
            session_min: '{{ $defaults["session_min"] }}',
            min_pass_len: '{{ $defaults["min_pass_len"] }}',
            llm_key: '{{ $defaults["llm_key"] }}',
            yield_unit: 'mt',
            yield_decimals: '1',
            yield_number_separator: 'comma'
          };
          if (el.type === 'checkbox') {
            var checks = {
              auto_expire: {{ $defaults['auto_expire'] ? 'true' : 'false' }},
              apply_current: {{ $defaults['apply_current'] ? 'true' : 'false' }},
              season_reco: true,
              quote_season: true,
              enable_reco: {{ $defaults['enable_reco'] ? 'true' : 'false' }},
              enable_llm: {{ $defaults['enable_llm'] ? 'true' : 'false' }},
              llm_fallback: {{ $defaults['llm_fallback'] ? 'true' : 'false' }},
              verify_farmers: {{ $defaults['verify_farmers'] ? 'true' : 'false' }},
              offline_cache: {{ $defaults['offline_cache'] ? 'true' : 'false' }},
              maintenance: {{ $defaults['maintenance'] ? 'true' : 'false' }},
              yield_zero_dash: false
            };
            el.checked = checks[el.name] || false;
          } else if (el.type === 'radio') {
            var rv = map[el.name];
            if (rv !== undefined) el.checked = (el.value === rv);
          } else if (el.tagName === 'SELECT') {
            var sv = map[el.name];
            if (sv) el.value = sv;
          } else if (el.type === 'number' || el.type === 'text' || el.type === 'password') {
            var v = map[el.name];
            if (v !== undefined) el.value = v;
          }
        });
      }

      // Save -> toast
      var toast = document.getElementById('setToast');
      var toastMsg = document.getElementById('setToastMsg');
      var toastTimer = null;
      function showToast(msg) {
        toastMsg.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2600);
      }

      document.getElementById('saveBtn').addEventListener('click', function () {
        var btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving…';
        var payload = {
          yield_unit: document.querySelector('input[name=yield_unit]:checked').value,
          yield_decimals: document.querySelector('input[name=yield_decimals]').value,
          yield_number_separator: document.querySelector('select[name=yield_number_separator]').value,
          yield_zero_dash: document.querySelector('input[name=yield_zero_dash]').checked
        };
        fetch('{{ route("admin.settings.interface") }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
          body: JSON.stringify(payload)
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.ok) showToast('Interface settings saved. They apply everywhere immediately.');
            else showToast('Saved settings failed to apply.');
          })
          .catch(function () { showToast('Failed to save — check your connection.'); })
          .finally(function () {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
          });
      });
      document.getElementById('resetBtn').addEventListener('click', function () {
        setDefaults();
        if (map) {
          setOfficialAddress('{{ $defaults["official_address"] }}');
          placePin(ORG_LAT, ORG_LNG, true);
        }
        updatePreview();
        showToast('Settings restored to defaults. Click Save to apply.');
      });

      // Live preview + interface field listeners
      document.querySelectorAll('input[name=yield_unit]').forEach(function (r) {
        r.addEventListener('change', updatePreview);
      });
      document.querySelector('input[name=yield_decimals]').addEventListener('input', updatePreview);
      document.querySelector('select[name=yield_number_separator]').addEventListener('change', updatePreview);
      document.querySelector('input[name=yield_zero_dash]').addEventListener('change', updatePreview);
      updatePreview();

      // Organization location map (OpenStreetMap + Leaflet, free, no API key)
      var ORG_LAT = 12.6747;
      var ORG_LNG = 123.8767;
      var map = null;
      var marker = null;

      function makePin() {
        return L.divIcon({
          className: 'org-pin',
          html: '<svg viewBox="0 0 24 24" width="36" height="36"><path fill="#143d23" stroke="#ffffff" stroke-width="1.4" d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/><circle cx="12" cy="9" r="2.6" fill="#6aeb2f"/></svg>',
          iconSize: [36, 36],
          iconAnchor: [18, 36],
          popupAnchor: [0, -32]
        });
      }

      function loadLeaflet(callback) {
        if (window.L) { callback(); return; }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);
        var s = document.createElement('script');
        s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        s.onload = callback;
        s.onerror = function () {
          document.getElementById('mapFallback').innerHTML =
            '<span>Map unavailable — check your internet connection.</span>';
        };
        document.head.appendChild(s);
      }

      var editing = false;
      var placeTimer = null;
      var placeCache = [];

      function initMap() {
        var fallback = document.getElementById('mapFallback');
        var mapEl = document.getElementById('orgMap');
        mapEl.style.display = 'block';
        fallback.style.display = 'none';
        map = L.map('orgMap').setView([ORG_LAT, ORG_LNG], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        map.on('click', function (e) {
          if (!editing) { showToast('Click “Edit Location” to change the pin.'); return; }
          placePin(e.latlng.lat, e.latlng.lng, false);
          reverseGeocode(e.latlng.lat, e.latlng.lng);
        });
        placePin(ORG_LAT, ORG_LNG, false);
      }

      function placePin(lat, lng, zoomTo) {
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng], { icon: makePin() }).addTo(map)
          .bindPopup('<b>Aquilala Irrigators Association Inc.</b><br>Bulan, Sorsogon');
        marker.openPopup();
        if (zoomTo) map.setView([lat, lng], 16);
      }

      function setOfficialAddress(name) {
        document.querySelector('[name=official_address]').value = name;
        document.getElementById('mapAddress').textContent = name;
      }

      function applyPlace(lat, lng, name, toastMsg) {
        placePin(lat, lng, true);
        setOfficialAddress(name);
        closeResults();
        if (toastMsg) showToast(toastMsg);
      }

      function reverseGeocode(lat, lng) {
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&zoom=16&lat=' + lat + '&lon=' + lng)
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.display_name) {
              setOfficialAddress(data.display_name);
              showToast('Official address confirmed from pin.');
            }
          })
          .catch(function () { /* keep current address if reverse lookup fails */ });
      }

      document.getElementById('mapEditBtn').addEventListener('click', function () {
        editing = !editing;
        document.getElementById('mapWrap').classList.toggle('map-editing', editing);
        document.getElementById('mapEditBtn').classList.toggle('editing', editing);
        document.getElementById('mapEditBadge').style.display = editing ? 'flex' : 'none';
        document.getElementById('mapEditLabel').textContent = editing ? 'Done Editing' : 'Edit Location';
        showToast(editing ? 'Editing on — click the map to drop a pin.' : 'Editing off — map changes are locked.');
      });

      function closeResults() {
        var box = document.getElementById('placeResults');
        box.classList.remove('show');
        box.innerHTML = '';
      }

      function renderResults(data, q) {
        var box = document.getElementById('placeResults');
        box.innerHTML = '';
        if (!data || !data.length) {
          box.innerHTML = '<div class="place-result" style="color:#94a3b8;cursor:default;">No matches found for “' + q + '”</div>';
          box.classList.add('show');
          placeCache = [];
          return;
        }
        placeCache = data;
        data.forEach(function (item, i) {
          var row = document.createElement('div');
          row.className = 'place-result';
          row.innerHTML = '<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
            '<div><div class="pr-name">' + item.name + '</div>' +
            '<div class="pr-sub">' + (item.display_name || item.name) + '</div></div>';
          row.addEventListener('click', function () {
            applyPlace(parseFloat(item.lat), parseFloat(item.lon), item.display_name,
              'Official address updated to “' + item.name + '”.');
            document.getElementById('placeInput').value = item.name;
          });
          box.appendChild(row);
        });
        box.classList.add('show');
      }

      function searchPlace(q, immediate) {
        var input = document.getElementById('placeInput');
        var btn = document.getElementById('placeSearchBtn');
        if (immediate) {
          btn.disabled = true;
          btn.textContent = 'Searching…';
        }
        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&addressdetails=1&countrycodes=ph&q=' +
              encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (immediate) {
              if (data && data.length) {
                applyPlace(parseFloat(data[0].lat), parseFloat(data[0].lon), data[0].display_name,
                  'Official address updated to “' + data[0].name + '”.');
                input.value = data[0].name;
              } else {
                showToast('Location not found. Try a more specific address.');
              }
            } else {
              renderResults(data, q);
            }
          })
          .catch(function () { showToast('Search unavailable — check your internet connection.'); })
          .finally(function () {
            if (immediate) { btn.disabled = false; btn.textContent = 'Search'; }
          });
      }

      document.getElementById('placeInput').addEventListener('input', function () {
        clearTimeout(placeTimer);
        var q = this.value.trim();
        if (q.length < 3) { closeResults(); return; }
        placeTimer = setTimeout(function () { searchPlace(q, false); }, 450);
      });

      document.getElementById('placeInput').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          if (placeCache.length) {
            var first = placeCache[0];
            this.value = first.name;
            applyPlace(parseFloat(first.lat), parseFloat(first.lon), first.display_name,
              'Official address updated to “' + first.name + '”.');
          } else {
            searchPlace(this.value.trim(), true);
          }
        }
        if (e.key === 'Escape') closeResults();
      });

      document.getElementById('placeSearchBtn').addEventListener('click', function () {
        var q = document.getElementById('placeInput').value.trim();
        if (!q) { showToast('Type a place to search.'); return; }
        searchPlace(q, true);
      });

      document.addEventListener('click', function (e) {
        if (!e.target.closest('.place-search') && !e.target.closest('.place-results')) closeResults();
      });

      loadLeaflet(initMap);
    })();
  </script>
@endsection
