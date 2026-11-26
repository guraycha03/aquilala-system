{{--
  Today event card — the only "notification" farmers get.
  Pass: $todayEvents
--}}
@php
    $calTypes = \App\Models\AssociationEvent::TYPES;
    $toneClass = fn (string $type): string => in_array($type, array_keys($calTypes), true)
        ? 'cal-tone-'.$type
        : 'cal-tone-other';
@endphp

<style>
    .cal-today {
        background: var(--cal-wash, #f8faf8);
        border: 1px solid var(--cal-line, #e7ece6);
        border-radius: 14px;
        padding: 15px 18px;
    }
    .cal-today-head { display: flex; align-items: center; gap: 9px; margin-bottom: 9px; flex-wrap: wrap; }
    .cal-today-badge {
        background: var(--cal-accent-soft, #eef4ee);
        color: var(--cal-accent, #5c7f66);
        font-size: 10px; font-weight: 750;
        text-transform: uppercase; letter-spacing: .05em;
        padding: 3px 9px; border-radius: 7px;
    }
    .cal-today-head strong { font-size: 13.5px; color: var(--cal-ink, #26342b); }
    .cal-today-row {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 9px 0; border-top: 1px solid var(--cal-line-soft, #f0f3ef);
    }
    .cal-today-row:first-of-type { border-top: 0; }
    .cal-today-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--tone-dot, #a79b88);
        margin-top: 5px; flex-shrink: 0;
    }
    .cal-today-title { font-size: 13px; font-weight: 700; color: var(--cal-ink, #26342b); }
    .cal-today-meta { font-size: 11.5px; color: var(--cal-muted, #748078); margin-top: 2px; line-height: 1.5; }
    .cal-today-empty { font-size: 12.5px; color: var(--cal-muted, #748078); margin: 0; }
</style>

<div class="cal-today">
    <div class="cal-today-head">
        <span class="cal-today-badge">Today</span>
        <strong>{{ now()->format('l, F j, Y') }}</strong>
    </div>
    @if($todayEvents->isEmpty())
        <p class="cal-today-empty">No events scheduled today.</p>
    @else
        @foreach($todayEvents as $ev)
            <div class="cal-today-row {{ $toneClass((string) $ev->event_type) }}">
                <span class="cal-today-dot"></span>
                <div>
                    <div class="cal-today-title">{{ $ev->title }}</div>
                    <div class="cal-today-meta">
                        {{ $ev->type_label }}
                        @if($ev->time_label) &middot; {{ $ev->time_label }} @endif
                        @if($ev->location) &middot; {{ $ev->location }} @endif
                    </div>
                    @if($ev->description)<div class="cal-today-meta">{{ $ev->description }}</div>@endif
                </div>
            </div>
        @endforeach
    @endif
</div>
