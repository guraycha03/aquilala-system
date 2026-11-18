{{--
    The AI card and the modal it opens — one set of rules, shared by the
    store's demand page and the farmer's quotation page. Both pages show
    the same thing (a button that asks the assistant for an explanation)
    and both open it the same way, so the card and the window live here
    instead of being written twice and drifting apart.

    This is styles only. Each page brings its own script: what it asks
    for, and how it draws the answer.
--}}
<style>
  /* ── The card ─────────────────────────────────────────────────
     A premium, obviously-AI surface: soft gradient, crisp sparkles,
     and a clear "AI" label. Kept in the app's green palette. */
  .ai-kpi-card {
    position: relative;
    display: flex;
    flex-direction: column;
    border-radius: 18px;
    border: 1px solid #cfe4d6;
    background:
      radial-gradient(120% 90% at 100% 0%, rgba(186, 230, 253, 0.28) 0%, rgba(186, 230, 253, 0) 55%),
      linear-gradient(145deg, #ffffff 0%, #f3fbf6 48%, #eef7f2 100%);
    padding: 20px;
    box-sizing: border-box;
    box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 10px 28px rgba(22,101,52,0.08);
    overflow: hidden;
  }
  .ai-kpi-card .kpi-desc { margin: 6px 0 16px; font-size: 13px; line-height: 1.45; color: #4b6354; }
  .ai-kpi-card .kpi-title { margin: 0; font-size: 17px; font-weight: 700; color: #163a28; letter-spacing: -0.01em; }
  .ai-kpi-card .ai-btn { align-self: flex-start; margin-top: auto; }
  [data-theme="dark"] .ai-kpi-card {
    background:
      radial-gradient(120% 90% at 100% 0%, rgba(56, 189, 248, 0.12) 0%, rgba(56, 189, 248, 0) 55%),
      linear-gradient(145deg, #0f172a 0%, #0f1f17 55%, #0b1a14 100%);
    border-color: #2f4a3b !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.25), 0 14px 34px rgba(0,0,0,0.28);
  }

  [data-theme="light"] .ai-kpi-card .kpi-icon-chip {
    --chip-bg: linear-gradient(145deg, #dcfce7 0%, #d1fae5 45%, #e0f2fe 100%);
    --chip-color: #15803d;
  }
  [data-theme="dark"] .ai-kpi-card .kpi-icon-chip {
    --chip-bg: linear-gradient(145deg, #14532d 0%, #134e4a 100%);
    --chip-color: #6ee7b7;
  }

  .ai-kpi-card .kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
  }
  .ai-kpi-card .kpi-icon-chip {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--chip-bg, #EDF1E9);
    color: var(--chip-color, #648268);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.55), 0 6px 14px rgba(22,101,52,0.12);
    position: relative;
  }
  .ai-kpi-card .kpi-icon-chip svg {
    position: relative;
    z-index: 1;
    width: 22px;
    height: 22px;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.9;
    stroke-linecap: round;
    stroke-linejoin: round;
    vector-effect: non-scaling-stroke;
    shape-rendering: geometricPrecision;
  }
  .ai-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #166534;
    background: rgba(255,255,255,0.72);
    border: 1px solid rgba(34,197,94,0.25);
    box-shadow: 0 1px 0 rgba(255,255,255,0.6) inset;
    backdrop-filter: blur(4px);
  }
  .ai-badge-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: linear-gradient(135deg, #34d399, #38bdf8);
    box-shadow: 0 0 0 3px rgba(52,211,153,0.18);
  }
  [data-theme="dark"] .ai-kpi-card .kpi-title { color: #e6fff3; }
  [data-theme="dark"] .ai-kpi-card .kpi-desc { color: #9fb3a6; }
  [data-theme="dark"] .ai-kpi-card .kpi-icon-chip { box-shadow: inset 0 0 0 1px rgba(110,231,183,0.18), 0 8px 18px rgba(0,0,0,0.25); }
  [data-theme="dark"] .ai-badge {
    color: #bbf7d0;
    background: rgba(15,23,42,0.55);
    border-color: rgba(110,231,183,0.22);
    box-shadow: none;
  }

  /* Matte, low-saturation sage — the old forest green was the one
     loud thing on the page. Flat fill, no glow, white text at 5.7:1. */
  .ai-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    background: #5A6B5C;
    color: #ffffff; border: 1px solid #556658; cursor: pointer;
    padding: 9px 14px; border-radius: 10px; white-space: nowrap;
    /* The label changes while the summary is written. A fixed width keeps
       the button the same size through every state — a label swap that
       resizes the button makes the card next to it jump. */
    min-width: 156px; max-width: 100%;
    font-size: 12px; font-weight: 700; font-family: inherit;
    box-shadow: none;
    transition: background 0.2s ease, transform 0.2s ease;
  }
  .ai-btn:hover:not(:disabled) { background: #4E5D50; border-color: #47554A; transform: translateY(-1px); }
  .ai-btn:active:not(:disabled) { transform: scale(0.97); }
  .ai-btn:disabled { opacity: 0.65; cursor: wait; }

  /* While the summary is being written the button is disabled, so the
     sparkle itself is the only thing that still moves — it turns like a
     spinner to say "working" without adding a second icon. */
  .ai-btn .ai-btn-icon { transform-origin: 50% 50%; }
  .ai-btn.is-loading .ai-btn-icon { animation: aiBtnSpin 0.9s linear infinite; }
  @keyframes aiBtnSpin { to { transform: rotate(360deg); } }
  .ai-spin { width: 13px; height: 13px; border: 2px solid rgba(255,255,255,0.35); border-top-color: #ffffff; border-radius: 50%; animation: aiSpin 0.7s linear infinite; }
  @keyframes aiSpin { to { transform: rotate(360deg); } }

  /* Floating loading indicator (outside the card, no overlay) */
  .ai-floating {
    position: fixed; bottom: 26px; right: 26px; z-index: 1300;
    display: flex; align-items: center; gap: 10px;
    background: #ffffff; border: 1px solid #E0E5DB; border-radius: 999px;
    padding: 12px 18px;
    box-shadow: 0 12px 32px rgba(20, 61, 35, 0.14);
    font-size: 13px; font-weight: 700; color: #344B38;
  }
  .ai-floating[hidden] { display: none; }
  .ai-floating .ai-spin { border-color: rgba(74,122,84,0.25); border-top-color: #166534; }

  /* Floating result modal — centered, no dark overlay behind */
  .app-modal {
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(0.96);
    width: min(660px, calc(100vw - 32px)); max-height: 78vh; overflow-y: auto;
    background: #ffffff; border: 1px solid #E0E5DB; border-radius: 18px;
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.25);
    z-index: 1400;
    opacity: 0; visibility: hidden;
    transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
    scrollbar-width: thin;
  }
  .app-modal.open { opacity: 1; visibility: visible; transform: translate(-50%, -50%) scale(1); }
  .app-modal-head {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    padding: 16px 20px; border-bottom: 1px solid #f0f2f1;
    position: sticky; top: 0; background: #fafdfb; border-radius: 18px 18px 0 0; z-index: 1;
  }
  .app-modal-head h3 { font-size: 15px; margin: 0; color: #1a2b1e; }
  .app-modal-head p { font-size: 12px; color: #879a8c; margin: 2px 0 0 0; }
  .app-modal-close {
    width: 30px; height: 30px; border-radius: 9px; border: none; cursor: pointer;
    background: #f0f2f1; color: #6b7c6e; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s ease; font-size: 16px; line-height: 1;
  }
  .app-modal-close:hover { background: #fee2e2; color: #b91c1c; }
  .app-modal-body { padding: 20px; font-size: 13.5px; color: #3f3f46; }

  /* The failure state of the summary: a plain icon, a plain headline and
     the reason, centred — not a raw red box in the corner of the modal. */
  .app-modal-error {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 10px; text-align: center; padding: 38px 24px;
    background: #F7F9F7; border: 1px solid #E0E5DB; border-radius: 14px;
  }
  .app-modal-error-icon {
    width: 46px; height: 46px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    background: #FBEDED; border: 1px solid #F0D2D2; color: #B04A42;
  }
  .app-modal-error-icon svg { width: 22px; height: 22px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .app-modal-error-title { font-size: 14.5px; font-weight: 700; color: #1a2b1e; }
  .app-modal-error-text { font-size: 13px; color: #6b7c6e; max-width: 380px; line-height: 1.55; }
  [data-theme="dark"] .app-modal-error { background: rgba(15, 23, 42, 0.72); border-color: #334155; }
  [data-theme="dark"] .app-modal-error-icon { background: rgba(127, 29, 29, 0.35); border-color: #7f1d1d; color: #fca5a5; }
  [data-theme="dark"] .app-modal-error-title { color: #d6f5e3; }
  [data-theme="dark"] .app-modal-error-text { color: #9fb3a6; }

  .app-modal-foot { margin-top: 16px; display: flex; justify-content: flex-end; }

  @media (max-width: 640px) {
    .app-modal { max-height: 82vh; width: calc(100vw - 20px); }
    .ai-kpi-card .ai-btn { padding: 9px 12px; font-size: 11.5px; }
  }
</style>
