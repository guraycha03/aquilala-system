{{--
  Shared month calendar for the association schedule.
  Pass: $year, $month, $monthLabel, $prevUrl, $nextUrl, $events

  Every cell is a fixed size. Events are shown with a quiet dot; clicking or
  hovering a date opens a small neutral popover with the details.
--}}
@php
    $todayStr   = now()->toDateString();
    $monthStart = \Carbon\Carbon::create((int) $year, (int) $month, 1);
    $gridStart  = $monthStart->copy()->startOfWeek();
    $gridEnd    = $monthStart->copy()->endOfMonth()->endOfWeek();
    $byDate     = $events->groupBy(fn ($e) => $e->event_date->toDateString());
    $calTypes   = \App\Models\AssociationEvent::TYPES;

    // Unknown types fall back to the neutral tone. The class lives in the
    // shared calendar-theme partial, so farmer and admin look identical.
    $toneClass = fn (string $type): string => in_array($type, array_keys($calTypes), true)
        ? 'cal-tone-'.$type
        : 'cal-tone-other';

    $cells = [];
    for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
        $cells[] = $d->copy();
    }

    $calData = [];
    foreach ($byDate as $date => $dayEvents) {
        foreach ($dayEvents as $ev) {
            $calData[$date][] = [
                'title'       => $ev->title,
                'type_label'  => $ev->type_label,
                'tone'        => $toneClass((string) $ev->event_type),
                'time_label'  => $ev->time_label,
                'location'    => $ev->location,
                'description' => $ev->description,
            ];
        }
    }
@endphp

<style>
    .cal-split { display: grid; grid-template-columns: minmax(300px, 370px) minmax(0, 1fr); gap: 18px; align-items: start; }
    .cal-split-today { grid-column: 1; grid-row: 1; min-width: 0; }
    .cal-split-events { grid-column: 1; grid-row: 2; min-width: 0; }
    .cal-split-cal { grid-column: 2; grid-row: 1 / span 2; min-width: 0; }
    @media (max-width: 1200px) {
        .cal-split { grid-template-columns: 1fr; }
        .cal-split-today { grid-column: 1; grid-row: 1; }
        .cal-split-cal { grid-column: 1; grid-row: 2; }
        .cal-split-events { grid-column: 1; grid-row: 3; }
    }

    .cal-card {
        background: var(--cal-surface, #fff);
        border: 1px solid var(--cal-line, #e7ece6);
        border-radius: 14px;
        overflow: hidden;
    }
    .cal-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        padding: 13px 18px; border-bottom: 1px solid var(--cal-line-soft, #f0f3ef);
    }
    .cal-toolbar-title { font-size: 15.5px; font-weight: 750; color: var(--cal-ink, #26342b); margin: 0; }
    .cal-nav { display: flex; gap: 7px; }
    .cal-nav a {
        display: inline-flex; align-items: center; justify-content: center;
        width: 30px; height: 30px; border-radius: 8px;
        border: 1px solid var(--cal-line, #e7ece6);
        background: var(--cal-wash, #f8faf8);
        color: var(--cal-muted, #748078);
        text-decoration: none; font-weight: 700;
    }
    .cal-nav a:hover { background: var(--cal-accent-soft, #eef4ee); color: var(--cal-accent, #5c7f66); }

    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
    .cal-wd {
        font-size: 10.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .05em; color: var(--cal-muted, #748078);
        text-align: center; padding: 9px 4px;
        background: var(--cal-wash, #f8faf8);
        border-bottom: 1px solid var(--cal-line-soft, #f0f3ef);
    }

    .cal-cell {
        box-sizing: border-box; height: 86px; padding: 8px 7px;
        overflow: hidden;
        border-bottom: 1px solid var(--cal-line-soft, #f0f3ef);
        border-right: 1px solid var(--cal-line-soft, #f0f3ef);
    }
    .cal-cell:nth-child(7n) { border-right: 0; }
    .cal-cell.outside { background: var(--cal-wash, #f8faf8); }
    .cal-cell.is-today { background: var(--cal-accent-soft, #eef4ee); }
    .cal-cell.has-events { cursor: pointer; }
    .cal-cell.has-events:hover,
    .cal-cell.has-events:focus-visible { background: var(--cal-wash, #f8faf8); outline: none; }

    .cal-daynum {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 22px; height: 22px; padding: 0 4px;
        border-radius: 7px; font-size: 12px; font-weight: 700;
        color: var(--cal-ink, #26342b); margin-bottom: 7px;
    }
    .cal-cell.outside .cal-daynum { color: var(--cal-muted, #748078); opacity: .7; }
    .cal-cell.is-today .cal-daynum { background: var(--cal-accent-soft, #eef4ee); color: var(--cal-accent, #5c7f66); }

    .cal-dots { display: flex; align-items: center; gap: 5px; padding-left: 2px; }
    .cal-dot, .cal-legend-dot, .cal-pop-dot {
        background: var(--tone-dot, #a79b88);
        border-radius: 50%; flex-shrink: 0;
    }
    .cal-dot { width: 7px; height: 7px; }
    .cal-more { font-size: 10px; font-weight: 700; color: var(--cal-muted, #748078); }

    .cal-legend {
        display: flex; flex-wrap: wrap; gap: 8px 16px;
        padding: 11px 18px; background: var(--cal-wash, #f8faf8);
        border-top: 1px solid var(--cal-line-soft, #f0f3ef);
    }
    .cal-legend-item {
        display: inline-flex; align-items: center; gap: 7px;
        font-size: 12px; font-weight: 600; color: var(--cal-muted, #748078);
    }
    .cal-legend-dot { width: 8px; height: 8px; }

    .cal-pop {
        position: absolute; z-index: 60; display: none;
        width: 290px; max-width: calc(100vw - 24px);
        background: var(--cal-surface, #fff);
        border: 1px solid var(--cal-line, #e7ece6);
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(30, 45, 35, .10);
        overflow: hidden;
    }
    .cal-pop-head {
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; color: var(--cal-muted, #748078);
        background: var(--cal-wash, #f8faf8); padding: 9px 14px;
        border-bottom: 1px solid var(--cal-line-soft, #f0f3ef);
    }
    .cal-pop-body { max-height: 264px; overflow-y: auto; padding: 4px 14px 8px; }
    .cal-pop-item {
        display: flex; gap: 9px; padding: 10px 0;
        border-bottom: 1px solid var(--cal-line-soft, #f0f3ef);
    }
    .cal-pop-item:last-child { border-bottom: 0; }
    .cal-pop-dot { width: 8px; height: 8px; margin-top: 5px; }
    .cal-pop-title { font-size: 13px; font-weight: 700; color: var(--cal-ink, #26342b); }
    .cal-pop-meta { font-size: 11.5px; color: var(--cal-muted, #748078); margin-top: 2px; }
    .cal-pop-desc { font-size: 11.5px; color: var(--cal-muted, #748078); margin-top: 3px; line-height: 1.5; }
</style>

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
                 data-date="{{ $day->toDateString() }}"
                 data-label="{{ $day->format('l, F j, Y') }}"
                 {{ $dayEvents->isNotEmpty() ? 'tabindex=0' : '' }}>
                <span class="cal-daynum">{{ $day->day }}</span>
                @if($dayEvents->isNotEmpty())
                    <div class="cal-dots">
                        @foreach($dayEvents->take(4) as $ev)
                            <span class="cal-dot {{ $toneClass((string) $ev->event_type) }}"></span>
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
                <span class="cal-legend-dot {{ $toneClass((string) $key) }}"></span>
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
                '<span class="cal-pop-dot ' + esc(e.tone) + '"></span>' +
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
