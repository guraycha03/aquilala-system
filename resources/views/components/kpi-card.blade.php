@props([
    'title',
    'value',
    'trendValue' => null,
    'trendDirection' => 'up',
    'trendLabel' => 'this month',
    'accent' => '#16a34a',
])

@php
    $arrowPath = match($trendDirection) {
        'up' => 'M12 19V5M5 12l7-7 7 7',
        'down' => 'M12 5v14M5 12l7 7 7-7',
        default => 'M5 12h14',
    };
    $arrowColor = match($trendDirection) {
        'up' => '#16a34a',
        'down' => '#dc2626',
        default => '#64748b',
    };
    $arrowBg = match($trendDirection) {
        'up' => 'rgba(22,163,74,0.1)',
        'down' => 'rgba(220,38,38,0.1)',
        default => 'rgba(100,116,139,0.1)',
    };
@endphp

<div class="kpi-card group"
     style="background: rgba(255,255,255,0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.3); border-radius: 16px; padding: 22px;
            display: flex; flex-direction: column; justify-content: space-between; min-height: 140px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.04), 0 1px 4px rgba(0,0,0,0.02);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; overflow: hidden;">
    
    <!-- Glassmorphism accent bar -->
    <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, {{ $accent }}, {{ $accent }}88, transparent); opacity: 0.6;"></div>
    
    <div style="position: relative; z-index: 1;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px;">
            <h3 style="margin: 0; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b;">
                {{ $title }}
            </h3>
            @isset($icon)
                <div style="color: #94a3b8; display: flex; align-items: center; opacity: 0.7; transition: opacity 0.3s;">
                    {{ $icon }}
                </div>
            @endisset
        </div>
        <div style="font-size: 30px; font-weight: 800; color: #0f172a; line-height: 1.1; letter-spacing: -0.02em;">
            {{ $value }}
        </div>
    </div>

    @if($trendValue !== null)
        <div style="display: flex; align-items: center; gap: 8px; margin-top: 14px; padding-top: 14px; border-top: 1px solid rgba(0,0,0,0.04); position: relative; z-index: 1;">
            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 8px; font-weight: 700; font-size: 12px; background: {{ $arrowBg }}; color: {{ $arrowColor }}; transition: transform 0.3s;">
                @if($trendDirection !== 'neutral')
                    <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.3s;">
                        <path d="{{ $arrowPath }}" />
                    </svg>
                @endif
                {{ $trendValue }}
            </span>
            <span style="color: #94a3b8; font-size: 12px; font-weight: 500;">{{ $trendLabel }}</span>
        </div>
    @endif
</div>

<style>
    .kpi-card {
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
        border-color: rgba(255,255,255,0.5);
    }
    .kpi-card:hover .kpi-icon {
        opacity: 1;
    }
</style>
