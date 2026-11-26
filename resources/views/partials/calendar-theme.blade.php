{{--
  Shared visual language for the association calendar and event pages.

  Farmer and admin see the same quiet layout. Only the admin pages add the
  create/edit/delete controls; those controls use the same neutral styling.
--}}
@once
<style>
  :root {
    --cal-line: #e7ece6;
    --cal-line-soft: #f0f3ef;
    --cal-ink: #26342b;
    --cal-muted: #748078;
    --cal-surface: #ffffff;
    --cal-wash: #f8faf8;
    --cal-accent: #5c7f66;
    --cal-accent-soft: #eef4ee;
  }

  /* Muted event tones: pale wash, readable ink, soft dot. No bright primaries. */
  .cal-tone-free_input     { --tone-dot: #7fa489; --tone-ink: #4f7358; --tone-wash: #edf3ee; }
  .cal-tone-water_schedule { --tone-dot: #8aa7b4; --tone-ink: #5c7e8c; --tone-wash: #edf3f6; }
  .cal-tone-meeting        { --tone-dot: #9a96ad; --tone-ink: #6e6a86; --tone-wash: #f1f0f6; }
  .cal-tone-other,
  .cal-tone-default        { --tone-dot: #a79b88; --tone-ink: #776e60; --tone-wash: #f5f3ef; }

  .cal-page-head {
    background: var(--cal-surface);
    border: 1px solid var(--cal-line);
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
  }
  .cal-page-head h1 {
    font-size: 22px;
    font-weight: 750;
    color: var(--cal-ink);
    margin: 0 0 5px;
    letter-spacing: -.015em;
  }
  .cal-page-head p {
    font-size: 13.5px;
    line-height: 1.55;
    color: var(--cal-muted);
    margin: 0;
    max-width: 680px;
  }

  .cal-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 15px;
    border: 1px solid #dfe6dd;
    border-radius: 9px;
    background: #f6f8f5;
    color: #55705c;
    font-family: inherit;
    font-size: 12.5px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
  }
  .cal-btn:hover { background: #eef3ec; }
  .cal-btn:focus-visible { outline: 2px solid var(--cal-accent); outline-offset: 2px; }
  .cal-btn--primary { background: var(--cal-accent); border-color: var(--cal-accent); color: #fff; }
  .cal-btn--primary:hover { background: #50705a; }
  .cal-btn--quiet { background: transparent; border-color: var(--cal-line); color: var(--cal-muted); }
  .cal-btn--quiet:hover { background: var(--cal-wash); }
  .cal-btn--danger { background: #fbf3f2; border-color: #ead9d6; color: #9b625c; }
  .cal-btn--danger:hover { background: #f7eae8; }

  .cal-flash {
    background: var(--cal-accent-soft);
    border: 1px solid #dbe8dc;
    color: #4f7358;
    font-size: 13px;
    border-radius: 10px;
    padding: 11px 15px;
    margin-bottom: 16px;
  }

  .cal-link {
    color: var(--cal-accent);
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 700;
    white-space: nowrap;
  }
  .cal-link:hover { text-decoration: underline; }

  [data-theme="dark"] {
    --cal-line: #334155;
    --cal-line-soft: #26334a;
    --cal-ink: #e6ecea;
    --cal-muted: #9fb3a6;
    --cal-surface: #111827;
    --cal-wash: #16201b;
    --cal-accent: #8bb99a;
    --cal-accent-soft: #1b2a20;
  }
  [data-theme="dark"] .cal-btn { background: #1f2937; border-color: #475569; color: #c9e0d0; }
  [data-theme="dark"] .cal-btn--primary { background: #4f7358; border-color: #4f7358; color: #fff; }
  [data-theme="dark"] .cal-flash { color: #bfe6c6; border-color: #4a7a54; }
  [data-theme="dark"] .cal-tone-free_input     { --tone-wash: #1b2a20; --tone-ink: #a9cfb3; }
  [data-theme="dark"] .cal-tone-water_schedule { --tone-wash: #1a2831; --tone-ink: #a8c7d4; }
  [data-theme="dark"] .cal-tone-meeting        { --tone-wash: #232236; --tone-ink: #bdb9d0; }
  [data-theme="dark"] .cal-tone-other,
  [data-theme="dark"] .cal-tone-default        { --tone-wash: #29251f; --tone-ink: #c7bda9; }
</style>
@endonce
