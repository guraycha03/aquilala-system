@props([
    'title',
    'value',
    'trendValue' => null,
    'trendDirection' => 'up',
    'trendLabel' => 'this month'
])

@php
    $directionColor = match($trendDirection) {
        'up' => [
            'bg' => '#e6f4ea',
            'text' => '#137333',
            'arrow' => 'M12 19V5M5 12l7-7 7 7'
        ],
        'down' => [
            'bg' => '#fce8e6',
            'text' => '#c5221f',
            'arrow' => 'M12 5v14M5 12l7 7 7-7'
        ],
        default => [
            'bg' => '#f1f3f4',
            'text' => '#5f6368',
            'arrow' => 'M5 12h14'
        ]
    };
@endphp

<div
    class="kpi-card"
    style="
        background:#fff;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:20px;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
        min-height:130px;
        box-shadow:0 1px 3px rgba(0,0,0,.05);
    "
>
    <div>
        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:flex-start;
                margin-bottom:12px;
            "
        >
            <h3
                style="
                    margin:0;
                    font-size:13px;
                    font-weight:600;
                    text-transform:uppercase;
                    letter-spacing:.05em;
                    color:#64748b;
                "
            >
                {{ $title }}
            </h3>

            @isset($icon)
                <div
                    style="
                        color:#94a3b8;
                        display:flex;
                        align-items:center;
                    "
                >
                    {{ $icon }}
                </div>
            @endisset
        </div>

        <div
            style="
                font-size:28px;
                font-weight:700;
                color:#1e2922;
                line-height:1.1;
            "
        >
            {{ $value }}
        </div>
    </div>

    @if($trendValue !== null)
        <div
            style="
                display:flex;
                align-items:center;
                gap:6px;
                margin-top:12px;
                font-size:13px;
            "
        >
            <span
                style="
                    display:inline-flex;
                    align-items:center;
                    gap:4px;
                    padding:2px 8px;
                    border-radius:6px;
                    font-weight:600;
                    font-size:12px;
                    background:{{ $directionColor['bg'] }};
                    color:{{ $directionColor['text'] }};
                "
            >
                @if($trendDirection !== 'neutral')
                    <svg
                        viewBox="0 0 24 24"
                        width="12"
                        height="12"
                        stroke="currentColor"
                        stroke-width="2.5"
                        fill="none"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="{{ $directionColor['arrow'] }}" />
                    </svg>
                @endif

                {{ $trendValue }}
            </span>

            <span style="color:#64748b;">
                {{ $trendLabel }}
            </span>
        </div>
    @endif
</div>