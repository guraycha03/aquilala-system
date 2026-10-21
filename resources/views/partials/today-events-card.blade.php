{{--
  Today event card — the only "notification" farmers get.
  Pass: $todayEvents
--}}
<style>
    .cal-today { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 16px 20px; }
    .cal-today-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .cal-today-badge { background: #4A7A54; color: #fff; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; padding: 3px 10px; border-radius: 20px; }
    .cal-today-head strong { font-size: 14.5px; color: #14532d; }
    .cal-today-row { display: flex; align-items: flex-start; gap: 12px; padding: 9px 0; border-top: 1px dashed #bbf7d0; }
    .cal-today-row:first-of-type { border-top: 0; }
    .cal-today-dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
    .cal-today-title { font-size: 14px; font-weight: 700; color: #166534; }
    .cal-today-meta { font-size: 12.5px; color: #4d7c5f; margin-top: 1px; }
    .cal-today-empty { font-size: 13.5px; color: #4d7c5f; margin: 0; }
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
            <div class="cal-today-row">
                <span class="cal-today-dot" style="background: {{ $ev->type_color }};"></span>
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
