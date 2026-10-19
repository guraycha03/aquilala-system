{{--
  Dashboard top — the shared design every role's dashboard opens with.

  One eyebrow, one greeting, one subtitle, then an optional welcome panel
  holding the quick actions, then whatever the page puts below (KPI strip,
  tables, charts). Colour, spacing and type are fixed here so the Farmer,
  Store Owner and Admin dashboards are the same page in three voices.

  Slots:
    panel-icon     44px sage tile glyph in the welcome panel
    panel-heading  the panel's one-line question
    panel-text     the panel's supporting sentence
    meta           extra contact chips (store addresses, phone)
    actions        the quick-action buttons
--}}
@props([
    'eyebrow',
    'title',
    'subtitle' => null,
])

@once
<style>
  /* ── Dashboard frame ──────────────────────────────────────────── */
  .fd-page {
    background: #F6F7F3;
    /* Full-bleed: cancel .main-content padding, then re-apply it so the
       page background reaches the edges without shifting content.
       Top padding sits at 40px, not the shell's 64px: on desktop nothing is
       fixed above the page (the 64px exists for the mobile header), and the
       full amount left a dead white band over the greeting. The -64px margin
       stays — it is what cancels .main-content's own top padding. */
    margin: -64px -32px -32px -32px;
    padding: 40px 32px 32px 32px;
    min-height: 100vh;
    box-sizing: border-box;
  }
  @media (max-width: 768px) {
    /* Mobile keeps the full 88px: the fixed burger header sits on top of
       the page here, so the greeting must start below it. */
    .fd-page { margin: -88px -16px -24px -16px; padding: 88px 16px 24px 16px; }
  }
  .fd-page > * + * { margin-top: 32px; }
  /* The component's own <style> block is the FIRST child of .fd-page, so the
     sibling gap above would also land on the greeting — its first *visible*
     block — and shove it down another 32px for no reason. The greeting starts
     at the page's top padding and nothing else. */
  .fd-page > .fd-head { margin-top: 0; }
  /* The greeting block gets one extra step of air before the block that
     follows it (welcome panel / first section), so the top of the page
     doesn't read as one dense mass. */
  .fd-page > .fd-head + * { margin-top: 40px; }

  /* 1 ─ Welcome header.
     Airy on purpose: eyebrow → greeting → season line each get their own
     breathing room instead of stacking on 4px, so the greeting reads as a
     block of its own before the welcome panel starts. */
  .fd-head > * + * { margin-top: 10px; }
  .fd-eyebrow {
    font-size: 12px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.16em; color: #71816F; margin: 0;
  }
  .fd-title {
    font-size: 24px; font-weight: 600; letter-spacing: -0.025em;
    color: #344B38; margin: 0; line-height: 1.32;
  }
  @media (min-width: 640px) { .fd-title { font-size: 30px; } }
  .fd-season { font-size: 14px; line-height: 1.55; color: #647263; margin: 0; }

  /* The actual line gaps of the greeting stack.
     Written with the element names instead of the `* + *` shorthand above:
     each rule further down resets `margin: 0` at the same specificity, so the
     generic sibling rule tied and lost the cascade — which is why the eyebrow,
     greeting and season line used to sit on top of each other with no space. */
  .fd-head > .fd-title,
  .fd-head > .fd-season { margin-top: 10px; }

  /* Contact chips — same calm row the farmer's season line uses, widened. */
  .fd-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
  .fd-meta span {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fff; border: 1px solid #E0E5DB; border-radius: 999px;
    padding: 6px 12px; font-size: 12px; font-weight: 500; color: #647263;
  }
  .fd-meta svg { width: 14px; height: 14px; color: #648268; flex-shrink: 0; }

  /* 2 ─ Welcome panel + quick actions */
  .fd-panel {
    overflow: hidden; border-radius: 16px; border: 1px solid #DDE5D8;
    background: #E6ECE2; padding: 20px; box-sizing: border-box;
  }
  @media (min-width: 640px) { .fd-panel { padding: 24px; } }
  .fd-panel-inner { display: flex; flex-direction: column; gap: 20px; }
  .fd-panel-row { display: flex; align-items: flex-start; gap: 12px; }
  .fd-panel-icon {
    width: 44px; height: 44px; flex-shrink: 0; border-radius: 12px;
    background: rgba(255,255,255,0.8); color: #47654E;
    display: flex; align-items: center; justify-content: center;
  }
  .fd-panel-icon svg { width: 24px; height: 24px; }
  .fd-panel-text > * + * { margin-top: 8px; }
  .fd-panel-text h2 { font-size: 18px; font-weight: 600; color: #344B38; margin: 0; line-height: 1.4; }
  .fd-panel-text p { max-width: 42rem; font-size: 14px; line-height: 1.5; color: #526653; margin: 0; }
  /* Same cascade note as the greeting stack: `p` resets its margin at equal
     specificity, so the gap has to be declared against the element itself. */
  .fd-panel-text > p { margin-top: 8px; }

  .fd-actions { display: grid; grid-template-columns: 1fr; gap: 12px; }
  @media (min-width: 640px) { .fd-actions { grid-template-columns: repeat(3, 1fr); } }

  .fd-action {
    display: flex; min-height: 56px; align-items: center; justify-content: center; gap: 10px;
    border-radius: 12px; background: #fff; padding: 12px 16px;
    font-size: 14px; font-weight: 500; color: #344B38; text-decoration: none;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); transition: background .15s, box-shadow .15s;
    box-sizing: border-box;
  }
  .fd-action:hover { background: #F7F9F5; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1); }
  .fd-action:focus-visible { outline: 2px solid #648268; outline-offset: 2px; }
  .fd-action svg { width: 20px; height: 20px; flex-shrink: 0; color: #648268; }

  /* 3 ─ Section + KPI heading.
     The strip's own grid comes from the shared `body .kpi-strip` rule in
     layouts/sidebar.blade.php. */
  .fd-section { display: flex; flex-direction: column; gap: 16px; }
  .fd-sec-head h2 { font-size: 18px; font-weight: 600; letter-spacing: -0.025em; color: #344B38; margin: 0; line-height: 1.4; }
  .fd-sec-head p { margin: 4px 0 0 0; font-size: 14px; color: #647263; line-height: 1.5; }
  .fd-section .kpi-strip { margin-bottom: 0; }

  /* ── Dark theme ────────────────────────────────────────────────── */
  [data-theme="dark"] .fd-page { background: #0b1220; }
  [data-theme="dark"] .fd-eyebrow { color: #8fa08d; }
  [data-theme="dark"] .fd-title,
  [data-theme="dark"] .fd-panel-text h2,
  [data-theme="dark"] .fd-sec-head h2 { color: #d6f5e3; }
  [data-theme="dark"] .fd-season,
  [data-theme="dark"] .fd-sec-head p { color: #9fb3a6; }
  [data-theme="dark"] .fd-meta span { background: #111827; border-color: #334155; color: #9fb3a6; }
  [data-theme="dark"] .fd-meta svg { color: #6ee7a0; }
  [data-theme="dark"] .fd-panel { background: #10261a; border-color: #1e3a2a; }
  [data-theme="dark"] .fd-panel-text p { color: #b6c2bb; }
  [data-theme="dark"] .fd-panel-icon { background: rgba(255,255,255,0.06); color: #6ee7a0; }
  [data-theme="dark"] .fd-action { background: #111827; color: #d6f5e3; border: 1px solid #334155; }
  [data-theme="dark"] .fd-action:hover { background: #1f2937; }
  [data-theme="dark"] .fd-action svg { color: #6ee7a0; }
</style>
@endonce

{{-- 1. Welcome header --}}
<header class="fd-head">
    <p class="fd-eyebrow">{{ $eyebrow }}</p>
    <h1 class="fd-title">{{ $title }}</h1>
    @if($subtitle)
        <p class="fd-season keep-sub">{!! $subtitle !!}</p>
    @endif
    @isset($meta)
        <div class="fd-meta">{!! $meta !!}</div>
    @endisset
</header>

{{-- 2. Welcome panel + quick actions --}}
@isset($panelHeading)
    <section class="fd-panel">
        <div class="fd-panel-inner">

            <div class="fd-panel-row">
                @isset($panelIcon)
                    <div class="fd-panel-icon">{!! $panelIcon !!}</div>
                @endisset

                <div class="fd-panel-text">
                    <h2>{!! $panelHeading !!}</h2>
                    @isset($panelText)
                        <p>{!! $panelText !!}</p>
                    @endisset
                </div>
            </div>

            @isset($actions)
                <div class="fd-actions">{{ $actions }}</div>
            @endisset

        </div>
    </section>
@endisset
