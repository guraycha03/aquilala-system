{{-- Light, breathable visual language for the harvest report workspace.
     Strong/dark fills are intentionally avoided: the page leans on space,
     thin borders, and the portal's soft sage tone. --}}
<svg aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden">
  <symbol id="ahr-i-moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></symbol>
  <symbol id="ahr-i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></symbol>
  <symbol id="ahr-i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
  <symbol id="ahr-i-reset" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></symbol>
  <symbol id="ahr-i-layers" viewBox="0 0 24 24"><path d="m12 3 9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 16l9 5 9-5"/></symbol>
  <symbol id="ahr-i-chevron-down" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></symbol>
  <symbol id="ahr-i-rain" viewBox="0 0 24 24"><path d="M17 17H6a4 4 0 0 1 .9-7.9A6 6 0 0 1 18 10a3.5 3.5 0 0 1-.5 7zM8 19v2M12 19v2M16 19v2"/></symbol>
  <symbol id="ahr-i-chart" viewBox="0 0 24 24"><path d="M4 20h16M6 16v-5M11 16V7M16 16v-8M21 16v-3"/></symbol>
  <symbol id="ahr-i-download" viewBox="0 0 24 24"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v3h16v-3"/></symbol>
  <symbol id="ahr-i-bookmark" viewBox="0 0 24 24"><path d="M6 3h12v18l-6-4-6 4z"/></symbol>
  <symbol id="ahr-i-trash" viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14M10 11v6M14 11v6"/></symbol>
  <symbol id="ahr-i-arrow" viewBox="0 0 24 24"><path d="M5 12h14m0 0-5-5m5 5-5 5"/></symbol>
  <symbol id="ahr-i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></symbol>
  <symbol id="ahr-i-sort" viewBox="0 0 24 24"><path d="M7 4v16m0 0-3-3m3 3 3-3M17 20V4m0 0-3 3m3-3 3 3"/></symbol>
  <symbol id="ahr-i-alert" viewBox="0 0 24 24"><path d="M10.3 3.9 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0zM12 9v5M12 17h.01"/></symbol>
  <symbol id="ahr-i-trend" viewBox="0 0 24 24"><path d="m3 17 6-6 4 4 8-8M15 7h6v6"/></symbol>
  <symbol id="ahr-i-file" viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5M9 13h6M9 17h4"/></symbol>
  <symbol id="ahr-i-sparkles" viewBox="0 0 24 24"><path d="m12 3 1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8zM19 15l.9 2.1 2.1.9-2.1.9L19 21l-.9-2.1-2.1-.9 2.1-.9zM5 15l.7 1.6L7.3 17.3l-1.6.7L5 19.6l-.7-1.6-1.6-.7 1.6-.7z"/></symbol>
</svg>

<style>
  :root { --ahr-header-offset: 60px; }
  .ahr-page {
    --ahr-bg: var(--aq-bg, #f4f5f0);
    --ahr-surface: #ffffff;
    --ahr-surface-soft: var(--aq-sage-50, #f1f4ee);
    --ahr-surface-sunken: var(--aq-sage-100, #e6ebe2);
    --ahr-line: #edf0ea;
    --ahr-line-strong: #dee5db;
    --ahr-line-sage: #cfdccb;
    --ahr-line-sage-strong: #b9cbb5;
    --ahr-ink: var(--aq-ink, #2c3530);
    --ahr-muted: var(--aq-ink-soft, #5c6d61);
    --ahr-faint: var(--aq-ink-faint, #879a8c);
    --ahr-primary: var(--aq-sage-700, #3f5a46);
    --ahr-primary-hover: var(--aq-sage-600, #4f6a56);
    --ahr-primary-soft: var(--aq-sage-50, #eef4ef);
    --ahr-primary-text: var(--aq-sage-700, #2f5d3f);
    --ahr-ring: rgba(185, 203, 181, .22);
    --ahr-wet: var(--aq-sage-500, #648268);
    --ahr-dry: var(--aq-sage-400, #8a9c78);
    --ahr-warm: var(--aq-ink-soft, #5c6d61);
    --ahr-danger: #b91c1c;
    --ahr-radius: 14px;
    --ahr-radius-sm: 9px;
    color: var(--ahr-ink);
  }
  html[data-theme="dark"] .ahr-page {
    --ahr-surface: #111827;
    --ahr-surface-soft: #1b2a20;
    --ahr-surface-sunken: #233328;
    --ahr-line: #2b3b31;
    --ahr-line-strong: #3a4c40;
    --ahr-line-sage: #405e49;
    --ahr-line-sage-strong: #56775e;
    --ahr-ink: #d6f5e3;
    --ahr-muted: #94a3b8;
    --ahr-faint: #6b7280;
    --ahr-primary: #8fd6a3;
    --ahr-primary-hover: #6ee7a0;
    --ahr-primary-soft: #1d3527;
    --ahr-primary-text: #d6f5e3;
    --ahr-ring: rgba(120, 170, 130, .20);
    --ahr-wet: #6ee7a0;
    --ahr-dry: #9ec98a;
    --ahr-warm: #94a3b8;
    --ahr-danger: #f87171;
  }

  .ahr-page svg.ahr-icon { width: 16px; height: 16px; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
  .ahr-page svg.ahr-icon.sm { width: 14px; height: 14px; }
  .ahr-page svg.ahr-icon.lg { width: 20px; height: 20px; }

  /* Quiet top navigation — no sticky blur, no heavy surface. */
  .ahr-topbar { margin: 0 0 24px; padding: 10px 0 0; border-bottom: 1px solid var(--ahr-line); background: transparent; }
  .ahr-topbar-inner { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
  .ahr-tabs { display: flex; align-items: center; gap: 24px; overflow-x: auto; max-width: 100%; }
  .ahr-tab {
    border: 0; border-bottom: 2px solid transparent; background: none;
    padding: 8px 0; white-space: nowrap; font: inherit;
    font-size: 13px; font-weight: 600; color: var(--ahr-muted); text-decoration: none;
  }
  .ahr-tab:hover { color: var(--ahr-primary); }
  .ahr-tab.active { border-bottom-color: var(--ahr-line-sage); color: var(--ahr-primary); font-weight: 700; }
  .ahr-top-actions { display: flex; align-items: center; gap: 8px; }

  .ahr-btn {
    appearance: none; -webkit-appearance: none;
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    min-height: 36px; padding: 0 14px; border-radius: var(--ahr-radius-sm);
    border: 1px solid var(--ahr-line-sage); background: var(--ahr-surface);
    color: var(--ahr-ink); font: inherit; font-size: 12px; font-weight: 600;
    text-decoration: none; cursor: pointer; white-space: nowrap;
  }
  .ahr-btn:hover { background: var(--ahr-surface-soft); border-color: var(--ahr-line-sage); color: var(--ahr-primary); }
  .ahr-btn:active { transform: translateY(1px); background: var(--ahr-surface-sunken); border-color: var(--ahr-line-sage); box-shadow: 0 0 0 3px var(--ahr-ring); }
  .ahr-btn[aria-pressed="true"] { background: var(--ahr-primary-soft); border-color: var(--ahr-line-sage-strong); color: var(--ahr-primary-text); }
  .ahr-btn.primary { background: var(--ahr-primary-soft); border-color: var(--ahr-line-sage); color: var(--ahr-primary-text); }
  .ahr-btn.primary:hover { background: var(--ahr-surface-sunken); }
  .ahr-btn.danger:hover { border-color: var(--ahr-danger); color: var(--ahr-danger); background: transparent; }
  .ahr-btn:disabled { opacity: .5; cursor: not-allowed; }

  .ahr-main { max-width: 1180px; margin: 0 auto; }
  .ahr-page-head { margin-bottom: 18px; }
  .ahr-eyebrow { font-size: 11.5px; font-weight: 600; color: var(--ahr-muted); }
  .ahr-h1 { margin: 3px 0 0; font-size: clamp(22px, 2vw, 27px); line-height: 1.2; letter-spacing: -.01em; font-weight: 700; }
  .ahr-live-pill { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; padding: 4px 10px; border: 1px solid var(--ahr-line); border-radius: 999px; font-size: 11px; font-weight: 600; background: var(--ahr-primary-soft); color: var(--ahr-primary); }
  .ahr-stack { display: flex; flex-direction: column; gap: 18px; }

  .ahr-card { background: var(--ahr-surface); border: 1px solid var(--ahr-line); border-radius: var(--ahr-radius); }
  .ahr-card-head { padding: 18px 22px; border-bottom: 1px solid var(--ahr-line); display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .ahr-card-title { margin: 0; font-size: 14px; font-weight: 700; }
  .ahr-sub { margin: 4px 0 0; font-size: 12px; color: var(--ahr-muted); line-height: 1.55; }
  .ahr-card-body { padding: 20px 22px; }
  .ahr-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

  /* Scope card — one calm panel, no numbered steps or dark tiles. */
  .ahr-scope-band { padding: 16px 22px; border-top: 1px solid var(--ahr-line); }
  .ahr-band + .ahr-band { margin-top: 0; padding-top: 16px; border-top: 1px solid var(--ahr-line); }
  .ahr-band-head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
  .ahr-band-label { font-size: 11.5px; font-weight: 700; color: var(--ahr-primary); }
  .ahr-quick { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 11px; color: var(--ahr-muted); }
  .ahr-link-btn { border: 0; background: none; padding: 0; cursor: pointer; font: inherit; font-size: 11.5px; font-weight: 600; color: var(--ahr-primary); text-decoration: none; }
  .ahr-link-btn:hover { color: var(--ahr-primary-hover); text-decoration: none; }
  .ahr-quick-sep { color: var(--ahr-line-strong); }
  .ahr-toggle-chevron { transition: transform .18s ease; }
  .ahr-btn.ahr-collapsed .ahr-toggle-chevron { transform: rotate(180deg); }
  .ahr-year-grid { display: flex; flex-wrap: wrap; gap: 8px; }
  .ahr-filter-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
  .ahr-year-chip, .ahr-season-btn, .ahr-crop-chip {
    transition: border-color .15s ease, background .15s ease, color .15s ease, box-shadow .15s ease, transform .15s ease;
  }
  .ahr-year-chip {
    min-height: 36px; padding: 0 14px; border-radius: var(--ahr-radius-sm);
    border: 1px solid var(--ahr-line); background: var(--ahr-surface);
    display: inline-flex; align-items: center;
    font: inherit; font-size: 12px; font-weight: 700; color: var(--ahr-muted);
    font-variant-numeric: tabular-nums; cursor: pointer;
  }
  .ahr-year-chip:hover { border-color: var(--ahr-line-sage); background: var(--ahr-surface-soft); color: var(--ahr-primary); transform: translateY(-1px); }
  .ahr-year-chip.active { border-color: var(--ahr-line-sage); background: var(--ahr-primary-soft); color: var(--ahr-primary-text); box-shadow: 0 0 0 3px var(--ahr-ring); }
  .ahr-season-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
  .ahr-season-btn {
    min-height: 40px; border: 1px solid var(--ahr-line); border-radius: var(--ahr-radius-sm); background: var(--ahr-surface);
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    font: inherit; font-size: 12px; font-weight: 600; color: var(--ahr-muted); text-decoration: none; cursor: pointer;
  }
  .ahr-season-btn:hover { border-color: var(--ahr-line-sage); background: var(--ahr-surface-soft); color: var(--ahr-primary); transform: translateY(-1px); }
  .ahr-season-btn.active { background: var(--ahr-primary-soft); color: var(--ahr-primary-text); border-color: var(--ahr-line-sage); box-shadow: 0 0 0 3px var(--ahr-ring); }
  .ahr-crop-list { display: flex; flex-wrap: wrap; gap: 8px; }
  .ahr-crop-chip {
    min-height: 36px; padding: 0 12px; border-radius: var(--ahr-radius-sm);
    border: 1px solid var(--ahr-line); background: var(--ahr-surface);
    display: inline-flex; align-items: center; gap: 8px;
    font: inherit; font-size: 12px; font-weight: 600; color: var(--ahr-muted); text-decoration: none; cursor: pointer;
  }
  .ahr-crop-chip:hover { border-color: var(--ahr-line-sage); background: var(--ahr-surface-soft); color: var(--ahr-primary); transform: translateY(-1px); }
  .ahr-crop-chip.active { border-color: var(--ahr-line-sage); background: var(--ahr-primary-soft); color: var(--ahr-primary-text); box-shadow: 0 0 0 3px var(--ahr-ring); }
  .ahr-crop-meta { font-weight: 500; font-variant-numeric: tabular-nums; opacity: .75; }
  .ahr-scope-footer { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 14px 22px; border-top: 1px solid var(--ahr-line); }
  .ahr-live-scope { font-size: 11.5px; color: var(--ahr-muted); font-variant-numeric: tabular-nums; }
  .ahr-live-scope strong { color: var(--ahr-ink); }
  .ahr-filter-updated { animation: ahr-filter-pulse .35s ease-out; }
  @keyframes ahr-filter-pulse {
    0% { color: var(--ahr-primary); background: var(--ahr-primary-soft); box-shadow: 0 0 0 4px var(--ahr-primary-soft); }
    100% { color: var(--ahr-muted); background: transparent; box-shadow: none; }
  }
  button.ahr-year-chip, button.ahr-season-btn, button.ahr-crop-chip { appearance: none; font-family: inherit; }
  .ahr-year-chip:active, .ahr-season-btn:active, .ahr-crop-chip:active { transform: scale(.96); }
  .ahr-modal { position: fixed; inset: 0; z-index: 80; display: flex; align-items: center; justify-content: center; padding: 20px; background: rgba(16, 32, 21, .45); }
  .ahr-modal[hidden] { display: none; }
  .ahr-modal-card { width: min(420px, 100%); padding: 22px; border-radius: 12px; background: var(--ahr-surface); box-shadow: 0 20px 50px rgba(12, 35, 20, .25); }
  .ahr-modal-card h2 { margin: 0 0 8px; font-size: 16px; color: var(--ahr-ink); }
  .ahr-modal-card p { margin: 0 0 18px; font-size: 12.5px; color: var(--ahr-muted); line-height: 1.6; }
  .ahr-modal-actions { display: flex; justify-content: flex-end; gap: 10px; }

  /* Centred deletion progress: keeps the action obvious while Turbo submits. */
  .ahr-delete-loading { position: fixed; inset: 0; z-index: 90; display: flex; align-items: center; justify-content: center; padding: 20px; background: rgba(16, 32, 21, .5); backdrop-filter: blur(2px); }
  .ahr-delete-loading[hidden] { display: none; }
  .ahr-delete-loading-card { width: min(340px, 100%); padding: 26px 22px; border: 1px solid var(--ahr-line-sage); border-radius: 12px; background: var(--ahr-surface); box-shadow: 0 24px 60px rgba(12, 35, 20, .28); text-align: center; }
  .ahr-delete-spinner { display: block; width: 38px; height: 38px; margin: 0 auto 15px; border: 3px solid var(--ahr-line-sage); border-top-color: var(--ahr-primary); border-radius: 50%; animation: ahr-delete-spin .75s linear infinite; }
  @keyframes ahr-delete-spin { to { transform: rotate(360deg); } }
  .ahr-delete-loading-card h2 { margin: 0 0 6px; font-size: 15px; font-weight: 800; color: var(--ahr-ink); }
  .ahr-delete-loading-card p { margin: 0; font-size: 12px; line-height: 1.55; color: var(--ahr-muted); }
  .ahr-delete-loading-card strong { color: var(--ahr-ink); }

  .ahr-generate-btn {
    appearance: none; border: 1px solid var(--ahr-line-sage); border-radius: var(--ahr-radius-sm);
    min-height: 40px; padding: 0 18px; cursor: pointer;
    background: var(--ahr-primary-soft); color: var(--ahr-primary-text);
    display: inline-flex; align-items: center; gap: 9px; font: inherit; font-size: 12.5px; font-weight: 700;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease, transform .15s ease;
  }
  .ahr-generate-btn:hover { background: var(--ahr-surface-soft); border-color: var(--ahr-line-sage); }
  .ahr-generate-btn:active { transform: translateY(1px); background: var(--ahr-primary-soft); border-color: var(--ahr-line-sage); box-shadow: 0 0 0 3px var(--ahr-ring); }
  .ahr-generate-btn:disabled { opacity: .5; cursor: not-allowed; }

  /* Preview metrics — one airy strip instead of four loud cards. */
  .ahr-kpi-toolbar { display:flex; align-items:flex-end; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:10px; }
  .ahr-year-pick { display:inline-flex; align-items:center; gap:8px; font-size:11.5px; font-weight:600; color:var(--ahr-muted); }
  .ahr-year-pick select { min-height:34px; padding:0 30px 0 10px; border:1px solid var(--ahr-line-sage); border-radius:var(--ahr-radius-sm); background:var(--ahr-surface); color:var(--ahr-ink); font:inherit; font-size:11.5px; font-weight:600; cursor:pointer; }
  .ahr-year-pick select:focus { outline:none; border-color:var(--ahr-line-sage); }
  .ahr-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); background: var(--ahr-surface); border: 1px solid var(--ahr-line); border-radius: var(--ahr-radius); overflow: hidden; }
  .ahr-kpi { padding: 18px 20px; border: 0; border-right: 1px solid var(--ahr-line); box-shadow: none; background: transparent; }
  .ahr-kpi:last-child { border-right: 0; }
  .ahr-kpi-label { font-size: 11px; font-weight: 600; color: var(--ahr-muted); }
  .ahr-kpi-main { margin-top: 8px; display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
  .ahr-kpi-value { font-size: 20px; font-weight: 700; letter-spacing: -.01em; font-variant-numeric: tabular-nums; color: var(--ahr-ink); }
  .ahr-kpi-value.accent { color: var(--ahr-primary); }
  .ahr-kpi-unit { font-size: 11.5px; font-weight: 600; color: var(--ahr-muted); }
  .ahr-kpi-note { margin-top: 6px; font-size: 10.5px; color: var(--ahr-muted); font-variant-numeric: tabular-nums; }
  .ahr-delta { display: inline-flex; align-items: center; gap: 2px; font-size: 10.5px; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--ahr-primary); }
  .ahr-delta.down { color: var(--ahr-warm); }
  .ahr-dash { color: var(--ahr-faint); font-size: 15px; }

  .ahr-report-head { padding: 20px 22px; border-bottom: 1px solid var(--ahr-line); }
  .ahr-report-kicker { font-size: 11px; color: var(--ahr-muted); }
  .ahr-report-title { margin: 6px 0 0; font-size: clamp(19px, 1.8vw, 24px); line-height: 1.25; font-weight: 700; }
  .ahr-report-subhead { margin: 6px 0 0; font-size: 12.5px; line-height: 1.65; color: var(--ahr-muted); max-width: 760px; }
  .ahr-takeaways { margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--ahr-line); display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 16px; }
  .ahr-takeaway-label { font-size: 10.5px; font-weight: 600; color: var(--ahr-muted); }
  .ahr-takeaway-value { margin-top: 4px; font-size: 17px; font-weight: 700; color: var(--ahr-primary); font-variant-numeric: tabular-nums; }
  .ahr-takeaway-text { margin-top: 3px; font-size: 11px; color: var(--ahr-muted); }
  .ahr-callouts { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
  .ahr-callout { padding: 20px 22px; }
  .ahr-callout + .ahr-callout { border-left: 1px solid var(--ahr-line); }
  .ahr-callout.peak, .ahr-callout.watch { background: var(--ahr-surface); }
  .ahr-callout-label { display: inline-flex; align-items: center; gap: 6px; font-size: 10.5px; font-weight: 700; color: var(--ahr-primary); }
  .ahr-callout.watch .ahr-callout-label { color: var(--ahr-muted); }
  .ahr-callout-metric { margin-left: auto; font-size: 11px; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--ahr-primary); }
  .ahr-callout.watch .ahr-callout-metric { color: var(--ahr-muted); }
  .ahr-callout-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
  .ahr-callout h3 { margin: 10px 0 2px; font-size: 14px; font-weight: 700; }
  .ahr-callout-context { font-size: 11px; color: var(--ahr-muted); }
  .ahr-callout-detail { margin: 8px 0 0; font-size: 12px; line-height: 1.65; }
  .ahr-detail-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 22px; padding: 20px 22px; border-top: 1px solid var(--ahr-line); }
  .ahr-mini-title { margin: 0; font-size: 13.5px; font-weight: 700; }
  .ahr-verdicts { margin-top: 10px; border-top: 1px solid var(--ahr-line); }
  .ahr-verdict { padding: 10px 0; border-bottom: 1px solid var(--ahr-line); }
  .ahr-verdict-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
  .ahr-verdict-name { font-size: 12px; font-weight: 700; }
  .ahr-verdict-metric { font-size: 10.5px; font-weight: 700; color: var(--ahr-primary); font-variant-numeric: tabular-nums; }
  .ahr-verdict-text { margin-top: 3px; font-size: 11.5px; line-height: 1.6; color: var(--ahr-muted); }
  .ahr-playbook-list { margin-top: 10px; display: flex; flex-direction: column; gap: 8px; }
  .ahr-rec { padding: 11px 13px; border: 1px solid var(--ahr-line); border-radius: var(--ahr-radius-sm); background: var(--ahr-surface-soft); }
  .ahr-rec-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
  .ahr-rec-title { font-size: 12px; font-weight: 700; }
  .ahr-priority { font-size: 10px; font-weight: 700; color: var(--ahr-primary); }
  .ahr-priority.high { color: var(--ahr-warm); }

  .ahr-placeholder { border-left: 3px solid var(--ahr-line-sage); background: var(--ahr-surface-soft); }
  .ahr-placeholder-inner { padding: 20px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
  .ahr-placeholder-inner h3 { margin: 0; font-size: 14px; font-weight: 700; }
  .ahr-empty-state { padding: 44px 22px; text-align: center; }
  .ahr-empty-state svg { color: var(--ahr-primary); }
  .ahr-empty-state h3, .ahr-empty-state h2 { margin: 10px 0 0; font-size: 16px; font-weight: 700; }
  .ahr-empty-state p { margin: 6px auto 0; max-width: 480px; font-size: 12px; color: var(--ahr-muted); line-height: 1.6; }
  .ahr-empty-state .ahr-btn { margin-top: 16px; }

  /* Tables and saved list */
  .ahr-table-card { overflow: hidden; }
  .ahr-table-toolbar { padding: 14px 18px; border-bottom: 1px solid var(--ahr-line); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
  .ahr-search { position: relative; display: inline-flex; align-items: center; }
  .ahr-search svg { position: absolute; left: 10px; color: var(--ahr-faint); pointer-events: none; }
  .ahr-search input { height: 36px; width: min(240px, 100%); padding: 0 11px 0 32px; border: 1px solid var(--ahr-line-sage); border-radius: var(--ahr-radius-sm); background: var(--ahr-surface); color: var(--ahr-ink); font: inherit; font-size: 12px; }
  .ahr-search input:focus { outline: none; border-color: var(--ahr-line-sage); }
  .ahr-table-scroll { overflow-x: auto; }
  .ahr-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
  .ahr-table th { padding: 10px 12px; text-align: left; font-weight: 700; color: var(--ahr-muted); background: var(--ahr-surface-soft); border-bottom: 1px solid var(--ahr-line); white-space: nowrap; }
  .ahr-table th.right, .ahr-table td.right { text-align: right; }
  .ahr-table td { padding: 10px 12px; border-bottom: 1px solid var(--ahr-line); vertical-align: middle; }
  .ahr-table tbody tr:last-child td { border-bottom: 0; }
  .ahr-table tbody tr:hover { background: var(--ahr-surface-soft); }
  .ahr-cell-main { font-weight: 700; }
  .ahr-cell-sub { margin-top: 2px; font-size: 10px; color: var(--ahr-muted); }
  .ahr-num { font-variant-numeric: tabular-nums; }
  .ahr-sort { display: inline-flex; align-items: center; gap: 4px; color: inherit; text-decoration: none; }
  .ahr-sort:hover { color: var(--ahr-primary); }
  .ahr-season-tag { display: inline-flex; align-items: center; gap: 5px; font-weight: 600; color: var(--ahr-muted); }
  .ahr-saved-row { padding: 16px 20px; border-bottom: 1px solid var(--ahr-line); display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
  .ahr-saved-row:last-child { border-bottom: 0; }
  .ahr-saved-row:hover { background: var(--ahr-surface-soft); }
  .ahr-saved-title { margin: 0; font-size: 13.5px; font-weight: 700; }
  .ahr-saved-headline { margin: 3px 0 0; font-size: 11.5px; color: var(--ahr-muted); }
  .ahr-saved-meta { margin-top: 5px; display: flex; flex-wrap: wrap; gap: 6px; font-size: 10.5px; color: var(--ahr-muted); }
  .ahr-alert { padding: 10px 14px; border-radius: var(--ahr-radius-sm); font-size: 12px; font-weight: 600; margin-bottom: 14px; }
  .ahr-alert.success { background: var(--ahr-primary-soft); color: var(--ahr-primary); border: 1px solid var(--ahr-line-sage); }
  .ahr-alert.error { background: transparent; color: var(--ahr-danger); border: 1px solid var(--ahr-danger); }
  .ahr-lang-switch { display: inline-flex; padding: 3px; border-radius: var(--ahr-radius-sm); background: var(--ahr-surface-soft); gap: 3px; border: 1px solid var(--ahr-line); }
  .ahr-lang-btn { border: 0; background: transparent; padding: 6px 11px; border-radius: 7px; font: inherit; font-size: 11.5px; font-weight: 600; color: var(--ahr-muted); cursor: pointer; }
  .ahr-lang-btn.active { background: var(--ahr-surface); color: var(--ahr-primary); }
  .ahr-lang-panel[hidden] { display: none !important; }
  .ahr-report-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
  .ahr-report-toolbar { max-width: 860px; margin: 0 auto 14px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
  .ahr-report-paper-host > .ahr-lang-panel + .ahr-lang-panel { margin-top: 0; }
  .ahr-report-document .ahr-report-head { padding-bottom: 16px; }
  .ahr-report-meta { margin: 8px 0 0; font-size: 11px; color: var(--ahr-faint); font-variant-numeric: tabular-nums; }
  .ahr-report-sections { padding: 18px 22px 22px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 28px; }
  .ahr-report-section h3 { margin: 0 0 7px; font-size: 12.5px; font-weight: 700; color: var(--ahr-primary); }
  .ahr-report-section ul, .ahr-report-section ol { margin: 0; padding-left: 18px; }
  .ahr-report-section li { margin: 0 0 6px; font-size: 12px; line-height: 1.6; color: var(--ahr-ink); }
  .ahr-report-section li::marker { color: var(--ahr-primary); }
  .ahr-footnote { font-size: 11px; color: var(--ahr-muted); line-height: 1.65; }

  /* Responsive: every grid collapses cleanly on small screens. */
  @media (max-width: 980px) {
    .ahr-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ahr-kpi:nth-child(2) { border-right: 0; }
    .ahr-kpi:nth-child(-n+2) { border-bottom: 1px solid var(--ahr-line); }
    .ahr-kpi:nth-child(3) { border-right: 0; border-bottom: 0; }
    .ahr-detail-grid { grid-template-columns: 1fr; }
    .ahr-report-sections { grid-template-columns: 1fr; }
  }
  @media (max-width: 760px) {
    .ahr-topbar { margin-bottom: 18px; }
    .ahr-topbar-inner { align-items: flex-start; }
    .ahr-tabs { width: 100%; }
    .ahr-card-head, .ahr-scope-band, .ahr-scope-footer, .ahr-report-head, .ahr-card-body, .ahr-detail-grid, .ahr-report-sections, .ahr-placeholder-inner { padding-left: 16px; padding-right: 16px; }
    .ahr-callout + .ahr-callout { border-left: 0; border-top: 1px solid var(--ahr-line); }
  }
  @media (max-width: 560px) {
    .ahr-kpis { grid-template-columns: 1fr; }
    .ahr-kpi { border-right: 0; border-bottom: 1px solid var(--ahr-line); }
    .ahr-kpi:nth-child(3) { border-bottom: 1px solid var(--ahr-line); }
    .ahr-kpi:last-child { border-bottom: 0; }
    .ahr-season-grid { grid-template-columns: 1fr; }
    .ahr-generate-btn, .ahr-btn, .ahr-top-actions .ahr-btn { width: 100%; }
    .ahr-year-pick, .ahr-year-pick select { width: 100%; }
    .ahr-top-actions { width: 100%; }
    .ahr-report-actions { justify-content: flex-start; }
    .ahr-confirm-actions { flex-direction: column-reverse; }
    .ahr-confirm-actions .ahr-btn { width: 100%; }
    .ahr-download-toast { left: 16px; right: 16px; bottom: 16px; max-width: none; }
    .ahr-scope-footer { align-items: stretch; flex-direction: column; }
    .ahr-generate-btn { justify-content: center; }
    .ahr-page-head { margin-bottom: 14px; }
  }

  .ahr-generating-overlay {
    position: fixed; inset: 0; z-index: 200;
    display: flex; align-items: center; justify-content: center;
    padding: 20px; background: rgba(15, 23, 42, 0.48);
    -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px);
  }
  .ahr-generating-overlay[hidden] { display: none; }
  .ahr-generating-card {
    width: min(420px, 100%); padding: 28px; text-align: center;
    background: var(--ahr-surface); border: 1px solid var(--ahr-line);
    border-radius: 16px; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.24);
  }
  .ahr-generating-spinner {
    width: 44px; height: 44px; border-radius: 50%; display: inline-block;
    border: 4px solid var(--ahr-line); border-top-color: var(--ahr-line-sage-strong);
    animation: ahr-spin 0.8s linear infinite;
  }
  @keyframes ahr-spin { to { transform: rotate(360deg); } }
  .ahr-generating-card h2 { margin: 16px 0 6px; font-size: 18px; font-weight: 700; color: var(--ahr-ink); }
  .ahr-generating-card p { margin: 0; font-size: 13px; line-height: 1.5; color: var(--ahr-muted); }
  .ahr-generating-track {
    display: block; height: 4px; margin-top: 18px; border-radius: 999px;
    background: var(--ahr-line); overflow: hidden;
  }
  .ahr-generating-track::after { content: ''; display: block; width: 35%; height: 100%; border-radius: inherit; background: var(--ahr-primary); animation: ahr-progress 1.1s ease-in-out infinite; }
  @keyframes ahr-progress { 0% { transform: translateX(-100%); } 100% { transform: translateX(300%); } }

  /* Supplementary AI explanation: outside the paper, never in print/export. */
  .ahr-ai-fab {
    position: fixed; right: 24px; bottom: 24px; z-index: 120;
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 16px; border-radius: 999px;
    /* Frosted, matte glass: light and soft, with no glossy highlight. */
    border: 1px solid rgba(143, 175, 148, .55);
    background: rgba(255, 255, 255, .74);
    -webkit-backdrop-filter: blur(12px) saturate(115%);
    backdrop-filter: blur(12px) saturate(115%);
    color: var(--ahr-primary-text);
    font-size: 12.5px; font-weight: 700; letter-spacing: .01em;
    box-shadow: 0 10px 28px rgba(20, 61, 35, .10), inset 0 0 0 1px rgba(255, 255, 255, .45);
    cursor: pointer;
    transition: transform .16s ease, background .16s ease, box-shadow .16s ease;
  }
  .ahr-ai-fab:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, .88);
    box-shadow: 0 12px 30px rgba(20, 61, 35, .13), inset 0 0 0 1px rgba(255, 255, 255, .55);
  }
  .ahr-ai-fab .ahr-icon { width: 16px; height: 16px; color: var(--ahr-primary); }
  /* Same responsive pattern as the crop-directory reference drawer:
     a right-side sheet on wide screens, a full sub-page on small screens. */
  .ahr-ai-panel {
    /* Start below the portal's fixed top bar on every screen size. */
    position: fixed; top: var(--ahr-header-offset); right: 0; bottom: 0; z-index: 151;
    width: 100%; max-width: 490px; display: flex; flex-direction: column;
    background: var(--ahr-surface); border-left: 1px solid var(--ahr-line-sage);
    box-shadow: -10px 0 36px rgba(30, 41, 35, .12);
    transform: translateX(102%);
    transition: transform .25s cubic-bezier(.16, 1, .3, 1);
  }
  .ahr-ai-panel.open { transform: translateX(0); }
  .ahr-ai-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;
    padding: 22px 22px 16px; border-bottom: 1px solid var(--ahr-line);
  }
  .ahr-ai-eyebrow { margin: 0 0 4px; font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: var(--ahr-primary); }
  .ahr-ai-head h2 { margin: 0; font-size: 18px; font-weight: 700; color: var(--ahr-ink); }
  .ahr-ai-close {
    width: 30px; height: 30px; flex: 0 0 30px; border: 1px solid var(--ahr-line-sage);
    border-radius: 8px; background: var(--ahr-surface-soft); color: var(--ahr-muted);
    font-size: 18px; line-height: 1; cursor: pointer;
  }
  .ahr-ai-close:hover { background: var(--ahr-surface-sunken); color: var(--ahr-ink); }
  .ahr-ai-body { flex: 1; overflow-y: auto; padding: 20px 22px 24px; }
  .ahr-ai-text { margin: 0; font-size: 13px; line-height: 1.75; color: var(--ahr-ink); white-space: pre-line; }
  .ahr-ai-loading { display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--ahr-muted); }
  .ahr-ai-loading .ahr-generating-spinner { width: 22px; height: 22px; border-width: 3px; }
  .ahr-ai-meta {
    display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
    margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--ahr-line);
  }
  .ahr-ai-pill {
    display: inline-flex; padding: 4px 9px; border-radius: 999px;
    background: var(--ahr-surface-soft); border: 1px solid var(--ahr-line-sage);
    font-size: 10.5px; font-weight: 700; color: var(--ahr-primary-text);
  }
  .ahr-ai-note { margin: 12px 0 0; font-size: 11px; line-height: 1.6; color: var(--ahr-muted); }
  .ahr-ai-error { margin: 0 0 12px; padding: 10px 12px; border-radius: 8px; background: #fff8ed; border: 1px solid #f0d8ae; font-size: 11.5px; color: #84602b; }
  @media (max-width: 768px) {
    .ahr-ai-fab { right: 14px; bottom: 14px; }
    /* Full-screen sub-page, meeting the bottom edge of the system header. */
    .ahr-ai-panel {
      top: var(--ahr-header-offset);
      left: 0; right: 0; bottom: 0;
      max-width: none;
      height: calc(100vh - var(--ahr-header-offset));
      height: calc(100dvh - var(--ahr-header-offset));
      border-left: 0;
      border-top: 1px solid var(--ahr-line-sage);
      box-shadow: none;
      transform: translateX(100%);
    }
    .ahr-ai-panel.open { transform: translateX(0); }
    .ahr-ai-head, .ahr-ai-body { padding-left: 16px; padding-right: 16px; }
  }

  @include('admin.harvests.generated.partials.report-paper-styles')
</style>
