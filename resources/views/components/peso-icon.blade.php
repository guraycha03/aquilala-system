{{-- Canonical Philippine-peso icon — the ONLY money icon in the system.

     Shape mirrors public/images/peso-yellow.png (stem, bowl, double crossbars)
     in the stroke style already used by the stat-card peso marks. Never draw a
     new currency glyph: reuse <x-peso-icon> on every cost / money card.

     Usage: <x-peso-icon />                       20px, currentColor, stroke 2
            <x-peso-icon :size="24" color="#16a34a" :stroke-width="1.5" /> --}}
@props(['size' => 20, 'color' => 'currentColor', 'strokeWidth' => 2])
<svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" stroke="{{ $color }}"
     stroke-width="{{ $strokeWidth }}" fill="none" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true">
    <line x1="8" y1="3" x2="8" y2="21"/>
    <path d="M8 3h6.5a4.5 4.5 0 0 1 0 9H8"/>
    <line x1="4.5" y1="6.5" x2="16" y2="6.5"/>
    <line x1="4.5" y1="9.5" x2="16" y2="9.5"/>
</svg>
