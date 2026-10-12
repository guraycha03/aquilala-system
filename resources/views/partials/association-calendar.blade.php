{{--
  Shared month calendar for the association schedule.
  Pass: $year, $month, $monthLabel, $prevUrl, $nextUrl, $events

  Every cell is a fixed size. Events are shown only as a colored day-number
  pill plus dots; hovering or clicking a date opens a popover with details.
--}}
@php
    $todayStr   = now()->toDateString();
    $monthStart = \Carbon\Carbon::create((int) $year, (int) $month, 1);
    $gridStart  = $monthStart->copy()->startOfWeek();
    $gridEnd    = $monthStart->copy()->endOfMonth()->endOfWeek();
    $byDate     = $events->groupBy(fn ($e) => $e->event_date->toDateString());
    $calTypes   = \App\Models\AssociationEvent::TYPES;
    $calColors  = \App\Models\AssociationEvent::TYPE_COLORS;

    $cells = [];
    for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
        $cells[] = $d->copy();
    }

    // Payload for the hover/click popover, keyed by date.
    $calData = [];
    foreach ($byDate as $date => $dayEvents) {
        foreach ($dayEvents as $ev) {
            $calData[$date][] = [
                'title'       => $ev->title,
                'type_label'  => $ev->type_label,
                'type_color'  => $ev->type_color,
                'time_label'  => $ev->time_label,
                'location'    => $ev->location,
                'description' => $ev->description,
            ];
        }
    }
@endphp

<style>
    /* ── Page split (wide screens): today card + events panel on the left,
       calendar alone on the right. Below 1200px everything stacks in
       DOM order: today, calendar, events. ── */
    .cal-split { display: grid; grid-template-columns: minmax(320px, 400px) minmax(0, 1fr); gap: 18px; align-items: start; }
    .cal-split-today { grid-column: 1; grid-row: 1; min-width: 0; }
    .cal-split-events { grid-column: 1; grid-row: 2; min-width: 0; }
    .cal-split-cal { grid-column: 2; grid-row: 1 / span 2; min-width: 0; }
    @media (max-width: 1200px) {
        .cal-split { grid-template-columns: 1fr; }
        .cal-split-today { grid-column: 1; grid-row: 1; }
        .cal-split-cal { grid-column: 1; grid-row: 2; }
        .cal-split-events { grid-column: 1; grid-row: 3; }
    }

    .cal-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.04); }

    .cal-toolbar { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #e5e7eb; }
    .cal-toolbar-title { font-size: 16.5px; font-weight: 800; color: #1e2922; margin: 0; }
    .cal-nav { display: flex; gap: 8px; }
    .cal-nav a { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 9px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569; text-decoration: none; font-weight: 700; }
    .cal-nav a:hover { background: #f0fdf4; border-color: #86efac; color: #16a34a; }

    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
    .cal-wd { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; text-align: center; padding: 10px 4px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; }

    .cal-cell { box-sizing: border-box; height: 96px; padding: 8px 7px; overflow: hidden; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; cursor: default; }
    .cal-cell:nth-child(7n) { border-right: 0; }
    .cal-cell.outside { background: #fafafa; }
    .cal-cell.is-today { background: #f0fdf4; box-shadow: inset 0 0 0 2px #16a34a; }
    .cal-cell.has-events { cursor: pointer; }
    .cal-cell.has-events:hover, .cal-cell.has-events:focus-visible { background: #ecfdf5; outline: none; }

    .cal-daynum { box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; min-width: 24px; height: 24px; padding: 0 5px; border-radius: 12px; font-size: 12.5px; font-weight: 700; color: #475569; background: #f1f5f9; margin-bottom: 7px; }
    .cal-cell.outside .cal-daynum { color: #cbd5e1; }
    .cal-cell.is-today .cal-daynum { background: #16a34a; color: #fff; font-weight: 800; }
    .cal-cell.has-events .cal-daynum { background: var(--ev, #334155); color: #fff; font-weight: 800; }
    .cal-cell.outside.has-events .cal-daynum { opacity: .6; }

    .cal-dots { display: flex; align-items: center; gap: 4px; padding-left: 3px; }
    .cal-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .cal-more { font-size: 10px; font-weight: 800; color: #94a3b8; }

    .cal-legend { display: flex; flex-wrap: wrap; gap: 14px; padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e5e7eb; }
    .cal-legend-item { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #64748b; }
    .cal-legend-dot { width: 10px; height: 10px; border-radius: 50%; }

    .cal-pop { position: absolute; z-index: 60; display: none; width: 300px; max-width: calc(100vw - 24px); background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 14px 34px rgba(15,23,42,.18); overflow: hidden; }
    .cal-pop-head { font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #64748b; background: #f8fafc; padding: 9px 14px; border-bottom: 1px solid #e2e8f0; }
    .cal-pop-body { max-height: 264px; overflow-y: auto; padding: 4px 14px 8px; }
    .cal-pop-item { display: flex; gap: 9px; padding: 10px 0; border-bottom: 1px dashed #e2e8f0; }
    .cal-pop-item:last-child { border-bottom: 0; }
    .cal-pop-dot { width: 9px; height: 9px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
    .cal-pop-title { font-size: 13.5px; font-weight: 700; color: #0f172a; }
    .cal-pop-meta { font-size: 12px; color: #64748b; margin-top: 1px; }
    .cal-pop-desc { font-size: 12px; color: #475569; margin-top: 3px; }
</style>

{{-- ── Month grid ── --}}
<div class="cal-card">
    <div class="cal-toolbar">
        <h2 class="cal-toolbar-title">{{ $monthLabel }}</h2>
        <div class="cal-nav">
            <a href="{{ $prevUrl }}" title="Previous month" aria-label="Previous month">&lsaquo;</a>
            <a href="{{ $nextUrl }}" title="Next month" aria-label="Next month">&rsaquo;</a>
        </div>
    </div>

    <div class="cal-grid" id="cal-grid">
        @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $wd)
            <div class="cal-wd">{{ $wd }}</div>
        @endforeach

        @foreach($cells as $day)
            @php
                $dayEvents = $byDate[$day->toDateString()] ?? collect();
                $isOutside = $day->format('Y-m') !== $monthStart->format('Y-m');
            @endphp
            <div class="cal-cell {{ $isOutside ? 'outside' : '' }} {{ $day->toDateString() === $todayStr ? 'is-today' : '' }} {{ $dayEvents->isNotEmpty() ? 'has-events' : '' }}"
                 style="--ev: {{ $dayEvents->first()?->type_color ?? 'transparent' }};"
                 data-date="{{ $day->toDateString() }}"
                 data-label="{{ $day->format('l, F j, Y') }}"
                 {{ $dayEvents->isNotEmpty() ? 'tabindex=0' : '' }}>
                <span class="cal-daynum">{{ $day->day }}</span>
                @if($dayEvents->isNotEmpty())
                    <div class="cal-dots">
                        @foreach($dayEvents->take(4) as $ev)
                            <span class="cal-dot" style="background: {{ $ev->type_color }};"></span>
                        @endforeach
                        @if($dayEvents->count() > 4)
                            <span class="cal-more">+{{ $dayEvents->count() - 4 }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="cal-legend">
        @foreach($calTypes as $key => $label)
            <span class="cal-legend-item">
                <span class="cal-legend-dot" style="background: {{ $calColors[$key] }};"></span>
                {{ $label }}
            </span>
        @endforeach
    </div>
</div>

<script type="application/json" id="cal-data">@json($calData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)</script>

<div class="cal-pop" id="cal-pop" role="dialog" aria-live="polite">
    <div class="cal-pop-head" id="cal-pop-date"></div>
    <div class="cal-pop-body" id="cal-pop-body"></div>
</div>

<script>
(function () {
    var dataEl = document.getElementById('cal-data');
    var pop = document.getElementById('cal-pop');
    if (!dataEl || !pop) return;

    var byDate = JSON.parse(dataEl.textContent || '{}');
    var popDate = document.getElementById('cal-pop-date');
    var popBody = document.getElementById('cal-pop-body');
    var pinnedCell = null;
    var hideTimer = null;

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function render(date, label) {
        var list = byDate[date] || [];
        popDate.textContent = label;
        popBody.innerHTML = list.map(function (e) {
            var meta = esc(e.type_label);
            if (e.time_label) meta += ' &middot; ' + esc(e.time_label);
            if (e.location) meta += ' &middot; ' + esc(e.location);
            return '<div class="cal-pop-item">' +
                '<span class="cal-pop-dot" style="background:' + esc(e.type_color) + '"></span>' +
                '<div><div class="cal-pop-title">' + esc(e.title) + '</div>' +
                '<div class="cal-pop-meta">' + meta + '</div>' +
                (e.description ? '<div class="cal-pop-desc">' + esc(e.description) + '</div>' : '') +
                '</div></div>';
        }).join('');
    }

    function place(cell) {
        pop.style.display = 'block';
        var r = cell.getBoundingClientRect();
        var w = pop.offsetWidth, h = pop.offsetHeight;
        var left = r.left + window.scrollX + r.width / 2 - w / 2;
        var maxLeft = document.documentElement.clientWidth - w - 10 + window.scrollX;
        if (left < 10 + window.scrollX) left = 10 + window.scrollX;
        if (left > maxLeft) left = maxLeft;
        var top = r.bottom + window.scrollY + 8;
        if (r.bottom + 8 + h > window.innerHeight) top = r.top + window.scrollY - h - 8;
        if (top < window.scrollY + 8) top = window.scrollY + 8;
        pop.style.left = left + 'px';
        pop.style.top = top + 'px';
    }

    function show(cell) {
        clearTimeout(hideTimer);
        render(cell.getAttribute('data-date'), cell.getAttribute('data-label'));
        place(cell);
    }

    function hide() {
        clearTimeout(hideTimer);
        pop.style.display = 'none';
    }

    function hideSoon() {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(function () {
            if (!pinnedCell) pop.style.display = 'none';
        }, 160);
    }

    function toggle(cell) {
        if (pinnedCell === cell) {
            pinnedCell = null;
            hide();
        } else {
            pinnedCell = cell;
            show(cell);
        }
    }

    var cells = document.querySelectorAll('#cal-grid .cal-cell');
    Array.prototype.forEach.call(cells, function (cell) {
        if (cell.classList.contains('has-events')) {
            cell.addEventListener('mouseenter', function () {
                if (!pinnedCell) show(cell);
            });
            cell.addEventListener('mouseleave', function () {
                if (!pinnedCell) hideSoon();
            });
            cell.addEventListener('click', function (ev) {
                ev.stopPropagation();
                toggle(cell);
            });
            cell.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    ev.stopPropagation();
                    toggle(cell);
                }
            });
        } else {
            cell.addEventListener('mouseenter', function () {
                if (!pinnedCell) hideSoon();
            });
        }
    });

    pop.addEventListener('mouseenter', function () { clearTimeout(hideTimer); });
    pop.addEventListener('mouseleave', function () { if (!pinnedCell) hideSoon(); });

    document.addEventListener('click', function (ev) {
        if (pinnedCell && !pop.contains(ev.target)) {
            pinnedCell = null;
            hide();
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && pinnedCell) {
            pinnedCell = null;
            hide();
        }
    });
})();
</script>
