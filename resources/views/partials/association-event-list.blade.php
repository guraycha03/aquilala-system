{{--
  Upcoming / Past event list for the association calendar subpage.
  Pass: $upcoming (collection), $past (paginator)
  Same quiet list for farmer and admin; only admin gets the action buttons.
--}}
@php
    $isAdmin = request()->routeIs('admin.*');
    $calTypes = \App\Models\AssociationEvent::TYPES;
    $toneClass = fn (string $type): string => in_array($type, array_keys($calTypes), true)
        ? 'cal-tone-'.$type
        : 'cal-tone-other';
@endphp

<style>
    .ael-section { margin-bottom: 24px; }
    .ael-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
    .ael-head h2 { font-size: 15px; font-weight: 750; color: var(--cal-ink, #26342b); margin: 0; }
    .ael-count {
        font-size: 11.5px; font-weight: 700; color: var(--cal-muted, #748078);
        background: var(--cal-wash, #f8faf8);
        border: 1px solid var(--cal-line-soft, #f0f3ef);
        padding: 3px 9px; border-radius: 7px;
    }
    .ael-card {
        display: grid; grid-template-columns: 54px minmax(0, 1fr) auto;
        gap: 14px; align-items: start;
        background: var(--cal-surface, #fff);
        border: 1px solid var(--cal-line, #e7ece6);
        border-radius: 12px; padding: 14px 16px; margin-bottom: 9px;
    }
    .ael-card.is-past { background: var(--cal-wash, #f8faf8); }
    .ael-card:target { border-color: var(--cal-accent, #5c7f66); box-shadow: 0 0 0 2px var(--cal-accent-soft, #eef4ee); }

    .ael-date { text-align: center; padding-right: 10px; border-right: 1px solid var(--cal-line-soft, #f0f3ef); }
    .ael-date .dow { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--cal-muted, #748078); letter-spacing: .04em; }
    .ael-date .dnum { display: block; font-size: 19px; font-weight: 750; color: var(--cal-ink, #26342b); line-height: 1.15; }
    .ael-date .mon { display: block; font-size: 11px; font-weight: 700; color: var(--cal-muted, #748078); }

    .ael-body { min-width: 0; }
    .ael-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .ael-type {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 10.5px; font-weight: 700;
        color: var(--tone-ink, #776e60);
        background: var(--tone-wash, #f5f3ef);
        padding: 4px 9px; border-radius: 7px;
    }
    .ael-type .ael-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--tone-dot, #a79b88); }
    .ael-today { color: var(--cal-accent, #5c7f66); background: var(--cal-accent-soft, #eef4ee); }
    .ael-title { font-size: 14px; font-weight: 700; color: var(--cal-ink, #26342b); }
    .ael-meta { font-size: 11.5px; color: var(--cal-muted, #748078); margin-top: 3px; line-height: 1.5; }
    .ael-desc { font-size: 12.5px; color: var(--cal-muted, #748078); margin-top: 6px; line-height: 1.55; white-space: pre-line; }

    .ael-actions { display: flex; gap: 6px; align-items: center; }
    .ael-empty {
        background: var(--cal-wash, #f8faf8);
        border: 1px dashed var(--cal-line, #e7ece6);
        border-radius: 12px; padding: 24px 18px;
        text-align: center; color: var(--cal-muted, #748078); font-size: 12.5px;
    }

    @media (max-width: 640px) {
        .ael-card { grid-template-columns: 48px minmax(0, 1fr); gap: 12px; }
        .ael-actions { grid-column: 2; margin-top: 2px; }
    }
</style>

<div class="ael-section">
    <div class="ael-head">
        <h2>Upcoming Events</h2>
        <span class="ael-count">{{ $upcoming->count() }} {{ \Illuminate\Support\Str::plural('event', $upcoming->count()) }}</span>
    </div>

    @if($upcoming->isEmpty())
        <div class="ael-empty">No upcoming events yet. Check back soon.</div>
    @else
        @foreach($upcoming as $ev)
            <div class="ael-card" id="event-{{ $ev->id }}">
                <div class="ael-date">
                    <span class="dow">{{ $ev->event_date->format('D') }}</span>
                    <span class="dnum">{{ $ev->event_date->format('j') }}</span>
                    <span class="mon">{{ $ev->event_date->format('M') }}</span>
                </div>
                <div class="ael-body">
                    <div class="ael-top">
                        <span class="ael-type {{ $toneClass((string) $ev->event_type) }}">
                            <span class="ael-dot"></span>{{ $ev->type_label }}
                        </span>
                        <span class="ael-title">{{ $ev->title }}</span>
                        @if($ev->event_date->isToday())
                            <span class="ael-type ael-today">Today</span>
                        @endif
                    </div>
                    <div class="ael-meta">
                        {{ $ev->event_date->format('l, F j, Y') }}
                        @if($ev->time_label) &middot; {{ $ev->time_label }} @endif
                        @if($ev->location) &middot; {{ $ev->location }} @endif
                    </div>
                    @if($ev->description)<div class="ael-desc">{{ $ev->description }}</div>@endif
                </div>
                @if($isAdmin)
                    <div class="ael-actions">
                        <button type="button" class="cal-btn cal-btn--quiet js-open-event-modal"
                                data-mode="edit"
                                data-id="{{ $ev->id }}"
                                data-title="{{ $ev->title }}"
                                data-type="{{ $ev->event_type }}"
                                data-date="{{ $ev->event_date->toDateString() }}"
                                data-start="{{ $ev->start_time?->format('H:i') }}"
                                data-end="{{ $ev->end_time?->format('H:i') }}"
                                data-location="{{ $ev->location }}"
                                data-description="{{ $ev->description }}">Edit</button>
                        <form method="POST" action="{{ route('admin.calendar.events.destroy', $ev) }}" onsubmit="return false;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cal-btn cal-btn--danger"
                                data-aqui-confirm="Remove this event from the calendar?"
                                data-aqui-tone="warn"
                                data-aqui-title="Confirm"
                                data-aqui-confirm-label="Remove">Delete</button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    @endif
</div>

<div class="ael-section">
    <div class="ael-head">
        <h2>Past Events</h2>
        <span class="ael-count">{{ $past->total() }} {{ \Illuminate\Support\Str::plural('event', $past->total()) }}</span>
    </div>

    @if($past->isEmpty())
        <div class="ael-empty">No past events recorded.</div>
    @else
        @foreach($past as $ev)
            <div class="ael-card is-past" id="event-{{ $ev->id }}">
                <div class="ael-date">
                    <span class="dow">{{ $ev->event_date->format('D') }}</span>
                    <span class="dnum">{{ $ev->event_date->format('j') }}</span>
                    <span class="mon">{{ $ev->event_date->format('M') }}</span>
                </div>
                <div class="ael-body">
                    <div class="ael-top">
                        <span class="ael-type {{ $toneClass((string) $ev->event_type) }}">
                            <span class="ael-dot"></span>{{ $ev->type_label }}
                        </span>
                        <span class="ael-title">{{ $ev->title }}</span>
                    </div>
                    <div class="ael-meta">
                        {{ $ev->event_date->format('l, F j, Y') }}
                        @if($ev->time_label) &middot; {{ $ev->time_label }} @endif
                        @if($ev->location) &middot; {{ $ev->location }} @endif
                    </div>
                    @if($ev->description)<div class="ael-desc">{{ $ev->description }}</div>@endif
                </div>
                @if($isAdmin)
                    <div class="ael-actions">
                        <button type="button" class="cal-btn cal-btn--quiet js-open-event-modal"
                                data-mode="edit"
                                data-id="{{ $ev->id }}"
                                data-title="{{ $ev->title }}"
                                data-type="{{ $ev->event_type }}"
                                data-date="{{ $ev->event_date->toDateString() }}"
                                data-start="{{ $ev->start_time?->format('H:i') }}"
                                data-end="{{ $ev->end_time?->format('H:i') }}"
                                data-location="{{ $ev->location }}"
                                data-description="{{ $ev->description }}">Edit</button>
                        <form method="POST" action="{{ route('admin.calendar.events.destroy', $ev) }}" onsubmit="return false;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cal-btn cal-btn--danger"
                                data-aqui-confirm="Remove this event from the calendar?"
                                data-aqui-tone="warn"
                                data-aqui-title="Confirm"
                                data-aqui-confirm-label="Remove">Delete</button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
        <div style="margin-top: 14px;">{{ $past->links('partials.pagination') }}</div>
    @endif
</div>
