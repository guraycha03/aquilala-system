{{--
  Left-hand panel: every event of the displayed month, grouped by date.
  Pass: $year, $month, $monthLabel, $events
  The card always renders — an empty month shows a quiet empty state.
--}}
@php
    $panelMonth  = \Carbon\Carbon::create((int) $year, (int) $month, 1)->format('Y-m');
    $todayStr    = now()->toDateString();
    $monthEvents = $events
        ->filter(fn ($e) => $e->event_date->format('Y-m') === $panelMonth)
        ->groupBy(fn ($e) => $e->event_date->toDateString());
    $isPanelAdmin = request()->routeIs('admin.*');
    $calTypes = \App\Models\AssociationEvent::TYPES;
    $toneClass = fn (string $type): string => in_array($type, array_keys($calTypes), true)
        ? 'cal-tone-'.$type
        : 'cal-tone-other';
@endphp

<style>
    .ev-card {
        background: var(--cal-surface, #fff);
        border: 1px solid var(--cal-line, #e7ece6);
        border-radius: 14px;
        overflow: hidden;
        display: flex; flex-direction: column;
    }
    .ev-head {
        display: flex; align-items: center; justify-content: space-between; gap: 10px;
        padding: 13px 17px;
        background: var(--cal-wash, #f8faf8);
        border-bottom: 1px solid var(--cal-line-soft, #f0f3ef);
    }
    .ev-head h3 { margin: 0; font-size: 14px; font-weight: 750; color: var(--cal-ink, #26342b); }

    .ev-body { padding: 2px 17px 12px; max-height: 660px; overflow-y: auto; }
    .ev-day { padding: 12px 0 7px; border-bottom: 1px solid var(--cal-line-soft, #f0f3ef); }
    .ev-day:last-child { border-bottom: 0; }
    .ev-day-label {
        display: flex; align-items: center; gap: 8px;
        font-size: 10.5px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .05em; color: var(--cal-muted, #748078); margin-bottom: 6px;
    }
    .ev-today-chip {
        background: var(--cal-accent-soft, #eef4ee);
        color: var(--cal-accent, #5c7f66);
        border-radius: 7px; padding: 2px 7px;
        font-size: 9.5px; letter-spacing: .03em;
    }
    .ev-item { display: flex; gap: 9px; padding: 6px 0; }
    .ev-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--tone-dot, #a79b88);
        margin-top: 5px; flex-shrink: 0;
    }
    .ev-title { font-size: 13px; font-weight: 700; color: var(--cal-ink, #26342b); }
    .ev-meta { font-size: 11.5px; color: var(--cal-muted, #748078); margin-top: 2px; line-height: 1.5; }

    .ev-empty { padding: 40px 18px 44px; text-align: center; }
    .ev-empty svg { color: #c8d2c9; }
    .ev-empty-title { font-size: 13.5px; font-weight: 700; color: var(--cal-muted, #748078); margin-top: 11px; }
    .ev-empty-sub { font-size: 12px; color: var(--cal-muted, #748078); margin-top: 4px; line-height: 1.5; }
</style>

<div class="ev-card">
    <div class="ev-head">
        <h3>Events &middot; {{ $monthLabel }}</h3>
        <a class="cal-link" href="{{ $isPanelAdmin ? route('admin.calendar.events') : route('farmer.calendar.events') }}">All events &rarr;</a>
    </div>

    <div class="ev-body">
        @if($monthEvents->isEmpty())
            <div class="ev-empty">
                <svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.5">
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
                    <div class="ev-day-label">
                        {{ $firstEvent->event_date->format('D, M j') }}
                        @if($isToday)<span class="ev-today-chip">Today</span>@endif
                    </div>
                    @foreach($dayEvents as $ev)
                        <div class="ev-item {{ $toneClass((string) $ev->event_type) }}">
                            <span class="ev-dot"></span>
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
