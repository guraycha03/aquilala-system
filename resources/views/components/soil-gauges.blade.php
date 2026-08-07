@props(['soilIndicators' => []])

@php
$s = $soilIndicators;
$moisture = $s['moisture'] ?? 0;
$moisturePct = $s['moisture_percent'] ?? 0;
$moistureStatus = $s['moisture_status'] ?? ['label' => 'Unknown', 'color' => '#64748b', 'bg' => '#f1f5f9'];
$fieldCapacity = $s['field_capacity'] ?? 75;
$wiltingPoint = $s['wilting_point'] ?? 25;
$soilTemp = $s['surface_temperature'] ?? 25;
$et = $s['evapotranspiration'] ?? 3.5;
$etStatus = $s['et_status'] ?? ['label' => 'Moderate', 'color' => '#16a34a', 'bg' => '#f0fdf4'];
$fieldReadiness = $s['field_readiness'] ?? ['score' => 0, 'label' => 'Unknown', 'color' => '#64748b'];
$soilTypeLabel = $s['soil_type_label'] ?? 'Clay Loam';
$recommendation = $s['recommendation'] ?? 'Standard field monitoring recommended.';

// SVG gauge helpers
function circleProps($value, $min, $max, $size = 120, $stroke = 10) {
    $r = ($size - $stroke) / 2;
    $circ = 2 * M_PI * $r;
    $norm = max(0, min(1, ($value - $min) / ($max - $min)));
    $offset = $circ * (1 - $norm);
    return ['r' => $r, 'circ' => $circ, 'offset' => $offset, 'pct' => round($norm * 100)];
}

$moistureGauge = circleProps($moisturePct, 0, 100);
$tempGauge = circleProps($soilTemp, 10, 50);
$etGauge = circleProps($et, 0, 8);

$moistureArcColor = $moistureStatus['color'];
$tempArcColor = $soilTemp > 35 ? '#dc2626' : ($soilTemp > 28 ? '#d97706' : '#16a34a');
$etArcColor = $etStatus['color'];
@endphp

<div class="glass-panel" style="background: rgba(255,255,255,0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-radius: 20px; padding: 24px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 4px 24px rgba(0,0,0,0.04), 0 1px 4px rgba(0,0,0,0.02);">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
        <div>
            <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Soil &amp; Agronomy Indicators</h3>
            <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">{{ $soilTypeLabel }} &middot; Real-time field conditions</p>
        </div>
        <span class="readiness-pill" style="background: {{ $fieldReadiness['color'] }}18; color: {{ $fieldReadiness['color'] }}; border: 1px solid {{ $fieldReadiness['color'] }}33; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: {{ $fieldReadiness['color'] }}; animation: pulse-dot 2s infinite;"></span>
            {{ $fieldReadiness['label'] }}
        </span>
    </div>

    <!-- 3 Gauge Grid -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">

        <!-- Soil Moisture -->
        <div class="gauge-card" style="background: rgba(255,255,255,0.5); border-radius: 16px; padding: 16px 10px; text-align: center; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(4px);">
            <svg viewBox="0 0 120 120" width="90" height="90" style="display: block; margin: 0 auto 8px;">
                <circle cx="60" cy="60" r="{{ $moistureGauge['r'] }}" fill="none" stroke="rgba(0,0,0,0.04)" stroke-width="10"/>
                <circle cx="60" cy="60" r="{{ $moistureGauge['r'] }}" fill="none" stroke="{{ $moistureArcColor }}" stroke-width="10" stroke-linecap="round"
                        stroke-dasharray="{{ $moistureGauge['circ'] }}" stroke-dashoffset="{{ $moistureGauge['offset'] }}" transform="rotate(-90 60 60)" style="transition: stroke-dashoffset 2s ease-out;"/>
                <!-- Field capacity marker -->
                @php $fcAngle = (1 - $fieldCapacity / 100) * 360; @endphp
                <line x1="60" y1="60" x2="{{ 60 + cos(deg2rad(-90 + $fcAngle)) * ($moistureGauge['r'] + 4) }}"
                      y2="{{ 60 + sin(deg2rad(-90 + $fcAngle)) * ($moistureGauge['r'] + 4) }}"
                      stroke="#16a34a" stroke-width="2" stroke-dasharray="3,2" opacity="0.6"/>
                <text x="60" y="56" text-anchor="middle" fill="#0f172a" font-size="22" font-weight="800">{{ $moisturePct }}%</text>
                <text x="60" y="72" text-anchor="middle" fill="{{ $moistureStatus['color'] }}" font-size="10" font-weight="600">{{ $moistureStatus['label'] }}</text>
            </svg>
            <div style="display: flex; justify-content: center; gap: 12px; font-size: 9px; color: #94a3b8; font-weight: 500;">
                <span>FC: {{ $fieldCapacity }}%</span>
                <span>WP: {{ $wiltingPoint }}%</span>
            </div>
            <div style="font-size: 11px; color: #475569; font-weight: 600; margin-top: 4px;">Soil Moisture</div>
        </div>

        <!-- Soil Surface Temperature -->
        <div class="gauge-card" style="background: rgba(255,255,255,0.5); border-radius: 16px; padding: 16px 10px; text-align: center; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(4px);">
            <svg viewBox="0 0 120 120" width="90" height="90" style="display: block; margin: 0 auto 8px;">
                <circle cx="60" cy="60" r="{{ $tempGauge['r'] }}" fill="none" stroke="rgba(0,0,0,0.04)" stroke-width="10"/>
                <circle cx="60" cy="60" r="{{ $tempGauge['r'] }}" fill="none" stroke="{{ $tempArcColor }}" stroke-width="10" stroke-linecap="round"
                        stroke-dasharray="{{ $tempGauge['circ'] }}" stroke-dashoffset="{{ $tempGauge['offset'] }}" transform="rotate(-90 60 60)" style="transition: stroke-dashoffset 2s ease-out;"/>
                <text x="60" y="56" text-anchor="middle" fill="#0f172a" font-size="22" font-weight="800">{{ round($soilTemp) }}°</text>
                <text x="60" y="72" text-anchor="middle" fill="{{ $tempArcColor }}" font-size="10" font-weight="600">{{ $soilTemp > 35 ? 'Hot' : ($soilTemp > 28 ? 'Warm' : 'Cool') }}</text>
            </svg>
            <div style="font-size: 9px; color: #94a3b8; font-weight: 500;">Optimal: 18–28°C</div>
            <div style="font-size: 11px; color: #475569; font-weight: 600; margin-top: 4px;">Surface Temp</div>
        </div>

        <!-- Evapotranspiration -->
        <div class="gauge-card" style="background: rgba(255,255,255,0.5); border-radius: 16px; padding: 16px 10px; text-align: center; border: 1px solid rgba(255,255,255,0.2); backdrop-filter: blur(4px);">
            <svg viewBox="0 0 120 120" width="90" height="90" style="display: block; margin: 0 auto 8px;">
                <circle cx="60" cy="60" r="{{ $etGauge['r'] }}" fill="none" stroke="rgba(0,0,0,0.04)" stroke-width="10"/>
                <circle cx="60" cy="60" r="{{ $etGauge['r'] }}" fill="none" stroke="{{ $etArcColor }}" stroke-width="10" stroke-linecap="round"
                        stroke-dasharray="{{ $etGauge['circ'] }}" stroke-dashoffset="{{ $etGauge['offset'] }}" transform="rotate(-90 60 60)" style="transition: stroke-dashoffset 2s ease-out;"/>
                <text x="60" y="56" text-anchor="middle" fill="#0f172a" font-size="22" font-weight="800">{{ number_format($et, 1) }}</text>
                <text x="60" y="72" text-anchor="middle" fill="{{ $etStatus['color'] }}" font-size="10" font-weight="600">{{ $etStatus['label'] }}</text>
            </svg>
            <div style="font-size: 9px; color: #94a3b8; font-weight: 500;">mm/day &middot; Water loss</div>
            <div style="font-size: 11px; color: #475569; font-weight: 600; margin-top: 4px;">Evapotranspiration</div>
        </div>
    </div>

    <!-- Recommendation -->
    <div style="background: rgba(255,255,255,0.5); border-radius: 12px; padding: 14px; border: 1px solid rgba(255,255,255,0.2); display: flex; gap: 10px; align-items: flex-start;">
        <span style="font-size: 18px; flex-shrink: 0;">💡</span>
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 2px;">Field Advisory</div>
            <p style="font-size: 12px; color: #475569; margin: 0; line-height: 1.5;">{{ $recommendation }}</p>
        </div>
    </div>
</div>

<style>
    .gauge-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .gauge-card:hover { transform: translateY(-3px); background: rgba(255,255,255,0.7) !important; box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
</style>
