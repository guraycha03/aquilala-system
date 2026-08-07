@props(['alerts' => []])

@if(!empty($alerts))
<div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
    @foreach($alerts as $alert)
    @php
    $severityColor = $alert['color'] ?? '#16a34a';
    $severityBg = $alert['bg_color'] ?? '#f0fdf4';
    $severityIcon = match($alert['severity'] ?? 'normal') {
        'severe' => '⚠️',
        'moderate' => '⚡',
        default => '✅',
    };
    @endphp
    <div class="alert-item glass-alert" style="background: {{ $severityBg }}ee; backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid {{ $severityColor }}44; border-radius: 16px; padding: 16px 18px; display: flex; gap: 14px; align-items: flex-start; box-shadow: 0 4px 16px rgba(0,0,0,0.03); transition: all 0.3s ease;">
        <div style="width: 36px; height: 36px; background: {{ $severityColor }}18; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px;">{{ $severityIcon }}</div>
        <div style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 14px; font-weight: 700; color: #0f172a;">{{ $alert['title'] ?? '' }}</span>
                <span class="severity-badge" style="background: {{ $severityColor }}22; color: {{ $severityColor }}; border: 1px solid {{ $severityColor }}33; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">{{ $alert['severity'] ?? 'info' }}</span>
            </div>
            <p style="font-size: 13px; color: #475569; margin: 6px 0 0 0; line-height: 1.5;">{{ $alert['message'] ?? '' }}</p>
            @if(!empty($alert['action_tip']))
            <div style="margin-top: 8px; padding: 8px 12px; background: {{ $severityColor }}0a; border-left: 3px solid {{ $severityColor }}; border-radius: 6px; font-size: 12px; color: {{ $severityColor }}; font-weight: 500;">
                💡 {{ $alert['action_tip'] }}
            </div>
            @endif
        </div>
    </div>
    @endforeach
</div>

<style>
    .alert-item { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .alert-item:hover { transform: translateX(4px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
</style>
@endif
