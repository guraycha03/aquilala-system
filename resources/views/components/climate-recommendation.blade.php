@props(['weatherContext' => 'normal', 'cropType' => 'Rice', 'landArea' => 1.0])

@php
$season = \App\Helpers\SeasonHelper::getCurrentSeason();
$climate = \App\Helpers\SeasonHelper::getClimateInfo();

$recs = match(true) {
    $weatherContext === 'typhoon' => [
        'title' => '🌧️ Heavy Rain / Typhoon Protocol',
        'advice' => 'Delay planting or harvesting by 2–3 days. Ensure drainage canals are clear. Apply fungicide proactively.',
        'icon' => 'alert-triangle',
        'color' => '#dc2626',
    ],
    $weatherContext === 'el_nino' => [
        'title' => '🌤️ El Niño / Drought Strategy',
        'advice' => 'Switch to drought-resistant varieties. Implement drip irrigation. Reduce nitrogen fertilizer by 20%.',
        'icon' => 'sun',
        'color' => '#d97706',
    ],
    $season === 'wet' => [
        'title' => '🌧️ Wet Season Recommendations',
        'advice' => 'Ideal for flood-tolerant rice varieties. Monitor for fungal diseases. Ensure proper drainage.',
        'icon' => 'cloud-rain',
        'color' => '#0284c7',
    ],
    default => [
        'title' => '☀️ Dry Season Recommendations',
        'advice' => 'High-yield varieties recommended. Schedule irrigation every 3–4 days. Prepare for potential heat stress.',
        'icon' => 'sun',
        'color' => '#d97706',
    ],
};
@endphp

<div class="glass-panel" style="background: rgba(255,255,255,0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border-radius: 20px; padding: 20px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 4px 24px rgba(0,0,0,0.04);">
    <div style="display: flex; gap: 14px; align-items: flex-start;">
        <div style="width: 42px; height: 42px; background: {{ $recs['color'] }}12; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 22px;">
            {{ str_contains($recs['title'], '🌧️') ? '🌧️' : (str_contains($recs['title'], '☀️') ? '☀️' : '🌤️') }}
        </div>
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0;">Climate-Aware Recommendation</h3>
                <span style="font-size: 11px; color: {{ $recs['color'] }}; font-weight: 600; background: {{ $recs['color'] }}10; padding: 2px 10px; border-radius: 20px;">{{ ucfirst($weatherContext) }}</span>
            </div>
            <p style="font-size: 13px; color: #475569; margin: 8px 0; line-height: 1.5;">{{ $recs['advice'] }}</p>
            <div style="display: flex; gap: 14px; font-size: 12px; color: #64748b;">
                <span>🌾 {{ $cropType }}</span>
                <span>📐 {{ $landArea }} ha</span>
                <span>📅 {{ $climate['label'] ?? $season }}</span>
            </div>
        </div>
    </div>
</div>
