{{-- Shared stylesheet for the admin settings sub-pages (General, Seasons,
     Recommendations & AI, Security, Interface). Emits the whole set of
     reusable component styles so each settings page stays lean. --}}
<style>
    .set-page { position: relative; }
    .set-page::before,
    .set-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .set-page::before { width: 320px; height: 320px; background: rgba(94,144,112,0.15); top: -80px; right: -80px; }
    .set-page::after { width: 280px; height: 280px; background: rgba(59,130,246,0.12); bottom: -60px; left: -60px; }
    .set-content { position: relative; z-index: 1; }

    .header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 10px; }
    .header-row h1 { font-size: 24px; font-weight: 700; color: #1e293b; letter-spacing: 0.02em; margin: 0 0 4px 0; }
    .header-row p { font-size: 13px; color: #64748b; margin: 0; }
    .header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-set {
      display: inline-flex; align-items: center; gap: 8px; text-decoration: none; border: 1px solid transparent;
      background: #EDF1E9; border-color: #D9E2D6;
      color: #3F6B4D; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 700;
      transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(20,61,35,0.08); cursor: pointer;
    }
    .btn-set:hover { background: #E2E9E0; transform: translateY(-1px); }
    .btn-set svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .btn-set.ghost { background: #ffffff; border-color: rgba(20,61,35,0.10); box-shadow: none; }
    .btn-set.ghost:hover { background: #f0fdf4; }
    .btn-set:disabled { background: #f1f5f9; border-color: #e2e8f0; color: #94a3b8; cursor: not-allowed; box-shadow: none; transform: none; }
    .btn-set.editing { background: #3F6B4D; border-color: #3F6B4D; color: #ffffff; box-shadow: none; }
    .btn-set.editing:hover { background: #375F43; transform: none; }

    .set-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03); overflow: hidden;
    }

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

    .set-toast {
      position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; align-items: center; gap: 10px;
      background: #143d23; color: #e2f3e6; padding: 13px 18px; border-radius: 12px; font-size: 13px; font-weight: 600;
      box-shadow: 0 12px 32px rgba(0,0,0,0.25); opacity: 0; transform: translateY(12px); pointer-events: none;
      transition: all 0.25s ease;
    }
    .set-toast.show { opacity: 1; transform: translateY(0); }
    .set-toast svg { width: 16px; height: 16px; stroke: #8FB89A; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

    /* Interface — radio unit selector */
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
      padding: 3px 8px; border-radius: 999px; background: #edf1e9; color: #648268;
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

    .map-edit-badge {
      position: absolute; top: 12px; left: 50%; transform: translateX(-50%); z-index: 400;
      display: inline-flex; align-items: center; gap: 7px;
      background: #143d23; color: #ffffff; font-size: 11px; font-weight: 700;
      padding: 6px 13px; border-radius: 999px; box-shadow: 0 4px 14px rgba(20,61,35,0.3);
      animation: mapPulse 1.8s ease-in-out infinite;
    }
    .map-edit-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: #8FB89A; }
    @keyframes mapPulse { 0%,100% { transform: translateX(-50%) scale(1); } 50% { transform: translateX(-50%) scale(1.04); } }
    .map-editing #orgMap { cursor: crosshair; }

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
    }
</style>