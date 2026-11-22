@extends('layouts.sidebar')

@section('title', 'Admin Dashboard')

@section('content')
@php
  $user = auth()->user();
  $now = now();
  $hour = (int) $now->format('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<style>
  /* The page frame, welcome header and quick-action panel now come from
     components/dash-top.blade.php — shared with the Farmer and Store Owner
     dashboards — and the KPI card from components/kpi-card.blade.php.
     Only this page's own queue, panels and chart are styled here. */

  .reveal { opacity: 0; transform: translateY(14px); transition: opacity 0.5s cubic-bezier(0.4,0,0.2,1), transform 0.5s cubic-bezier(0.4,0,0.2,1); }
  .reveal.in { opacity: 1; transform: translateY(0); }

  /* Pending action queue */
  .queue-wrap { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; padding: 4px 20px 20px 20px; }
  .queue-item {
    display: flex; align-items: center; gap: 14px; text-decoration: none;
    background: rgba(255,255,255,0.7); border: 1px solid rgba(20,61,35,0.09);
    border-radius: 14px; padding: 16px 18px; transition: all 0.2s ease; position: relative;
  }
  .queue-item:hover { background: #ffffff; box-shadow: 0 8px 24px rgba(0,0,0,0.07); transform: translateY(-1px); border-color: rgba(20,61,35,0.18); }
  .queue-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .queue-icon svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .queue-info { min-width: 0; flex: 1; }
  .queue-info b { display: block; font-size: 13.5px; color: #1e293b; font-weight: 700; }
  .queue-info small { font-size: 11px; color: #94a3b8; display: block; margin-top: 2px; }
  .queue-count { font-size: 22px; font-weight: 800; color: #143d23; font-variant-numeric: tabular-nums; }
  .queue-arrow { width: 16px; height: 16px; stroke: #94a3b8; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
  .queue-empty { display: flex; align-items: center; gap: 10px; font-size: 12.5px; color: #40632d; font-weight: 600; }
  .queue-empty svg { width: 16px; height: 16px; stroke: #4A7A54; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  .dash-panel {
    background: rgba(255,255,255,0.84); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.5); border-radius: 18px;
    box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
    transition: all 0.35s cubic-bezier(0.4,0,0.2,1); overflow: hidden;
  }
  .dash-panel:hover { box-shadow: 0 16px 48px rgba(0,0,0,0.09); }
  .panel-head { padding: 18px 22px; border-bottom: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; gap: 12px; }
  .panel-head h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.06em; }
  .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; letter-spacing: 0.03em; }
  .panel-body { padding: 18px 22px; }

  .btn-ghost {
    font-size: 12px; font-weight: 700; color: #143d23; text-decoration: none; letter-spacing: 0.03em;
    padding: 7px 14px; border-radius: 9px; background: rgba(20,61,35,0.07);
    display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;
    transition: all 0.2s ease;
  }
  .btn-ghost:hover { background: rgba(20,61,35,0.13); }
  .btn-ghost svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

  /* Dashboard grid */
  .dash-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 22px; align-items: start; }
  .dash-grid.equal { grid-template-columns: repeat(2, minmax(0, 1fr)); }

  /* Stock pills */
  .stock-pill { display: inline-block; padding: 3px 8px; border-radius: 7px; font-size: 10px; font-weight: 700; }
  .stock-ok { background: #edf1e9; color: #648268; }
  .stock-low { background: #fef3c7; color: #d97706; }
  .stock-out { background: #edf1e9; color: #648268; }

  /* Table */
  .admin-table { width: 100%; border-collapse: collapse; text-align: left; }
  .admin-table thead tr { background: #f6f8f7; }
  .admin-table th { padding: 11px 16px; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
  .admin-table td { padding: 12px 16px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
  .admin-table tbody tr { transition: background 0.15s ease; }
  .admin-table tbody tr:hover { background: #fafbfa; }
  .admin-table tbody tr:last-child td { border-bottom: none; }
  .table-avatar { width: 34px; height: 34px; border-radius: 10px; background: rgb(224, 235, 170); color: #49a750; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; text-transform: uppercase; flex-shrink: 0; }
  .role-badge { display: inline-block; padding: 4px 10px; border-radius: 7px; font-size: 11px; font-weight: 700; }
  .role-admin { background: #e0f2fe; color: #0369a1; }
  .role-owner { background: #fef3c7; color: #d97706; }
  .role-farmer { background: #dcfce7; color: #15803d; }

  /* Timeline */
  .qt-item { position: relative; padding: 13px 0 13px 24px; border-bottom: 1px solid rgba(0,0,0,0.045); }
  .qt-item:last-child { border-bottom: none; }
  .qt-item:hover { transform: translateX(4px); }
  .qt-dot { position: absolute; left: 0; top: 18px; width: 12px; height: 12px; border-radius: 50%; border: 2.5px solid; background: #fff; }

  /* Product rows */
  .prod-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; }
  .prod-row + .prod-row { border-top: 1px solid rgba(0,0,0,0.045); }
  .prod-thumb { width: 42px; height: 42px; border-radius: 11px; background: #f4f7f5; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .prod-thumb img { width: 100%; height: 100%; object-fit: cover; }
  .prod-thumb svg { width: 20px; height: 20px; stroke: #94a3b8; }

  @media (min-width: 981px) {
  }

  /* The Total IA Members chip carries a pre-coloured 512px raster
     (public/images/farmer-light.png, farmer-dark.png), so it needs no
     masking — each file already ships in its theme's chip colour. Only the
     sizing is set here. It sits at 26px rather than the 20px the stroked
     SVG chips use: at the same box size the raster's strokes land around
     1.2px, well under the SVGs' 2px stroke-width, so the glyph read
     noticeably smaller and lighter than its neighbours. Sizing up evens the
     visual weight without touching the shared component rule. The dark theme
     swaps the file outright via content:, the same technique the sidebar
     nav icons use. */
  .kpi-icon-chip .kpi-icon-farmer {
    width: 26px;
    height: 26px;
    object-fit: contain;
    /* The pitchfork-free replacement kept the old 512px canvas, so the
       farmer's ink now ends around x=442 and the glyph sits ~6% left of the
       canvas centre — visible as a left-leaning icon in the chip. Shifting
       it back re-centres it without re-exporting the PNG. */
    transform: translateX(6%);
  }
  [data-theme="dark"] .kpi-icon-chip .kpi-icon-farmer {
    content: url('{{ asset("images/farmer-dark.png") }}?v={{ filemtime(public_path("images/farmer-dark.png")) }}');
  }

  @media (max-width: 980px) {
    .dash-grid, .dash-grid.equal { grid-template-columns: 1fr; }
  }

  @media (max-width: 768px) {
    .dash-grid, .dash-grid.equal { grid-template-columns: 1fr; }
  }
</style>

<div class="fd-page">

    {{-- 1 & 2. Welcome header + quick actions — shared with every dashboard --}}
    <x-dash-top
        eyebrow="Aquilala IA Management Portal"
        :title="$greeting . ', ' . $user->name"
        :subtitle="'Association overview for <strong>' . $now->format('l, F j, Y') . '</strong> &middot; currently in the <strong>' . $currentSeasonLabel . '</strong>.'"
    >
    </x-dash-top>

    <!-- 2. KPI CARDS
         Four cards, one question each: who is in the association, what have
         they submitted, how much that adds up to in the field, and what is
         next on the calendar. -->
    @php
        // "Upcoming Event" reads the nearest future calendar entry in the
        // same words the calendar page uses. Year only rides along when the
        // event lands in a different year than today. The card stays an
        // overview — big date, card title, and the event's category as the
        // bottom line — so the full title and time are deliberately not
        // built here.
        $nextEventDate = $upcomingEvent
            ? $upcomingEvent->event_date->format(
                $upcomingEvent->event_date->isSameYear(now()) ? 'M j' : 'M j, Y'
            )
            : null;
        $nextEventType = $upcomingEvent?->type_label;
    @endphp
    <section class="kpi-strip">
      {{-- Who is in the association — the members list, not the accounts
           table: a CSV/scan import adds people without logins, so counting
           farmer accounts here would read 0 right after an upload. --}}
      <x-kpi-card title="Total IA Members" value="{{ $totalMembers }}" trendLabel="Official farmer registry" accent="#4A7A54" link="{{ route('admin.members.index') }}">
        {{-- Local copy of the Icons8 farmer glyph (from message_to_opencode.txt),
             vendored to public/images so the card doesn't depend on a remote
             host. The source PNG is solid black, which fought the sage chips,
             so it is painted through a CSS mask instead of drawn directly:
             the mask carries the shape and currentColor carries the colour,
             exactly like the stroked SVG chips beside it. --}}
        {{-- Local 512px farmer raster (public/images), pre-coloured per theme:
             farmer-light.png in light mode, farmer-dark.png in dark. The dark
             swap is a content: override in the <style> block above, so no JS
             is needed. Sized to 26px — a touch above the stroked SVG chips'
             20px — so the raster's thinner strokes carry the same visual
             weight as the icons beside it.

             Both URLs carry ?v=<mtime> on purpose. public/sw.js serves .png
             cache-first and never revalidates, so a fixed filename meant a
             swapped-in file kept the old bytes cached on every device that had
             already seen it. Busting off the file's own mtime changes the URL
             the moment the PNG changes, which defeats both the service worker
             cache and the browser HTTP cache with no manual clearing. --}}
        <x-slot:icon><img class="kpi-icon-farmer" src="{{ asset('images/farmer-light.png') }}?v={{ filemtime(public_path('images/farmer-light.png')) }}" alt="" width="26" height="26" aria-hidden="true"></x-slot:icon>
      </x-kpi-card>
      {{-- Everything farmers have submitted. The captions on these cards
           stay period-neutral on purpose — a long-press modal will soon set
           the scope (all time / yearly / monthly), so no fixed period word
           is baked into the visible text. --}}
      <x-kpi-card title="Harvest Reports" value="{{ number_format($totalHarvests) }}" trendLabel="Farmer submissions" accent="#2563eb" link="{{ route('admin.harvests.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></svg></x-slot:icon>
      </x-kpi-card>
      {{-- Production reported, summed across every report. Icon is the official
           Lucide "file-chart-column" glyph: the layout's createIcons() pass
           swaps this <i data-lucide> placeholder for the real SVG. (Lucide has
           no "file-chat-column" — no *-chat-* icon at all — and this is the
           file-with-a-column-chart glyph that name was reaching for.) --}}
      <x-kpi-card title="Reported Total Yield" value="{{ number_format((float) $totalHarvestYield, 2) }} MT" trendLabel="Across harvest reports" accent="#d97706" link="{{ route('admin.harvests.reports') }}">
        <x-slot:icon><i data-lucide="file-chart-column" width="18" height="18" aria-hidden="true"></i></x-slot:icon>
      </x-kpi-card>
      {{-- What is next on the association calendar. Overview only: the big
           date, the card title, and the event category as the bottom line.
           No top-right pill, no long title/time text. --}}
      <x-kpi-card title="Upcoming Event"
        :value="$nextEventDate ?? 'None yet'"
        :trendLabel="$nextEventType"
        accent="#0284c7" link="{{ route('admin.calendar.index') }}">
        <x-slot:icon><svg viewBox="0 0 24 24" width="18" height="18"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></x-slot:icon>
      </x-kpi-card>
    </section>

    <!-- 4. HARVEST REPORTS -->
<section style="margin-top:22px;">
  <div class="dash-panel reveal" data-reveal>
    <div class="panel-head">
      <div>
        <h3>Harvest Reports</h3>
        <p>Farmer harvest submissions across the platform</p>
      </div>
      <div class="harvest-head-actions">
        <a href="{{ route('admin.harvests.index') }}" class="btn-ghost">
          Full View
          <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
      </div>
    </div>
    <div class="panel-body" style="padding:0;">
      {{-- The chart, its styling and its behaviour live in one shared partial
           so the Harvest Performance page shows exactly the same chart. --}}
      @include('partials.harvest-report-chart')
    </div>
  </div>
</section>

    <!-- 5. WET vs. DRY SEASON (overview of the full seasonal chart) -->
<section style="margin-top:22px;">
  <div class="dash-panel reveal wdb-panel" data-reveal>
    @include('partials.wet-dry-season-balance')
  </div>
</section>

</div>

<script src="{{ asset('js/chart.umd.min.js') }}" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    function setGreeting() {
      var h = new Date().getHours();
      var el = document.querySelector('[data-greeting]');
      if (!el) return;
      el.textContent = h < 12 ? 'Good morning' : (h < 18 ? 'Good afternoon' : 'Good evening');
    }
    setGreeting();
    if (!window.__aqGreetTimer) window.__aqGreetTimer = setInterval(setGreeting, 60000);

    var revealEls = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window) {
      var ro = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('in');
            ro.unobserve(entry.target);
          }
        });
      }, { threshold: 0.08 });
      revealEls.forEach(function (el) { ro.observe(el); });
    } else {
      revealEls.forEach(function (el) { el.classList.add('in'); });
    }
  });
</script>
@endsection
