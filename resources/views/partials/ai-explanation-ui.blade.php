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
  /* ── The card ────────────────────────────────────────────────
     The same shape as the KPI cards around it: a sparkle tile, a
     title, one line of description, and the button that does the
     work. Flat white, sage tile, no glow. */
  .ai-kpi-card {
    display: flex;
    flex-direction: column;
    border-radius: 16px;
    border: 1px solid #E0E5DB;
    background: #fff;
    padding: 20px;
    box-sizing: border-box;
  }
  .ai-kpi-card .kpi-desc { margin-bottom: 14px; }
  .ai-kpi-card .kpi-title { margin-top: 20px; }
  .ai-kpi-card .ai-btn { align-self: flex-start; }
  [data-theme="dark"] .ai-kpi-card { background: #111827; border-color: #334155 !important; }

  /* The AI card sits outside `.kpi-card`, so the chip's --chip-bg /
     --chip-color never reach it — the tile lost its sage fill and the
     sparkle fell back to the inherited text colour. Give it a green tint
     of its own; dark mode keeps the shared chip skin. */
  [data-theme="light"] .ai-kpi-card .kpi-icon-chip {
    --chip-bg: #E8F0E6;
    --chip-color: #4E7D57;
    --chip-glow: 78, 125, 87;
  }
  [data-theme="dark"] .ai-kpi-card .kpi-icon-chip {
    --chip-glow: 110, 231, 160;
  }

  /* The sparkle tile: one flat green background with a crisp edge that
     brightens on a slow loop. No glow, no blur, no second layer — an
     overlay gradient on a 40px tile just reads as a smudge. */
  .ai-kpi-card .kpi-icon-chip {
    position: relative;
    animation: aiChipEdge 3.6s ease-in-out infinite;
  }
  .ai-kpi-card .kpi-icon-chip svg {
    position: relative;
    z-index: 1;
    animation: aiChipTwinkle 3.6s ease-in-out infinite;
  }
  @keyframes aiChipEdge {
    0%, 100% { box-shadow: inset 0 0 0 1px rgba(var(--chip-glow), 0.22); }
    50%      { box-shadow: inset 0 0 0 1px rgba(var(--chip-glow), 0.62); }
  }
  @keyframes aiChipTwinkle {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%      { opacity: 0.86; transform: scale(1.06); }
  }
  @media (prefers-reduced-motion: reduce) {
    .ai-kpi-card .kpi-icon-chip,
    .ai-kpi-card .kpi-icon-chip svg { animation: none; }
    .ai-btn.is-loading .ai-btn-icon { animation: none; }
  }

  /* The tile's own rules. They live here as well as in the KPI card
     component: a page that shows the AI card without any other KPI
     card still needs the tile to look right. */
  .ai-kpi-card .kpi-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
  }
  .ai-kpi-card .kpi-icon-chip {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--chip-bg, #EDF1E9);
    color: var(--chip-color, #648268);
  }
  .ai-kpi-card .kpi-icon-chip svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .ai-kpi-card .kpi-title {
    margin: 4px 0 0;
    font-size: 14px;
    font-weight: 600;
    color: #344B38;
  }
  .ai-kpi-card .kpi-desc {
    margin: 4px 0 0;
    font-size: 12px;
    line-height: 1.35;
    color: #647263;
  }
  [data-theme="dark"] .ai-kpi-card .kpi-icon-chip { background: #16301f; color: #6ee7a0; }
  [data-theme="dark"] .ai-kpi-card .kpi-title { color: #d6f5e3; }
  [data-theme="dark"] .ai-kpi-card .kpi-desc { color: #9fb3a6; }

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
  .ai-modal {
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
  .ai-modal.open { opacity: 1; visibility: visible; transform: translate(-50%, -50%) scale(1); }
  .ai-modal-head {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    padding: 16px 20px; border-bottom: 1px solid #f0f2f1;
    position: sticky; top: 0; background: #fafdfb; border-radius: 18px 18px 0 0; z-index: 1;
  }
  .ai-modal-head h3 { font-size: 15px; margin: 0; color: #1a2b1e; }
  .ai-modal-head p { font-size: 12px; color: #879a8c; margin: 2px 0 0 0; }
  .ai-modal-close {
    width: 30px; height: 30px; border-radius: 9px; border: none; cursor: pointer;
    background: #f0f2f1; color: #6b7c6e; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s ease; font-size: 16px; line-height: 1;
  }
  .ai-modal-close:hover { background: #fee2e2; color: #b91c1c; }
  .ai-modal-body { padding: 20px; font-size: 13.5px; color: #3f3f46; }

  /* The failure state of the summary: a plain icon, a plain headline and
     the reason, centred — not a raw red box in the corner of the modal. */
  .ai-modal-error {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 10px; text-align: center; padding: 38px 24px;
    background: #F7F9F7; border: 1px solid #E0E5DB; border-radius: 14px;
  }
  .ai-modal-error-icon {
    width: 46px; height: 46px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    background: #FBEDED; border: 1px solid #F0D2D2; color: #B04A42;
  }
  .ai-modal-error-icon svg { width: 22px; height: 22px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .ai-modal-error-title { font-size: 14.5px; font-weight: 700; color: #1a2b1e; }
  .ai-modal-error-text { font-size: 13px; color: #6b7c6e; max-width: 380px; line-height: 1.55; }
  [data-theme="dark"] .ai-modal-error { background: rgba(15, 23, 42, 0.72); border-color: #334155; }
  [data-theme="dark"] .ai-modal-error-icon { background: rgba(127, 29, 29, 0.35); border-color: #7f1d1d; color: #fca5a5; }
  [data-theme="dark"] .ai-modal-error-title { color: #d6f5e3; }
  [data-theme="dark"] .ai-modal-error-text { color: #9fb3a6; }

  .ai-modal-foot { margin-top: 16px; display: flex; justify-content: flex-end; }

  @media (max-width: 640px) {
    .ai-modal { max-height: 82vh; width: calc(100vw - 20px); }
    .ai-kpi-card .ai-btn { padding: 9px 12px; font-size: 11.5px; }
  }
</style>
