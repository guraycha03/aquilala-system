@extends('layouts.sidebar')

@section('title', 'Quotation Demand Trends')

@section('content')
<style>
  /* Top-of-page metrics reuse the dashboard KPI card (components/kpi-card)
     inside the shared responsive `kpi-strip` grid — flat white, sage chips,
     no gradients, so this page reads like every other dashboard. */

  /* ── Panels ─────────────────────────────────────────────────── */
  /* One panel lives on this page now (the demand list), so the strip is a
     single column — the panel gets the full width of the page. */
  .trend-panels {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 24px;
  }

  .panel {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
  }
  .panel-header { padding: 20px 24px; border-bottom: 1px solid #f0f2f1; background: #fafdfb; }
  .panel-body { padding: 20px 24px; }

  .demand-bar { height: 8px; background: #F0F2F1; border-radius: 4px; overflow: hidden; }
  .demand-bar-fill {
    height: 100%; border-radius: 4px;
    background: #7FA886;
    transition: width 0.6s ease;
  }

  /* Collapsible demand list — the top 3 show first; the rest open with a
     toggle so a store with many products doesn't get a half-page list.
     Separators ride on the sibling selector so hidden items never leave a
     stray line behind. */
  .demand-item + .demand-item { border-top: 1px solid #f0f2f1; padding-top: 20px; margin-top: 20px; }
  .demand-item.is-collapsed { display: none; }
  .demand-toggle {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    width: 100%; margin-top: 18px; padding: 9px 14px;
    background: #ffffff; border: 1px solid #E0E5DB; border-radius: 10px;
    font-family: inherit; font-size: 12.5px; font-weight: 700; color: #166534;
    cursor: pointer; transition: background 0.15s ease, border-color 0.15s ease;
  }
  .demand-toggle:hover { background: #f0fdf4; border-color: #c8d5c5; }
  .demand-toggle svg {
    width: 14px; height: 14px; stroke: currentColor; fill: none;
    stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round;
    transition: transform 0.2s ease;
  }
  .demand-toggle.is-open svg { transform: rotate(180deg); }

  /* ── The AI card — same shape as the KPI cards around it ────── */
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

  .ai-output {
    margin-top: 12px; padding-top: 12px; border-top: 1px dashed rgba(74,122,84,0.25);
    font-size: 13px; color: #3f4f44; max-height: 340px; overflow-y: auto;
    scrollbar-width: thin;
  }
  .ai-output p, .ai-output li { color: #3f4f44; }
  .ai-output .ai-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 10px; padding: 12px 14px; font-size: 12.5px; line-height: 1.5; display: flex; gap: 8px; align-items: flex-start; }

  /* Floating AI loading indicator (outside the KPI card, no overlay) */
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

  .ai-narrative {
    background: #F3F5F0;
    border: 1px solid #E0E5DB; border-radius: 12px; padding: 16px 18px;
  }
  .ai-narrative-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
  .ai-narrative-head h4 { font-size: 15px; font-weight: 700; color: #344B38; margin: 0; }
  .ai-narrative-body { font-size: 14px; color: #3b4a3f; line-height: 1.7; }
  .ai-narrative-body p { margin: 8px 0 0 0; }
  .ai-narrative-body ul, .ai-narrative-body ol { margin: 10px 0 0 18px; padding: 0; }
  .ai-narrative-body li { margin-bottom: 7px; line-height: 1.6; }
  .ai-narrative-body strong { color: #166534; }
  .ai-narrative-body h1, .ai-narrative-body h2, .ai-narrative-body h3, .ai-narrative-body h4 {
    margin: 16px 0 6px; font-weight: 700; color: #25392b; line-height: 1.35;
  }
  .ai-narrative-body h1 { font-size: 16px; }
  .ai-narrative-body h2 { font-size: 15px; }
  .ai-narrative-body h3 { font-size: 14.5px; }
  .ai-narrative-body h4 { font-size: 14px; }
  .ai-narrative-body > *:first-child { margin-top: 0; }
  .ai-narrative-body table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 13px; }
  .ai-narrative-body th { background: #EDF1E9; text-align: left; font-weight: 700; color: #25392b; }
  .ai-narrative-body th, .ai-narrative-body td { border: 1px solid #dbe5df; padding: 7px 9px; }
  .ai-narrative-body blockquote { margin: 10px 0; padding: 9px 13px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; color: #78350f; }
  .ai-narrative-body .table-wrap { overflow-x: auto; max-width: 100%; }
  .ai-narrative-body .warn-icon-img { width: 16px; height: 16px; display: inline-block; margin-right: 6px; vertical-align: -3px; }

  /* One card per quoted product. The products are the point of the
     report, so each one gets its own tile instead of a line of prose. */
  .ai-product-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 10px;
    margin: 10px 0 2px;
  }
  .ai-product-card {
    background: #ffffff;
    border: 1px solid #E0E5DB;
    border-radius: 12px;
    padding: 12px 14px;
  }
  .ai-product-name { font-size: 13.5px; font-weight: 700; color: #25392b; line-height: 1.35; }
  .ai-product-meta { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px; font-size: 12px; color: #879a8c; }
  .ai-product-value { margin-top: 8px; font-size: 13px; font-weight: 700; color: #4b5c50; }
  .ai-crop-row { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 12px; }
  .ai-crop-label { font-size: 11px; font-weight: 700; color: #6b7c6e; text-transform: uppercase; letter-spacing: .06em; }
  .ai-crop-chip { font-size: 12px; font-weight: 600; color: #4b5c50; background: #EDF1E9; border: 1px solid #E0E5DB; border-radius: 999px; padding: 4px 11px; }
  .ai-crop-name { text-transform: capitalize; }
  [data-theme="dark"] .ai-product-card { background: rgba(15, 23, 42, 0.72); border-color: #334155; }
  [data-theme="dark"] .ai-product-name { color: #d6f5e3; }
  [data-theme="dark"] .ai-product-value { color: #a7f3d0; }
  [data-theme="dark"] .ai-crop-chip { background: #16301f; border-color: #334155; color: #a7f3d0; }
  [data-theme="dark"] .ai-crop-label { color: #9fb3a6; }

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

  .ai-modal-foot { margin-top: 16px; display: flex; justify-content: flex-end; }  .ai-chat-link {
    display: inline-flex; align-items: center; gap: 8px;
    background: #166534; color: #ffffff; border: none; border-radius: 10px;
    padding: 10px 16px; font-size: 13px; font-weight: 700; font-family: 'DM Sans', sans-serif;
    cursor: pointer; transition: background 0.2s ease;
  }
  .ai-chat-link:hover { background: #14532d; }
  .ai-chat-link svg { width: 15px; height: 15px; }

  /* ── Tabs ───────────────────────────────────────────────────────
     Two views of the same page: the demand read on the left, the
     full table on the right. A tab bar keeps them one click apart
     instead of one whole page apart. */
  .tab-bar {
    display: flex; align-items: center; gap: 4px;
    margin-bottom: 20px; border-bottom: 1px solid #f0f2f1;
    overflow-x: auto; scrollbar-width: none;
  }
  .tab-bar::-webkit-scrollbar { display: none; }
  .tab-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 16px; margin-bottom: -1px;
    background: none; border: none; border-bottom: 2px solid transparent;
    font-family: inherit; font-size: 13.5px; font-weight: 700; color: #879a8c;
    cursor: pointer; white-space: nowrap;
    transition: color 0.15s ease, border-color 0.15s ease;
  }
  .tab-btn:hover { color: #344B38; }
  .tab-btn.is-active { color: #1a2b1e; border-bottom-color: #5A6B5C; }
  .tab-btn svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .tab-count {
    font-size: 11px; font-weight: 700; color: #4b5c50;
    background: #F0F2F1; border-radius: 999px; padding: 2px 8px;
  }
  .tab-panel[hidden] { display: none; }
  [data-theme="dark"] .tab-bar { border-bottom-color: #334155; }
  [data-theme="dark"] .tab-btn { color: #9fb3a6; }
  [data-theme="dark"] .tab-btn:hover { color: #d6f5e3; }
  [data-theme="dark"] .tab-btn.is-active { color: #d6f5e3; border-bottom-color: #6ee7a0; }
  [data-theme="dark"] .tab-count { background: #1f2937; color: #a7f3d0; }

  /* ── The table (All Farmer Quotations tab) ──────────────────── */
  .table-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 16px rgba(0,0,0,0.02);
    border: 1px solid #f0f2f1;
    overflow: hidden;
  }

  /* A floor width for the table: on a phone the TABLE scrolls sideways
     (the shared .app-scroll wrapper) instead of every column being
     crushed into a two-word stack. */
  .quotations-table { min-width: 900px; }

  /* Table base style lives in the shared .data-table rules (layouts.sidebar). */

  /* The farmer's tile is the shared account avatar, so it carries the
     same look as everywhere else. */
  .farmer-cell { display: flex; align-items: center; gap: 12px; }

  .action-btn {
    background: #14532d;
    color: white; border: none;
    padding: 7px 16px; border-radius: 8px;
    font-size: 12px; font-weight: 700;
    cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 5px;
    transition: all 0.2s;
  }
  .action-btn:hover { background: #166534; }

  .product-count-badge {
    background: #F3F5F0; color: #4b5c50;
    padding: 3px 10px; border-radius: 8px;
    font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 4px;
    white-space: nowrap;
    border: 1px solid #E0E5DB;
  }

  .empty-state { text-align: center; padding: 56px 24px; }
  .empty-icon {
    width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 16px;
    background: #EDF1E9; border: 1px solid #E0E5DB;
    display: flex; align-items: center; justify-content: center;
  }
  .empty-icon svg { stroke: #648268; }

  /* ── Phone sizing ─────────────────────────────────────────────
     The panels already collapse to one column at 980px; these steps
     trim padding and type so the page reads comfortably on a phone
     instead of feeling like a squeezed desktop. */
  @media (max-width: 640px) {
    .trend-panels { gap: 14px; margin-bottom: 18px; }
    .panel-header { padding: 15px 16px; }
    .panel-header h2 { font-size: 15px; }
    .panel-body { padding: 16px; }
    .tab-btn { padding: 10px 12px; font-size: 12.5px; }
    .table-card-head { padding: 15px 16px; flex-wrap: wrap; gap: 10px; }
    .empty-state { padding: 40px 16px; }
    .ai-modal { max-height: 82vh; width: calc(100vw - 20px); }
    .ai-floating { bottom: 16px; right: 16px; left: 16px; justify-content: center; }
    .ai-kpi-card .ai-btn { padding: 9px 12px; font-size: 11.5px; }
  }
</style>

<div style="margin-bottom: 28px;">
  <h1 style="font-size: 26px; font-weight: 800; color: #1a2b1e; margin: 0 0 4px 0; letter-spacing: -0.02em;">Quotation Demand Trends</h1>
  <p class="keep-sub" style="color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.5;">See which of your products appear in farmers' quotations — no action needed.</p>
</div>

@if(session('success'))
  <div style="background: #F4F8F4; border: 1px solid #D6E6D8; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
    <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(100,130,104,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="#648268" stroke-width="2.5" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <span style="font-size: 14px; color: #3f5c46; font-weight: 600;">{{ session('success') }}</span>
  </div>
@endif

@php
    // Plain-language trend wording — an ordinary store owner should read
    // "2 more than last week", never a bare percentage against a baseline.
    $weekDiff = $recentQuotations - $priorQuotations;
    $weekPill = match (true) {
        $weekDiff > 0 => $weekDiff . ' more than last week',
        $weekDiff < 0 => abs($weekDiff) . ' less than last week',
        $priorQuotations > 0 => 'Same as last week',
        $recentQuotations > 0 => 'New this week',
        default => 'None yet',
    };

    // The demand list opens with only the top 3 products on screen; the
    // rest wait behind the "Show all" toggle, so a store with dozens of
    // products doesn't get a half-page list of bars.
    $demandTopCount = 3;
@endphp

<section class="kpi-strip">
  <x-kpi-card title="Farmer Quotations" value="{{ $totalQuotations }}" trendValue="From {{ $uniqueFarmers }} farmer{{ $uniqueFarmers === 1 ? '' : 's' }}" trendLabel="Quotations that include your products">
    <x-slot:icon>
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    </x-slot:icon>
  </x-kpi-card>

  {{-- The week-on-week comparison rides on the description line, not on a
       second pill — the card already carries the count, so a pill repeated
       the same card in three places. --}}
  <x-kpi-card title="New This Week" value="{{ $recentQuotations }}" trendLabel="Quotations made in the last 7 days{{ $weekPill !== 'None yet' ? ' — ' . lcfirst($weekPill) : '' }}">
    <x-slot:icon>
      <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    </x-slot:icon>
  </x-kpi-card>

  {{-- Not a peso figure: the sum of quoted item values is not sales, not
       orders, and not money on the way — showing it as "worth" feeds false
       hope. The honest, hope-free number is how many of the store's own
       products farmers actually put in their quotations. --}}
  <x-kpi-card title="Your Products Quoted" value="{{ $demandProductCount }}" trendLabel="Your products that appeared in these quotations">
    <x-slot:icon>
      <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
    </x-slot:icon>
  </x-kpi-card>

  <div class="ai-kpi-card">
    <div class="kpi-top">
      <div class="kpi-icon-chip">
        {{-- Lucide "square-sparkles" (1.48.0), inlined verbatim. The bundled
             lucide.min.js is 1.47.0 — one release short — so <i data-lucide>
             would leave an empty tag; the glyph is copied here instead. --}}
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 15H7"/><path d="M15.41 2.49a.6.6 0 011.18 0l.63 3.334a1.2 1.2 0 00.956.955l3.334.631a.6.6 0 010 1.18l-3.334.63a1.2 1.2 0 00-.955.956l-.631 3.334a.6.6 0 01-1.18 0l-.63-3.334a1.2 1.2 0 00-.956-.955L10.49 8.59a.6.6 0 010-1.18l3.334-.63a1.2 1.2 0 00.955-.956z"/><path d="M21 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h6"/><path d="M9 13v4"/></svg>
      </div>
    </div>
    <h3 class="kpi-title">Smart Store Summary</h3>
    <p class="kpi-desc">A short read on what farmers are quoting for.</p>
    <button type="button" class="ai-btn" id="aiOverviewBtn" onclick="generateAiOverview(this)">
      <svg class="ai-btn-icon" viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/></svg>
      <span>Create Summary</span>
    </button>
  </div>
</section>

{{-- Two views of the same page: the demand read on the left, the full
     table on the right. Tabs keep them one click apart instead of one
     whole page apart. --}}
<div class="tab-bar" role="tablist" aria-label="Quotation views">
  <button type="button" class="tab-btn is-active" role="tab" id="tabDemand" data-tab="demand" aria-controls="panelDemand" aria-selected="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
    Your Products in Demand
  </button>
  <button type="button" class="tab-btn" role="tab" id="tabQuotes" data-tab="quotes" aria-controls="panelQuotes" aria-selected="false">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
    All Farmer Quotations
    <span class="tab-count">{{ $totalQuotations }}</span>
  </button>
</div>

<div class="tab-panel" id="panelDemand" role="tabpanel" aria-labelledby="tabDemand">
<div class="trend-panels">
  <div class="panel">
    <div class="panel-header">
      <h2 style="font-size: 16px; color: #1a2b1e; font-weight: 700; margin: 0;">Your Products in Demand</h2>
      <p id="demandPanelSub" style="font-size: 13px; color: #879a8c; margin: 3px 0 0 0;">How often each product appears in quotations — top {{ $demandTopCount }} shown first</p>
    </div>
    <div class="panel-body">
      @if(count($productDemand) > 0)
        @php $maxCount = max(array_column($productDemand, 'count')); @endphp
        @foreach($productDemand as $productName => $data)
          <div class="demand-item{{ $loop->index >= $demandTopCount ? ' is-collapsed' : '' }}" data-demand-item>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-weight: 700; color: #1a2b1e; font-size: 14px;">{{ $productName }}</span>
              <span style="font-size: 12px; color: #879a8c; font-weight: 600;">{{ $data['count'] }} quotation{{ $data['count'] == 1 ? '' : 's' }}</span>
            </div>
            <div class="demand-bar">
              <div class="demand-bar-fill" style="width: {{ ($data['count'] / $maxCount) * 100 }}%;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 6px; font-size: 12px; color: #b0b8b3;">
              <span>{{ number_format($data['total_qty'], 1) }} units total</span>
              <span style="font-weight: 600; color: #4b5c50;">₱{{ number_format($data['total_value'], 0) }} quoted</span>
            </div>
          </div>
        @endforeach
        @if(count($productDemand) > $demandTopCount)
          <button type="button" class="demand-toggle" id="demandToggle" data-total="{{ count($productDemand) }}" aria-expanded="false">
            <span>Show all {{ count($productDemand) }} products</span>
            <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
        @endif
      @else
        <div style="text-align: center; padding: 40px 20px;">
          <div class="empty-icon" style="margin-bottom: 12px;">
            <svg viewBox="0 0 24 24" width="28" height="28" stroke-width="1.5" fill="none"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
          </div>
          <p style="margin: 0; font-size: 14px; color: #879a8c; line-height: 1.5;">Nothing here yet. Once farmers create quotations that include your products, you'll see them here.</p>
        </div>
      @endif
    </div>
  </div>
</div>
</div>

<div class="tab-panel" id="panelQuotes" role="tabpanel" aria-labelledby="tabQuotes" hidden>
<x-table-toolbar
    uid="store-quotes"
    work-table="storeQuotesTable"
    per-page="10"
    noun="quotations"
    search-placeholder="Search by farmer, crop, or season…"
    :filters="[
        ['key' => 'crop', 'label' => 'All crops', 'options' => collect($quotations->pluck('crop_type')->unique()->values())->map(fn($c) => [strtolower($c), $c])->all()],
        ['key' => 'season', 'label' => 'All seasons', 'options' => collect($quotations->pluck('season')->unique()->values())->map(fn($s) => [strtolower($s), ucfirst($s)])->all()],
    ]"
    :sorts="[
        ['key' => 'date', 'dir' => -1, 'type' => 'date', 'label' => 'Date (newest)'],
        ['key' => 'date', 'dir' => 1, 'type' => 'date', 'label' => 'Date (oldest)'],
        ['key' => 'value', 'dir' => -1, 'type' => 'num', 'label' => 'Estimated cost (highest)'],
        ['key' => 'value', 'dir' => 1, 'type' => 'num', 'label' => 'Estimated cost (lowest)'],
        ['key' => 'farmer', 'dir' => 1, 'type' => 'str', 'label' => 'Farmer (A–Z)'],
        ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop (A–Z)'],
    ]"
  />

<div class="table-card">
  <div class="table-card-head" style="padding: 20px 24px; border-bottom: 1px solid #f0f2f1; display: flex; justify-content: space-between; align-items: center; background: #fafdfb;">
    <div>
      <h2 style="font-size: 16px; color: #1a2b1e; font-weight: 700; margin: 0;">All Farmer Quotations</h2>
      <p style="font-size: 13px; color: #879a8c; margin: 2px 0 0 0;">Every quotation that includes your products</p>
    </div>
    <span style="font-size: 13px; color: #879a8c; font-weight: 600; background: #f0f2f1; padding: 4px 12px; border-radius: 8px;">{{ $totalQuotations }} total</span>
  </div>
  <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
    <div class="app-scroll">
      <table id="storeQuotesTable" class="data-table quotations-table">
      <thead>
        <tr>
          <th>Quotation No.</th>
          <th>Farmer</th>
          <th>Crop / Season</th>
          <th>Land Area</th>
          <th>Products Included</th>
          <th>Estimated Cost</th>
          <th>Date</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($quotations as $q)
          @php
            $storeItems = $q->items->filter(fn ($item) => $item->product && $item->product->user_id === Auth::id());
            // A store learns that the demand exists, not who created it. The
            // farmer's name is masked, and it is left out of the search
            // index too, so the name cannot be read back out of the page.
            $farmerName = $q->farmer?->maskedName() ?? 'Axxx';
          @endphp
          <tr data-search="{{ strtolower(($q->quotation_number ?? '') . ' ' . $farmerName . ' ' . ($q->crop_type ?? '') . ' ' . ($q->season ?? '')) }}"
            data-farmer="{{ strtolower($farmerName) }}"
            data-crop="{{ strtolower($q->crop_type ?? '') }}"
            data-season="{{ strtolower($q->season ?? '') }}"
            data-value="{{ (float) ($q->estimated_total_cost ?? 0) }}"
            data-date="{{ $q->created_at?->format('Y-m-d') ?? '' }}">
            <td><span style="font-weight: 700; color: #143d23; font-family: 'SF Mono', ui-monospace, monospace; font-size: 13px;">{{ $q->quotation_number }}</span></td>
            <td>
              <div class="farmer-cell">
                <x-user-avatar :user="$q->farmer" :size="32" :mask="true" />
                <span style="font-weight: 500;">{{ $farmerName }}</span>
              </div>
            </td>
            <td>
              <div style="text-transform: capitalize; font-weight: 500;">{{ $q->crop_type }}</div>
              <div style="font-size: 12px; color: #879a8c; text-transform: capitalize;">{{ $q->season }} season</div>
            </td>
            <td>
              <span style="font-weight: 600;">{{ number_format($q->land_area, 1) }}</span> <span style="color: #879a8c; font-size: 12px;">ha</span>
              @if($q->is_expired)
                <div style="font-size: 11px; color: #9aa3ad; margin-top: 4px; white-space: nowrap;">Expired {{ $q->valid_until->format('M d, Y') }}</div>
              @endif
            </td>
            <td><span class="product-count-badge">{{ $storeItems->count() }} product{{ $storeItems->count() === 1 ? '' : 's' }}</span></td>
            <td>
              @if($q->estimated_total_cost > 0)
                <span style="font-weight: 700; color: #1a2b1e;">₱{{ number_format($q->estimated_total_cost, 2) }}</span>
              @else
                <span style="color: #d1d9d3;">—</span>
              @endif
            </td>
            <td style="font-size: 13px; color: #879a8c; white-space: nowrap;">{{ $q->created_at->format('M d, Y') }}</td>
            <td style="text-align: right;">
              <a href="{{ route('store.quotations.show', $q) }}" class="action-btn">
                <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                View
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8">
              <div class="empty-state">
                <div class="empty-icon">
                  <svg viewBox="0 0 24 24" width="28" height="28" stroke-width="1.5" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <p style="font-size: 16px; font-weight: 600; color: #6b7c6e; margin: 0 0 4px 0;">No Quotations Yet</p>
                <p style="font-size: 13px; color: #879a8c; margin: 0;">When a farmer creates a quotation that includes your products, it will appear here.</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
    </div>
    <div class="app-scrollbar">
      <div class="app-scrollbar-track"></div>
      <div class="app-scrollbar-thumb"></div>
    </div>
  </div>
</div>
</div>

<div class="ai-floating" id="aiFloatingLoading" hidden>
  <span class="ai-spin"></span>
  <span>Preparing your store summary&hellip;</span>
</div>

<div class="ai-modal" id="aiModal" role="dialog" aria-modal="true" aria-labelledby="aiModalTitle" aria-hidden="true">
  <div class="ai-modal-head">
    <div>
      <h3 id="aiModalTitle">Smart Store Summary</h3>
      <p>A short read on what farmers are quoting for</p>
    </div>
    <button type="button" class="ai-modal-close" id="aiModalClose" aria-label="Close summary" title="Close">
      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="ai-modal-body" id="aiModalBody"></div>
</div>

<script>
  function escapeAiHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function inlineAi(t) {
    return t
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
      .replace(/\u2014/g, '&mdash;');
  }
  function renderAiMarkdown(raw) {
    raw = String(raw || '').replace(/\[[ \t\u00A0]*SUGGEST:[^\]]*\]/gi, '');
    var lines = escapeAiHtml(raw).replace(/\r/g, '').split('\n');
    var html = '', inUl = false, inOl = false, inBq = false;
    function closeUl() { if (inUl) { html += '</ul>'; inUl = false; } }
    function closeOl() { if (inOl) { html += '</ol>'; inOl = false; } }
    function closeBq() { if (inBq) { html += '</blockquote>'; inBq = false; } }
    function renderTable(buf) {
      var rows = buf.map(function (r) { return r.slice(1, -1).split('|').map(function (c) { return c.trim() }); })
        .filter(function (cells) { return !cells.every(function (c) { return /^:?-+:?$/.test(c); }); });
      if (!rows.length) return;
      html += '<div class="table-wrap"><table>';
      var header = rows.shift();
      html += '<thead><tr>' + header.map(function (c) { return '<th>' + inlineAi(c) + '</th>'; }).join('') + '</tr></thead><tbody>';
      rows.forEach(function (cells) {
        html += '<tr>' + cells.map(function (c) { return '<td>' + inlineAi(c) + '</td>'; }).join('') + '</tr>';
      });
      html += '</tbody></table></div>';
    }
    for (var i = 0; i < lines.length; i++) {
      var trimmed = lines[i].trim();
      if (!trimmed) { closeUl(); closeOl(); closeBq(); continue; }
      if (trimmed.charAt(0) === '|' && trimmed.charAt(trimmed.length - 1) === '|') {
        closeUl(); closeOl();
        var buf = [];
        while (i < lines.length) {
          var t = lines[i].trim();
          if (t.charAt(0) === '|' && t.charAt(t.length - 1) === '|') { buf.push(t); i++; } else break;
        }
        i--;
        renderTable(buf);
        continue;
      }
      if (trimmed.indexOf('&gt;') === 0) {
        closeUl(); closeOl();
        var warnImg = '<img src="{{ asset('images/caution.png') }}" class="warn-icon-img" alt="Warning">';
        if (!inBq) { html += '<blockquote>'; inBq = true; }
        html += '<p>' + warnImg + inlineAi(trimmed.replace(/^&gt;\s?/, '').replace(/^[\s\u26A0\uFE0F\u2757\u2B21]+/u, '').trim()) + '</p>';
        continue;
      } else { closeBq(); }
      var hMatch = trimmed.match(/^(#{1,4})\s+(.+)$/);
      if (hMatch) {
        closeUl(); closeOl();
        var lvl = hMatch[1].length;
        html += '<h' + lvl + '>' + inlineAi(hMatch[2]) + '</h' + lvl + '>';
        continue;
      }
      if (/^[-*]\s+/.test(trimmed)) {
        if (!inUl) { closeOl(); html += '<ul>'; inUl = true; }
        html += '<li>' + inlineAi(trimmed.replace(/^[-*]\s+/, '')) + '</li>';
        continue;
      } else { closeUl(); }
      if (/^\d+[.)]\s+/.test(trimmed)) {
        if (!inOl) { closeUl(); html += '<ol>'; inOl = true; }
        html += '<li>' + inlineAi(trimmed.replace(/^\d+[.)]\s+/, '')) + '</li>';
        continue;
      } else { closeOl(); }
      html += '<p>' + inlineAi(trimmed) + '</p>';
    }
    closeUl(); closeOl(); closeBq();
    return html;
  }
  var lastAiOverview = '';
  function openAiChat() {
    try {
      // The placeholder for the product cards is a page concern, not part
      // of the report — it must never reach the chat as text.
      var seed = String(lastAiOverview || '').replace(/\[\[PRODUCT_CARDS\]\]/g, '').replace(/\n{3,}/g, '\n\n');
      sessionStorage.setItem('aiSeedOverview', seed);
    } catch (e) {}
    window.location.href = '{{ route('store.ai-chat.index') }}';
  }

  // One card per quoted product, drawn straight from the metrics so the
  // numbers are the store's own and not the model's paraphrase.
  function buildProductCards(metrics) {
    if (!metrics) return '';
    var products = metrics.top_products || [];
    var crops = metrics.top_crops || [];
    if (!products.length && !crops.length) return '';

    var html = '<h4>Your Most Quoted Products</h4>';

    if (products.length) {
      html += '<div class="ai-product-cards">';
      products.forEach(function (p) {
        var count = Number(p.count) || 0;
        var qty = Number(p.qty) || 0;
        var value = Number(p.value) || 0;
        html += '<div class="ai-product-card">' +
          '<div class="ai-product-name">' + escapeAiHtml(String(p.name || 'Unnamed product')) + '</div>' +
          '<div class="ai-product-meta">' +
            '<span>' + count + ' quotation' + (count === 1 ? '' : 's') + '</span>' +
            '<span>' + qty.toLocaleString('en-PH', { maximumFractionDigits: 1 }) + ' units</span>' +
          '</div>' +
          '<div class="ai-product-value">₱' + value.toLocaleString('en-PH', { maximumFractionDigits: 0 }) + ' quoted</div>' +
        '</div>';
      });
      html += '</div>';
    }

    if (crops.length) {
      html += '<div class="ai-crop-row"><span class="ai-crop-label">Top crops</span>';
      crops.slice(0, 4).forEach(function (c) {
        var count = Number(c.count) || 0;
        html += '<span class="ai-crop-chip"><span class="ai-crop-name">' + escapeAiHtml(String(c.crop || '')) + '</span> · ' + count + ' quotation' + (count === 1 ? '' : 's') + '</span>';
      });
      html += '</div>';
    }

    return html;
  }
  function openAiModal(data) {
    if (data && typeof data === 'object' && data.error) {
      document.getElementById('aiModalBody').innerHTML =
        '<div class="ai-modal-error">' +
          '<div class="ai-modal-error-icon"><i data-lucide="circle-alert"></i></div>' +
          '<div class="ai-modal-error-title">We couldn\'t build your summary</div>' +
          '<div class="ai-modal-error-text">' + escapeAiHtml(data.error) + '</div>' +
        '</div>';
      // The icon is injected after Lucide has already swept the page.
      if (typeof renderLucideIcons === 'function') renderLucideIcons();
      var modal = document.getElementById('aiModal');
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      lockPageScroll(true);
      return;
    }
    lastAiOverview = String(data.overview || '');
    var body = renderAiMarkdown(lastAiOverview);
    var cards = buildProductCards(data.metrics);
    if (body.indexOf('[[PRODUCT_CARDS]]') !== -1) {
      // The placeholder always goes: with products it becomes the cards,
      // without them it simply leaves no gap behind.
      body = body.replace(/<p>\s*\[\[PRODUCT_CARDS\]\]\s*<\/p>/, function () { return cards; });
      body = body.replace(/\[\[PRODUCT_CARDS\]\]/g, function () { return cards; });
    } else if (cards) {
      body += cards;
    }
    var html = '<div class="ai-narrative">' +
      '<div class="ai-narrative-head">' +
      '<svg viewBox="0 0 24 24" width="14" height="14" stroke="#166534" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>' +
      '<h4>Your Store Summary</h4></div><div class="ai-narrative-body">' + body + '</div></div>' +
      '<div class="ai-modal-foot">' +
      '<button type="button" class="ai-chat-link" onclick="openAiChat()">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>' +
      '<span>Open in AI Chat</span></button></div>';
    document.getElementById('aiModalBody').innerHTML = html;
    var modal = document.getElementById('aiModal');
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    lockPageScroll(true);
  }
  // A modal that sits on top of the page must not let the page scroll
  // underneath it — the wheel would move content the reader cannot see.
  function lockPageScroll(lock) {
    var body = document.body;
    if (lock) {
      body.dataset.scrollLocked = '1';
      body.style.overflow = 'hidden';
    } else if (body.dataset.scrollLocked) {
      delete body.dataset.scrollLocked;
      body.style.overflow = '';
    }
  }
  function closeAiModal() {
    var modal = document.getElementById('aiModal');
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    lockPageScroll(false);
  }
  function showAiLoading() {
    var el = document.getElementById('aiFloatingLoading');
    el.hidden = false;
  }
  function hideAiLoading() {
    document.getElementById('aiFloatingLoading').hidden = true;
  }
  function setAiBtnState(btn, busy, done) {
    var label = btn.querySelector('span');
    btn.disabled = busy;
    // The sparkle turns while the summary is being written, so the button
    // reads as working even though it can't be pressed again.
    btn.classList.toggle('is-loading', busy);
    // Short labels on purpose: the button sits in a card with room for
    // about one word and a half, so a long one spills out of it. A failed
    // run keeps "Create Summary" — nothing was created to do again.
    label.textContent = busy ? 'Generating\u2026' : (done ? 'Create Again' : 'Create Summary');
  }
  function generateAiOverview(btn) {
    setAiBtnState(btn, true);
    showAiLoading();

    fetch('{{ route('store.quotations.ai-overview') }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: '{}'
    })
    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
    .then(function (res) {
      hideAiLoading();
      if (res.ok && res.data && res.data.overview) {
        setAiBtnState(btn, false, true);
        openAiModal(res.data);
      } else if (res.data && res.data.error) {
        setAiBtnState(btn, false, false);
        openAiModal({ error: res.data.error });
      } else {
        setAiBtnState(btn, false, false);
        openAiModal({ error: 'The AI service could not generate an overview right now. Please try again.' });
      }
    })
    .catch(function () {
      hideAiLoading();
      setAiBtnState(btn, false, false);
      openAiModal({ error: 'Connection failed. Please try again.' });
    });
  }
  document.getElementById('aiModalClose').addEventListener('click', closeAiModal);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAiModal();
  });

  // Tabs — the demand read and the full table are one click apart.
  (function () {
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.tab-btn'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('.tab-panel'));
    if (!tabs.length) return;

    function show(name) {
      tabs.forEach(function (btn) {
        var on = btn.dataset.tab === name;
        btn.classList.toggle('is-active', on);
        btn.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      panels.forEach(function (panel) {
        panel.hidden = panel.id !== 'panel' + name.charAt(0).toUpperCase() + name.slice(1);
      });
    }

    tabs.forEach(function (btn) {
      btn.addEventListener('click', function () { show(btn.dataset.tab); });
    });
  })();

  // Collapsible demand list — expand/collapse the items past the top 3.
  // The hidden set is captured once, so the same nodes toggle back and forth.
  (function () {
    var toggle = document.getElementById('demandToggle');
    if (!toggle) return;
    var total = parseInt(toggle.getAttribute('data-total'), 10) || 0;
    var hidden = document.querySelectorAll('[data-demand-item].is-collapsed');
    var label = toggle.querySelector('span');
    var sub = document.getElementById('demandPanelSub');
    var topText = 'How often each product appears in quotations — top ' + {{ $demandTopCount }} + ' shown first';
    var allText = 'How often each product appears in quotations — all ' + total + ' products shown';

    toggle.addEventListener('click', function () {
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      hidden.forEach(function (el) { el.classList.toggle('is-collapsed', expanded); });
      toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      toggle.classList.toggle('is-open', !expanded);
      label.textContent = expanded ? 'Show all ' + total + ' products' : 'Show fewer';
      if (sub) sub.textContent = expanded ? topText : allText;
    });
  })();
</script>

@endsection
