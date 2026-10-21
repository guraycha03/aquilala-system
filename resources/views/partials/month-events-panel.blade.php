{{--
  Left-hand panel: every event of the displayed month, grouped by date.
  Pass: $year, $month, $monthLabel, $events
  The card always renders — an empty month shows an empty state.
--}}
@php
    $panelMonth  = \Carbon\Carbon::create((int) $year, (int) $month, 1)->format('Y-m');
    $todayStr    = now()->toDateString();
    $monthEvents = $events
        ->filter(fn ($e) => $e->event_date->format('Y-m') === $panelMonth)
        ->groupBy(fn ($e) => $e->event_date->toDateString());
    $isPanelAdmin = request()->routeIs('admin.*');
@endphp

<style>
    .ev-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.04); overflow: hidden; display: flex; flex-direction: column; }
    .ev-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 18px; background: #f8fafc; border-bottom: 1px solid #e5e7eb; }
    .ev-head h3 { margin: 0; font-size: 15px; font-weight: 800; color: #1e2922; }
    .ev-head a { font-size: 12.5px; font-weight: 700; color: #4A7A54; text-decoration: none; white-space: nowrap; }
    .ev-head a:hover { text-decoration: underline; }
    .ev-body { padding: 4px 18px 12px; max-height: 660px; overflow-y: auto; }

    .ev-day { padding: 12px 0 6px; border-bottom: 1px dashed #e5e7eb; }
    .ev-day:last-child { border-bottom: 0; }
    .ev-day-label { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; margin-bottom: 6px; }
    .ev-day-label.is-today { color: #4A7A54; }
    .ev-today-chip { background: #4A7A54; color: #fff; border-radius: 10px; padding: 1px 8px; font-size: 9.5px; letter-spacing: .04em; }
    .ev-item { display: flex; gap: 9px; padding: 6px 0; }
    .ev-dot { width: 9px; height: 9px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
    .ev-title { font-size: 13.5px; font-weight: 700; color: #0f172a; }
    .ev-meta { font-size: 12px; color: #64748b; margin-top: 1px; }

    .ev-empty { padding: 44px 18px 48px; text-align: center; }
    .ev-empty svg { color: #cbd5e1; }
    .ev-empty-title { font-size: 14px; font-weight: 700; color: #64748b; margin-top: 12px; }
    .ev-empty-sub { font-size: 12.5px; color: #94a3b8; margin-top: 4px; line-height: 1.5; }
</style>

<div class="ev-card">
    <div class="ev-head">
        <h3>Events &middot; {{ $monthLabel }}</h3>
        <a href="{{ $isPanelAdmin ? route('admin.calendar.events') : route('farmer.calendar.events') }}">All events &rarr;</a>
    </div>

    <div class="ev-body">
        @if($monthEvents->isEmpty())
            <div class="ev-empty">
                <svg viewBox="0 0 24 24" width="42" height="42" fill="none" stroke="currentColor" stroke-width="1.6">
                    <rect x="3" y="5" width="18" height="16" rx="2"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <line x1="8" y1="3" x2="8" y2="7"/>
                    <line x1="16" y1="3" x2="16" y2="7"/>
                </svg>
                <div class="ev-empty-title">No events this month.</div>
                <div class="ev-empty-sub">Association events for {{ $monthLabel }} will appear here once they are scheduled.</div>
            </div>
        @else
            @foreach($monthEvents as $date => $dayEvents)
                @php
                    $firstEvent = $dayEvents->first();
                    $isToday = $date === $todayStr;
                @endphp
                <div class="ev-day">
                    <div class="ev-day-label {{ $isToday ? 'is-today' : '' }}">
                        {{ $firstEvent->event_date->format('D, M j') }}
                        @if($isToday)<span class="ev-today-chip">Today</span>@endif
                    </div>
                    @foreach($dayEvents as $ev)
                        <div class="ev-item">
                            <span class="ev-dot" style="background: {{ $ev->type_color }};"></span>
                            <div>
                                <div class="ev-title">{{ $ev->title }}</div>
                                <div class="ev-meta">
                                    {{ $ev->type_label }}
                                    @if($ev->time_label) &middot; {{ $ev->time_label }} @endif
                                    @if($ev->location) &middot; {{ $ev->location }} @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
</div>
