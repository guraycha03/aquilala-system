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
    // The count-up only runs when the value is a plain figure wearing
    // decoration it knows how to put back: "₱1,234", "403.71 MT", "87%".
    // Anything else — an event date ("Oct 25, 2026"), a title that happens
    // to contain a digit — is rendered exactly as passed. Stripping every
    // non-digit used to turn that date into a counted-up "252,026".
    $raw = trim((string) $value);
    $prefix = '';
    $suffix = '';
    $decimals = 0;
    $countTarget = null;

    if (preg_match('/^(₱?)\s*([0-9][0-9,]*(?:\.[0-9]+)?)\s*([A-Za-z%]{1,4})?$/u', $raw, $parts)) {
        $number = str_replace(',', '', $parts[2]);

        if (is_numeric($number)) {
            $prefix = $parts[1];
            $suffix = $parts[3] ?? '';
            $countTarget = (float) $number;
            $decimals = str_contains($parts[2], '.')
                ? strlen(strrchr($parts[2], '.')) - 1
                : 0;
        }
    }
@endphp

@if($link)
    <a href="{{ $link }}" class="kpi-card kpi-trend-{{ $trendDirection }}" data-accent="{{ $accent }}" @if($iconBg) data-chip-bg="{{ $iconBg }}" @endif @if($iconColor) data-chip-color="{{ $iconColor }}" @endif>
@else
    <div class="kpi-card kpi-trend-{{ $trendDirection }}" data-accent="{{ $accent }}" @if($iconBg) data-chip-bg="{{ $iconBg }}" @endif @if($iconColor) data-chip-color="{{ $iconColor }}" @endif>
@endif

    <div class="kpi-top">
        @isset($icon)
            <div class="kpi-icon-chip">{{ $icon }}</div>
        @endisset
        {{-- The farmer dashboard's tag slot: a short, calm pill. Colour
             carried by the wording ("reorder soon"), not by a red badge. --}}
        @if($trendValue !== null)
            <span class="kpi-trend-pill">{{ $trendValue }}</span>
        @endif
    </div>

    <div class="kpi-value" data-count-target="{{ $countTarget ?? '' }}" data-count-decimals="{{ $decimals }}" data-count-prefix="{{ $prefix }}" data-count-suffix="{{ $suffix }}">
        {{ $value }}
    </div>

    <h3 class="kpi-title">{{ $title }}</h3>

    @if($trendLabel)
        <p class="kpi-desc">{{ $trendLabel }}</p>
    @endif
@if($link)
    </a>
@else
    </div>
@endif

<style>
    /* The Farmer dashboard's KPI card, shared by every role's dashboard:
       flat white, sage icon tile, one calm tag, then the figure, the label
       and a one-line description. */
    .kpi-card {
        --accent: #16a34a;
        --chip-bg: #EDF1E9;
        --chip-color: #648268;
        display: flex;
        flex-direction: column;
        border-radius: 16px;
        border: 1px solid #E0E5DB;
        background: #fff;
        padding: 20px;
        box-sizing: border-box;
        color: inherit;
        text-decoration: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .kpi-card:hover {
        border-color: #C8D5C5;
        box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    }
    .kpi-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .kpi-title {
        margin: 4px 0 0;
        font-size: 14px;
        font-weight: 600;
        color: #344B38;
    }
    .kpi-icon-chip {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: var(--chip-bg);
        color: var(--chip-color);
    }
    .kpi-icon-chip svg { width: 20px; height: 20px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .kpi-icon-chip img { max-width: 22px; max-height: 22px; object-fit: contain; }
    .kpi-value {
        margin-top: 20px;
        font-size: 30px;
        font-weight: 600;
        color: #344B38;
        line-height: 1.1;
        letter-spacing: -0.025em;
        display: inline-flex;
        align-items: baseline;
    }
    .kpi-desc {
        margin: 4px 0 0;
        font-size: 12px;
        line-height: 1.35;
        color: #647263;
    }
    /* Same pill the farmer cards use. The wording carries the meaning
       ("reorder soon"), so no red badge competes with it. Wraps onto a
       second line instead of spilling outside a narrow card on phones. */
    .kpi-trend-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 999px;
        background: #F3F5F0;
        padding: 4px 10px;
        font-size: 12px;
        font-weight: 500;
        color: #71816F;
        max-width: 100%;
        min-width: 0;
        text-align: right;
        white-space: normal;
        overflow-wrap: break-word;
    }
    .kpi-trend-pill svg { width: 12px; height: 12px; stroke: currentColor; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 640px) {
        .kpi-value { margin-top: 12px; }
        .kpi-trend-pill { padding: 3px 8px; }
        /* Keep the card short on phones: the description never runs past
           two lines (the wording is short; this is the safety net). */
        .kpi-card .kpi-desc {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    }

    [data-theme="dark"] .kpi-card { background: #111827; border-color: #334155; }
    [data-theme="dark"] .kpi-card:hover { border-color: #4ade80; }
    [data-theme="dark"] .kpi-icon-chip { background: #16301f; color: #6ee7a0; }
    [data-theme="dark"] .kpi-trend-pill { background: #1f2937; color: #9fb3a6; }
    [data-theme="dark"] .kpi-value { color: #d6f5e3; }
    [data-theme="dark"] .kpi-title { color: #d6f5e3; }
    [data-theme="dark"] .kpi-desc { color: #9fb3a6; }

    @keyframes kpiPop { 0% { transform: scale(1); } 45% { transform: scale(1.08); } 100% { transform: scale(1); } }
    @keyframes kpiSuffixBounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-2px); } }

    .kpi-value .kpi-number.pop { animation: kpiPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .kpi-value .kpi-suffix { display: inline-block; animation: kpiSuffixBounce 2.2s ease-in-out infinite; }
    .kpi-value .kpi-suffix:empty { display: none; }

    @media (prefers-reduced-motion: reduce) {
        .kpi-number.pop, .kpi-suffix { animation: none; }
    }
</style>

<script>
    (function () {
        if (window.__kpiInit) return;
        window.__kpiInit = true;

        function applyAccents() {
            document.querySelectorAll('.kpi-card').forEach(function (card) {
                // The icon tile is sage on every dashboard. A card only tints
                // it when it asks for a colour explicitly, so one loud accent
                // can't make a dashboard drift from the shared look.
                if (card.dataset.accent) {
                    card.style.setProperty('--accent', card.dataset.accent);
                }
                if (card.dataset.chipBg) card.style.setProperty('--chip-bg', card.dataset.chipBg);
                if (card.dataset.chipColor) card.style.setProperty('--chip-color', card.dataset.chipColor);
            });
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
