@props(['weatherData' => [], 'hourlyForecast' => []])

@php
$w = $weatherData;
$condition = $w['condition'] ?? 'sunny';
$isNight = $w['is_night'] ?? false;
$temp = $w['temperature'] ?? 28;
$feelsLike = $w['feels_like'] ?? $temp;
$humidity = $w['humidity'] ?? 75;
$wind = $w['wind_speed'] ?? 12;
$uv = $w['uv_index'] ?? 5;
$rainProb = $w['rain_probability'] ?? 30;
$isReal = $w['is_real_data'] ?? false;

// Determine gradient theme based on weather
$gradient = match(true) {
    $isNight => 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%)',
    $condition === 'rain' || $rainProb > 60 => 'linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #4a7fb5 100%)',
    $condition === 'cloudy' || $rainProb > 30 => 'linear-gradient(135deg, #475569 0%, #64748b 50%, #94a3b8 100%)',
    $uv > 7 => 'linear-gradient(135deg, #0369a1 0%, #0ea5e9 50%, #7dd3fc 100%)',
    default => 'linear-gradient(135deg, #0284c7 0%, #38bdf8 50%, #bae6fd 100%)',
};

// Background pattern
$bgPattern = match(true) {
    $isNight => 'stars',
    $condition === 'rain' || $rainProb > 60 => 'rain',
    default => 'clouds',
};

// Weather icon
$iconSvg = match(true) {
    $isNight => '<svg viewBox="0 0 64 64" width="64" height="64"><circle cx="32" cy="32" r="14" fill="none" stroke="#fbbf24" stroke-width="2"/><circle cx="32" cy="32" r="10" fill="#fbbf24" opacity="0.3"/><circle cx="32" cy="32" r="4" fill="#fbbf24" opacity="0.6"/><circle cx="14" cy="20" r="2" fill="#fbbf24" opacity="0.5"/><circle cx="50" cy="18" r="1.5" fill="#fbbf24" opacity="0.4"/><circle cx="22" cy="12" r="1" fill="#fbbf24" opacity="0.6"/><circle cx="45" cy="28" r="1" fill="#fbbf24" opacity="0.3"/><circle cx="10" cy="38" r="1.5" fill="#fbbf24" opacity="0.5"/><circle cx="52" cy="38" r="1" fill="#fbbf24" opacity="0.4"/><circle cx="40" cy="10" r="1" fill="#fbbf24" opacity="0.5"/><circle cx="18" cy="45" r="1.2" fill="#fbbf24" opacity="0.3"/></svg>',
    $condition === 'rain' || $rainProb > 60 => '<svg viewBox="0 0 64 64" width="64" height="64"><ellipse cx="34" cy="30" rx="18" ry="12" fill="#94a3b8" opacity="0.6"/><ellipse cx="28" cy="28" rx="14" ry="10" fill="#cbd5e1" opacity="0.8"/><ellipse cx="32" cy="26" rx="12" ry="8" fill="#e2e8f0"/><line x1="18" y1="40" x2="16" y2="48" stroke="#60a5fa" stroke-width="2.5" stroke-linecap="round" opacity="0.8"/><line x1="26" y1="42" x2="23" y2="52" stroke="#60a5fa" stroke-width="2.5" stroke-linecap="round" opacity="0.7"/><line x1="34" y1="40" x2="32" y2="50" stroke="#60a5fa" stroke-width="2.5" stroke-linecap="round" opacity="0.8"/><line x1="42" y1="42" x2="40" y2="52" stroke="#60a5fa" stroke-width="2.5" stroke-linecap="round" opacity="0.7"/><line x1="50" y1="38" x2="48" y2="46" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" opacity="0.6"/></svg>',
    $condition === 'cloudy' || $rainProb > 30 => '<svg viewBox="0 0 64 64" width="64" height="64"><circle cx="24" cy="28" r="8" fill="#94a3b8" opacity="0.5"/><ellipse cx="34" cy="30" rx="18" ry="12" fill="#94a3b8" opacity="0.6"/><ellipse cx="28" cy="28" rx="14" ry="10" fill="#cbd5e1" opacity="0.8"/><ellipse cx="32" cy="26" rx="12" ry="8" fill="#e2e8f0"/><circle cx="38" cy="22" r="6" fill="#f1f5f9" opacity="0.9"/></svg>',
    default => '<svg viewBox="0 0 64 64" width="64" height="64"><circle cx="32" cy="32" r="16" fill="#fbbf24" opacity="0.2"/><circle cx="32" cy="32" r="12" fill="#fbbf24" opacity="0.4"/><circle cx="32" cy="32" r="8" fill="#fde047" opacity="0.8"/><circle cx="32" cy="32" r="6" fill="#fef08a"/><line x1="32" y1="6" x2="32" y2="12" stroke="#fbbf24" stroke-width="2.5" stroke-linecap="round"/><line x1="32" y1="52" x2="32" y2="58" stroke="#fbbf24" stroke-width="2.5" stroke-linecap="round"/><line x1="8" y1="32" x2="14" y2="32" stroke="#fbbf24" stroke-width="2.5" stroke-linecap="round"/><line x1="50" y1="32" x2="56" y2="32" stroke="#fbbf24" stroke-width="2.5" stroke-linecap="round"/><line x1="14.5" y1="14.5" x2="18.5" y2="18.5" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/><line x1="45.5" y1="45.5" x2="49.5" y2="49.5" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/><line x1="14.5" y1="49.5" x2="18.5" y2="45.5" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/><line x1="45.5" y1="18.5" x2="49.5" y2="14.5" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"/></svg>',
};

// Radial gauge helpers
function gaugeArc($value, $min, $max, $size = 60, $stroke = 8) {
    $r = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $r;
    $normalized = max(0, min(1, ($value - $min) / ($max - $min)));
    $offset = $circumference * (1 - $normalized);
    return ['r' => $r, 'circ' => $circumference, 'offset' => $offset, 'pct' => round($normalized * 100)];
}

$humidityGauge = gaugeArc($humidity, 0, 100);
$uvGauge = gaugeArc($uv, 0, 14);
$windGauge = gaugeArc($wind, 0, 60);
$rainGauge = gaugeArc($rainProb, 0, 100);

$humidityColor = $humidity > 80 ? '#0284c7' : ($humidity > 50 ? '#16a34a' : '#d97706');
$uvColor = $uv > 7 ? '#dc2626' : ($uv > 4 ? '#d97706' : '#16a34a');
$windColor = $wind > 40 ? '#dc2626' : ($wind > 20 ? '#d97706' : '#16a34a');
$rainColor = $rainProb > 60 ? '#0284c7' : ($rainProb > 30 ? '#d97706' : '#16a34a');
@endphp

<div class="weather-widget glass-panel" style="background: {{ $gradient }}; border-radius: 20px; padding: 24px; position: relative; overflow: hidden; min-height: 380px; box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);">

    <!-- Animated Background Pattern -->
    @if($bgPattern === 'rain')
    <div class="rain-container" style="position: absolute; inset: 0; pointer-events: none; overflow: hidden; opacity: 0.4;">
        @for($i = 0; $i < 20; $i++)
        <div class="rain-drop" style="left: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 20) / 10 }}s; animation-duration: {{ 0.5 + rand(0, 10) / 10 }}s;"></div>
        @endfor
    </div>
    @elseif($bgPattern === 'stars')
    <div style="position: absolute; inset: 0; pointer-events: none; overflow: hidden;">
        @for($i = 0; $i < 30; $i++)
        <div class="star" style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 30) / 10 }}s; animation-duration: {{ 1 + rand(0, 20) / 10 }}s;"></div>
        @endfor
    </div>
    @else
    <div class="cloud-drift" style="position: absolute; inset: 0; pointer-events: none; overflow: hidden; opacity: 0.15;">
        <div class="cloud cloud-1"></div>
        <div class="cloud cloud-2"></div>
        <div class="cloud cloud-3"></div>
    </div>
    @endif

    <!-- Glassmorphism overlay -->
    <div style="position: absolute; inset: 0; background: rgba(255,255,255,0.06); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px); border-radius: 20px; pointer-events: none;"></div>

    <!-- Content -->
    <div style="position: relative; z-index: 2;">
        <!-- Header Row -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span style="color: rgba(255,255,255,0.9); font-size: 13px; font-weight: 600;">Bulan, Sorsogon</span>
                    <span style="color: rgba(255,255,255,0.5); font-size: 11px;">12.67°N, 123.88°E</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: {{ $isReal ? '#22c55e' : '#f59e0b' }}; animation: pulse-dot 2s infinite;"></span>
                    <span style="color: rgba(255,255,255,0.6); font-size: 11px;">{{ $isReal ? 'Real-time data' : 'Simulated data' }}</span>
                </div>
            </div>
            <div class="weather-icon-container" style="opacity: 0.9; filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2));">
                {!! $iconSvg !!}
            </div>
        </div>

        <!-- Main Temperature -->
        <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 20px;">
            <span style="font-size: 64px; font-weight: 800; color: white; line-height: 1; letter-spacing: -0.03em; text-shadow: 0 2px 12px rgba(0,0,0,0.15);">{{ round($temp) }}°</span>
            <div style="padding-top: 8px;">
                <div style="color: rgba(255,255,255,0.8); font-size: 14px; font-weight: 500;">Feels like {{ round($feelsLike) }}°</div>
                <div style="color: rgba(255,255,255,0.6); font-size: 13px; text-transform: capitalize;">{{ $w['condition'] ?? 'Clear' }}</div>
            </div>
        </div>

        <!-- Metric Gauges Grid -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px;">
            <!-- Humidity -->
            <div class="gauge-card" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 14px; padding: 12px 8px; text-align: center; border: 1px solid rgba(255,255,255,0.1);">
                <svg viewBox="0 0 60 60" width="44" height="44" style="display: block; margin: 0 auto 6px;">
                    <circle cx="30" cy="30" r="{{ $humidityGauge['r'] }}" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="6"/>
                    <circle cx="30" cy="30" r="{{ $humidityGauge['r'] }}" fill="none" stroke="{{ $humidityColor }}" stroke-width="6" stroke-linecap="round"
                            stroke-dasharray="{{ $humidityGauge['circ'] }}" stroke-dashoffset="{{ $humidityGauge['offset'] }}" transform="rotate(-90 30 30)" style="transition: stroke-dashoffset 1.5s ease-out;"/>
                    <text x="30" y="34" text-anchor="middle" fill="white" font-size="14" font-weight="700">{{ $humidityGauge['pct'] }}%</text>
                </svg>
                <div style="color: rgba(255,255,255,0.6); font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Humidity</div>
            </div>

            <!-- UV Index -->
            <div class="gauge-card" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 14px; padding: 12px 8px; text-align: center; border: 1px solid rgba(255,255,255,0.1);">
                <svg viewBox="0 0 60 60" width="44" height="44" style="display: block; margin: 0 auto 6px;">
                    <circle cx="30" cy="30" r="{{ $uvGauge['r'] }}" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="6"/>
                    <circle cx="30" cy="30" r="{{ $uvGauge['r'] }}" fill="none" stroke="{{ $uvColor }}" stroke-width="6" stroke-linecap="round"
                            stroke-dasharray="{{ $uvGauge['circ'] }}" stroke-dashoffset="{{ $uvGauge['offset'] }}" transform="rotate(-90 30 30)" style="transition: stroke-dashoffset 1.5s ease-out;"/>
                    <text x="30" y="34" text-anchor="middle" fill="white" font-size="14" font-weight="700">{{ $uv }}</text>
                </svg>
                <div style="color: rgba(255,255,255,0.6); font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">UV Index</div>
            </div>

            <!-- Wind Speed -->
            <div class="gauge-card" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 14px; padding: 12px 8px; text-align: center; border: 1px solid rgba(255,255,255,0.1);">
                <svg viewBox="0 0 60 60" width="44" height="44" style="display: block; margin: 0 auto 6px;">
                    <circle cx="30" cy="30" r="{{ $windGauge['r'] }}" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="6"/>
                    <circle cx="30" cy="30" r="{{ $windGauge['r'] }}" fill="none" stroke="{{ $windColor }}" stroke-width="6" stroke-linecap="round"
                            stroke-dasharray="{{ $windGauge['circ'] }}" stroke-dashoffset="{{ $windGauge['offset'] }}" transform="rotate(-90 30 30)" style="transition: stroke-dashoffset 1.5s ease-out;"/>
                    <text x="30" y="34" text-anchor="middle" fill="white" font-size="11" font-weight="700">{{ round($wind) }}</text>
                </svg>
                <div style="color: rgba(255,255,255,0.6); font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Wind km/h</div>
            </div>

            <!-- Rain Probability -->
            <div class="gauge-card" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 14px; padding: 12px 8px; text-align: center; border: 1px solid rgba(255,255,255,0.1);">
                <svg viewBox="0 0 60 60" width="44" height="44" style="display: block; margin: 0 auto 6px;">
                    <circle cx="30" cy="30" r="{{ $rainGauge['r'] }}" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="6"/>
                    <circle cx="30" cy="30" r="{{ $rainGauge['r'] }}" fill="none" stroke="{{ $rainColor }}" stroke-width="6" stroke-linecap="round"
                            stroke-dasharray="{{ $rainGauge['circ'] }}" stroke-dashoffset="{{ $rainGauge['offset'] }}" transform="rotate(-90 30 30)" style="transition: stroke-dashoffset 1.5s ease-out;"/>
                    <text x="30" y="34" text-anchor="middle" fill="white" font-size="11" font-weight="700">{{ $rainProb }}%</text>
                </svg>
                <div style="color: rgba(255,255,255,0.6); font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Rain Prob.</div>
            </div>
        </div>

        <!-- Hourly Forecast Chart Container -->
        <div style="background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border-radius: 14px; padding: 14px; border: 1px solid rgba(255,255,255,0.08);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="color: rgba(255,255,255,0.7); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">24-Hour Forecast</span>
                <span style="color: rgba(255,255,255,0.4); font-size: 10px;">Temperature °C | Rain %</span>
            </div>
            <canvas id="hourlyChart" width="400" height="160" style="width: 100%; height: 160px; display: block;"></canvas>
        </div>
    </div>
</div>

<style>
    .weather-widget { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    .weather-widget:hover { transform: translateY(-4px); box-shadow: 0 16px 48px rgba(0,0,0,0.18); }
    .gauge-card { transition: all 0.3s ease; }
    .gauge-card:hover { background: rgba(255,255,255,0.15) !important; transform: translateY(-2px); }
    .weather-icon-container { transition: transform 0.5s ease; }
    .weather-widget:hover .weather-icon-container { transform: scale(1.05) rotate(5deg); }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }

    /* Rain drops */
    .rain-drop {
        position: absolute;
        width: 2px;
        height: 18px;
        background: linear-gradient(transparent, rgba(148,163,184,0.6));
        top: -20px;
        animation: rain-fall 0.8s linear infinite;
    }
    @keyframes rain-fall {
        0% { transform: translateY(-20px); opacity: 0; }
        10% { opacity: 1; }
        100% { transform: translateY(400px); opacity: 0; }
    }

    /* Stars */
    .star {
        position: absolute;
        width: 3px;
        height: 3px;
        background: white;
        border-radius: 50%;
        animation: twinkle 2s ease-in-out infinite;
    }
    @keyframes twinkle {
        0%, 100% { opacity: 0.2; transform: scale(0.8); }
        50% { opacity: 1; transform: scale(1.2); }
    }

    /* Cloud drift */
    .cloud {
        position: absolute;
        background: rgba(255,255,255,0.8);
        border-radius: 50%;
    }
    .cloud-1 { width: 80px; height: 30px; top: 20%; animation: drift 25s linear infinite; }
    .cloud-2 { width: 60px; height: 24px; top: 50%; animation: drift 35s linear infinite; animation-delay: -10s; }
    .cloud-3 { width: 100px; height: 35px; top: 70%; animation: drift 30s linear infinite; animation-delay: -20s; }
    @keyframes drift {
        0% { transform: translateX(-120px); }
        100% { transform: translateX(calc(100vw + 120px)); }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('hourlyChart');
    if (!canvas || typeof Chart === 'undefined') return;

    const forecast = @json($hourlyForecast);
    if (!forecast || forecast.length === 0) return;

    const labels = forecast.map(h => h.time || '');
    const temps = forecast.map(h => h.temperature || 0);
    const rains = forecast.map(h => h.rain_probability || 0);

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Temp °C',
                    data: temps,
                    borderColor: 'rgba(251,191,36,0.9)',
                    backgroundColor: 'rgba(251,191,36,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    pointBackgroundColor: 'rgba(251,191,36,1)',
                    borderWidth: 2.5,
                    yAxisID: 'y',
                },
                {
                    label: 'Rain %',
                    data: rains,
                    borderColor: 'rgba(96,165,250,0.7)',
                    backgroundColor: 'rgba(96,165,250,0.05)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointHoverRadius: 5,
                    pointBackgroundColor: 'rgba(96,165,250,1)',
                    borderWidth: 2,
                    borderDash: [5, 3],
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,0.9)',
                    titleColor: '#fff',
                    bodyColor: 'rgba(255,255,255,0.8)',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: true,
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 9 }, maxTicksLimit: 8 }
                },
                y: {
                    position: 'left',
                    grid: { color: 'rgba(255,255,255,0.06)' },
                    ticks: { color: 'rgba(255,255,255,0.5)', font: { size: 9 }, maxTicksLimit: 5 },
                    min: Math.min(...temps) - 5,
                    max: Math.max(...temps) + 5,
                },
                y1: {
                    position: 'right',
                    grid: { display: false },
                    ticks: { color: 'rgba(255,255,255,0.4)', font: { size: 9 }, maxTicksLimit: 4 },
                    max: 100,
                    min: 0,
                }
            }
        }
    });
});
</script>
