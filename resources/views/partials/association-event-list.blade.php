{{--
  Upcoming / Past event list for the association calendar subpage.
  Pass: $upcoming (collection), $past (paginator)
--}}
@php
    $isAdmin = request()->routeIs('admin.*');
@endphp

<style>
    .ael-section { margin-bottom: 26px; }
    .ael-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .ael-head h2 { font-size: 16px; font-weight: 800; color: #1e2922; margin: 0; }
    .ael-count { font-size: 12px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 3px 10px; border-radius: 20px; }
    .ael-card { display: flex; flex-direction: column; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 18px; margin-bottom: 10px; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
    .ael-card.is-past { opacity: .72; }
    /* A notice about an event opens this page on that event: the card it
       points at says so. */
    .ael-card:target { border-color: #648268; box-shadow: 0 0 0 3px rgba(100, 130, 104, .18); }
    /* Top row: date sits above the content on the left, edit/remove opposite it. */
    .ael-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
    .ael-date { display: inline-flex; align-items: baseline; gap: 5px; }
    .ael-date .dow { font-size: 12.5px; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: .05em; }
    .ael-date .dnum { font-size: 19px; font-weight: 800; color: #1a2b1e; line-height: 1.1; }
    .ael-date .mon { font-size: 13px; font-weight: 700; color: #475569; }
    .ael-body { flex: 1; min-width: 0; }
    .ael-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px; }
    .ael-type { display: inline-flex; align-items: center; gap: 7px; font-size: 11.5px; font-weight: 800; padding: 7px 15px; border-radius: 20px; }
    .ael-title { font-size: 15.5px; font-weight: 700; color: #1a2b1e; }
    .ael-meta { font-size: 13px; color: #64748b; margin-top: 2px; }
    .ael-desc { font-size: 13.5px; color: #475569; margin-top: 6px; line-height: 1.55; white-space: pre-line; }
    .ael-actions { display: flex; gap: 8px; align-items: center; margin-left: auto; }
    .ael-btn { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; padding: 7px 13px; border-radius: 9px; border: 1.5px solid #e2e8f0; background: #edf1e9; color: #648268; cursor: pointer; }
    .ael-btn:hover { background: #f1f5f9; }
    .ael-btn.danger { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
    .ael-empty { background: #fff; border: 1.5px dashed #e2e8f0; border-radius: 14px; padding: 28px 20px; text-align: center; color: #94a3b8; font-size: 13.5px; }
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
                <div class="ael-card-head">
                    <div class="ael-date">
                        <span class="dow">{{ $ev->event_date->format('D') }},</span>
                        <span class="dnum">{{ $ev->event_date->format('j') }}</span>
                        <span class="mon">{{ $ev->event_date->format('M') }}</span>
                    </div>
                    @if($isAdmin)
                        <div class="ael-actions">
                            <button type="button" class="ael-btn js-open-event-modal"
                                    data-mode="edit"
                                    data-id="{{ $ev->id }}"
                                    data-title="{{ $ev->title }}"
                                    data-type="{{ $ev->event_type }}"
                                    data-date="{{ $ev->event_date->toDateString() }}"
                                    data-start="{{ $ev->start_time?->format('H:i') }}"
                                    data-end="{{ $ev->end_time?->format('H:i') }}"
                                    data-location="{{ $ev->location }}"
                                    data-description="{{ $ev->description }}">Edit</button>
                            <form method="POST" action="{{ route('admin.calendar.events.destroy', $ev) }}"
                                  onsubmit="return confirm('Remove this event from the calendar?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ael-btn danger">Delete</button>
                            </form>
                        </div>
                    @endif
                </div>
                <div class="ael-body">
                    <div class="ael-top">
                        <span class="ael-type" style="color: {{ $ev->type_color }}; background: {{ $ev->type_color }}14;">
                            <span style="width:7px;height:7px;border-radius:50%;background:{{ $ev->type_color }};"></span>
                            {{ $ev->type_label }}
                        </span>
                        <span class="ael-title">{{ $ev->title }}</span>
                        @if($ev->event_date->isToday())
                            <span class="ael-type" style="color:#4A7A54;background:#f0fdf4;">Today</span>
                        @endif
                    </div>
                    <div class="ael-meta">
                        {{ $ev->event_date->format('l, F j, Y') }}
                        @if($ev->time_label) &middot; {{ $ev->time_label }} @endif
                        @if($ev->location) &middot; {{ $ev->location }} @endif
                    </div>
                    @if($ev->description)<div class="ael-desc">{{ $ev->description }}</div>@endif
                </div>
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
                <div class="ael-card-head">
                    <div class="ael-date">
                        <span class="dow">{{ $ev->event_date->format('D') }},</span>
                        <span class="dnum">{{ $ev->event_date->format('j') }}</span>
                        <span class="mon">{{ $ev->event_date->format('M') }}</span>
                    </div>
                    @if($isAdmin)
                        <div class="ael-actions">
                            <button type="button" class="ael-btn js-open-event-modal"
                                    data-mode="edit"
                                    data-id="{{ $ev->id }}"
                                    data-title="{{ $ev->title }}"
                                    data-type="{{ $ev->event_type }}"
                                    data-date="{{ $ev->event_date->toDateString() }}"
                                    data-start="{{ $ev->start_time?->format('H:i') }}"
                                    data-end="{{ $ev->end_time?->format('H:i') }}"
                                    data-location="{{ $ev->location }}"
                                    data-description="{{ $ev->description }}">Edit</button>
                            <form method="POST" action="{{ route('admin.calendar.events.destroy', $ev) }}"
                                  onsubmit="return confirm('Remove this event from the calendar?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ael-btn danger">Delete</button>
                            </form>
                        </div>
                    @endif
                </div>
                <div class="ael-body">
                    <div class="ael-top">
                        <span class="ael-type" style="color: {{ $ev->type_color }}; background: {{ $ev->type_color }}14;">
                            <span style="width:7px;height:7px;border-radius:50%;background:{{ $ev->type_color }};"></span>
                            {{ $ev->type_label }}
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
            </div>
        @endforeach
        <div style="margin-top: 14px;">{{ $past->links('partials.pagination') }}</div>
    @endif
</div>
