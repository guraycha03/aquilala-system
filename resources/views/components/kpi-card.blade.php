@props([
    'title',
    'value',
    'trendValue' => null,
    'trendDirection' => 'up',
    'trendLabel' => '',
    'accent' => '#16a34a',
    'link' => null,
    'iconBg' => null,
    'iconColor' => null,
])

@php
    $arrowPath = match($trendDirection) {
        'up' => 'M12 19V5M5 12l7-7 7 7',
        'down' => 'M12 5v14M5 12l7 7 7-7',
        default => 'M5 12h14',
    };

    // Detect numeric value for count-up animation
    $cleanValue = preg_replace('/[^0-9.]/', '', (string) $value);
    $numeric = $cleanValue !== '' && is_numeric($cleanValue);
    $hasDecimals = $numeric && (float) $cleanValue != (int) $cleanValue;
    $countTarget = $numeric ? (float) $cleanValue : null;
    $prefix = '';
    $suffix = '';
    if ($numeric) {
        $raw = trim((string) $value);
        if (str_starts_with($raw, '₱')) $prefix = '₱';
        if (str_ends_with($raw, '%')) $suffix = '%';
    }
@endphp

@if($link)
    <a href="{{ $link }}" class="kpi-card kpi-trend-{{ $trendDirection }}" data-accent="{{ $accent }}" @if($iconBg) data-chip-bg="{{ $iconBg }}" @endif @if($iconColor) data-chip-color="{{ $iconColor }}" @endif>
@else
    <div class="kpi-card kpi-trend-{{ $trendDirection }}" data-accent="{{ $accent }}" @if($iconBg) data-chip-bg="{{ $iconBg }}" @endif @if($iconColor) data-chip-color="{{ $iconColor }}" @endif>
@endif

    <div class="kpi-top">
        <h3 class="kpi-title">{{ $title }}</h3>
        @isset($icon)
            <div class="kpi-icon-chip">{{ $icon }}</div>
        @endisset
    </div>

    <div class="kpi-value" data-count-target="{{ $countTarget ?? '' }}" data-count-decimals="{{ $hasDecimals ? 2 : 0 }}" data-count-prefix="{{ $prefix }}" data-count-suffix="{{ $suffix }}">
        {{ $value }}
    </div>

    @if($trendValue !== null)
        <div class="kpi-foot">
            <span class="kpi-trend-pill">
                @if($trendDirection !== 'neutral')
                    <svg viewBox="0 0 24 24"><path d="{{ $arrowPath }}" /></svg>
                @endif
                {{ $trendValue }}
            </span>
            @if($trendLabel)
                <span class="kpi-trend-label">{{ $trendLabel }}</span>
            @endif
        </div>
    @endif
@if($link)
    </a>
@else
    </div>
@endif

<style>
    .kpi-card {
        --accent: #16a34a;
        --chip-bg: rgba(22, 163, 74, 0.10);
        --chip-color: var(--accent);
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 150px;
        padding: 22px 24px 20px;
        background: linear-gradient(160deg, rgba(255,255,255,0.78), rgba(255,255,255,0.42));
        backdrop-filter: blur(18px) saturate(160%);
        -webkit-backdrop-filter: blur(18px) saturate(160%);
        border: 1px solid rgba(255,255,255,0.65);
        border-radius: 16px;
        box-shadow: 0 8px 28px rgba(31, 45, 61, 0.10), inset 0 1px 0 rgba(255,255,255,0.7);
        text-decoration: none;
        transition: background 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    }
    .kpi-card:hover {
        background: linear-gradient(160deg, rgba(255,255,255,0.94), rgba(255,255,255,0.62));
        border-color: rgba(255,255,255,0.92);
        box-shadow: 0 10px 30px rgba(31, 45, 61, 0.14), inset 0 1px 0 rgba(255,255,255,0.9);
    }
    .kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
    }
    .kpi-title {
        margin: 0;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.09em;
        color: #64748b;
    }
    .kpi-icon-chip {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: var(--chip-bg);
        color: var(--chip-color);
    }
    .kpi-icon-chip svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .kpi-value {
        font-size: 32px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
        letter-spacing: -0.03em;
        display: inline-flex;
        align-items: baseline;
    }
    .kpi-foot {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
    }
    .kpi-trend-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        color: var(--trend-color);
    }
    .kpi-trend-up .kpi-trend-pill { background: rgba(22, 163, 74, 0.10); }
    .kpi-trend-down .kpi-trend-pill { background: rgba(220, 38, 38, 0.10); }
    .kpi-trend-neutral .kpi-trend-pill { background: rgba(100, 116, 139, 0.10); }
    .kpi-trend-pill svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .kpi-trend-label { font-size: 12px; font-weight: 500; color: #94a3b8; }
    .kpi-trend-up { --trend-color: #16a34a; }
    .kpi-trend-down { --trend-color: #dc2626; }
    .kpi-trend-neutral { --trend-color: #64748b; }

    @keyframes kpiArrowUp { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
    @keyframes kpiArrowDown { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(3px); } }
    @keyframes kpiPop { 0% { transform: scale(1); } 45% { transform: scale(1.08); } 100% { transform: scale(1); } }
    @keyframes kpiSuffixBounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-2px); } }

    .kpi-trend-up .kpi-trend-pill svg { animation: kpiArrowUp 1.6s ease-in-out infinite; }
    .kpi-trend-down .kpi-trend-pill svg { animation: kpiArrowDown 1.6s ease-in-out infinite; }
    .kpi-value .kpi-number.pop { animation: kpiPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .kpi-value .kpi-suffix { display: inline-block; animation: kpiSuffixBounce 2.2s ease-in-out infinite; }
    .kpi-value .kpi-suffix:empty { display: none; }

    @media (prefers-reduced-motion: reduce) {
        .kpi-trend-pill svg, .kpi-number.pop, .kpi-suffix { animation: none; }
    }
</style>

<script>
    (function () {
        if (window.__kpiInit) return;
        window.__kpiInit = true;

        function applyAccents() {
            document.querySelectorAll('.kpi-card').forEach(function (card) {
                var accent = card.dataset.accent;
                if (accent) {
                    card.style.setProperty('--accent', accent);
                    if (!card.dataset.chipBg) {
                        card.style.setProperty('--chip-bg', hexToRgba(accent, 0.10));
                    }
                    if (!card.dataset.chipColor) {
                        card.style.setProperty('--chip-color', accent);
                    }
                }
                if (card.dataset.chipBg) card.style.setProperty('--chip-bg', card.dataset.chipBg);
                if (card.dataset.chipColor) card.style.setProperty('--chip-color', card.dataset.chipColor);
            });
        }
        function hexToRgba(hex, alpha) {
            var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex || '');
            if (!m) return 'rgba(22, 163, 74, ' + alpha + ')';
            return 'rgba(' + parseInt(m[1], 16) + ', ' + parseInt(m[2], 16) + ', ' + parseInt(m[3], 16) + ', ' + alpha + ')';
        }
        applyAccents();
        document.addEventListener('DOMContentLoaded', applyAccents);

        function animateValue(el) {
            var target = parseFloat(el.getAttribute('data-count-target') || '');
            var decimals = parseInt(el.getAttribute('data-count-decimals') || '0', 10);
            var prefix = el.getAttribute('data-count-prefix') || '';
            var suffix = el.getAttribute('data-count-suffix') || '';
            if (isNaN(target)) return;

            var prefixEl = document.createElement('span');
            prefixEl.className = 'kpi-prefix';
            prefixEl.textContent = prefix;
            var numEl = document.createElement('span');
            numEl.className = 'kpi-number';
            var suffixEl = document.createElement('span');
            suffixEl.className = 'kpi-suffix';
            suffixEl.textContent = suffix;
            el.textContent = '';
            el.appendChild(prefixEl);
            el.appendChild(numEl);
            el.appendChild(suffixEl);

            function format(n) {
                return n.toLocaleString('en-PH', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }

            var start = null;
            var duration = 1000;
            function step(ts) {
                if (!start) start = ts;
                var progress = Math.min((ts - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                numEl.textContent = format(target * eased);
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    numEl.textContent = format(target);
                    numEl.classList.add('pop');
                    numEl.addEventListener('animationend', function handler() {
                        numEl.classList.remove('pop');
                        numEl.removeEventListener('animationend', handler);
                    });
                }
            }
            requestAnimationFrame(step);
        }

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        animateValue(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.3 });
            document.querySelectorAll('.kpi-value').forEach(function (el) { observer.observe(el); });
        } else {
            document.querySelectorAll('.kpi-value').forEach(function (el) { animateValue(el); });
        }
    })();
</script>
