@props([
    'allCrops' => [],
    'stats' => ['land_area' => 0, 'used_land_area' => 0, 'remaining_land' => 0],
    'user' => null,
    'allocPct' => 0,
    'unitLabel' => 'ha',
])

<!-- Load AlpineJS -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Load Flatpickr CSS & JS for fully custom datepicker popups -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<style>
    .ncb-overlay {
        position: fixed; top: 0; left: 260px; right: 0; bottom: 0;
        display: none; align-items: center; justify-content: center;
        padding: 0; overflow-y: auto; scrollbar-width: none; -ms-overflow-style: none;
        background: rgba(255, 255, 255, 0.42);
        z-index: 500;
    }
    .ncb-overlay::-webkit-scrollbar { display: none; width: 0; }
    .ncb-overlay.active { display: flex; }
    @media (max-width: 768px) { .ncb-overlay { left: 0; } }
    .ncb-modal {
        background: #fff; border-radius: 18px; width: 100%; max-width: 520px;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18), 0 30px 80px rgba(15, 41, 24, 0.32);
        margin: auto;
        max-height: calc(100vh - 120px);
        max-height: calc(100dvh - 120px);
    }
    @media (min-width: 640px) { .ncb-modal { max-width: 700px; } }
    @media (min-width: 1024px) { .ncb-modal { max-width: 880px; } }
    .ncb-header { padding: 20px 24px 16px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fff; border-radius: 18px 18px 0 0; }
    .ncb-header h3 { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; }
    .ncb-close { flex: 0 0 auto; width: 34px; height: 34px; border-radius: 10px; border: none; background: #f1f5f9; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; transition: all 0.15s ease; }
    .ncb-close:hover { background: #e2e8f0; color: #1e293b; }
    .ncb-body { padding: 20px 24px; background: #fff; overflow-y: auto; max-height: calc(100vh - 260px); max-height: calc(100dvh - 260px); scrollbar-width: none; -ms-overflow-style: none; }
    .ncb-body::-webkit-scrollbar { display: none; width: 0; }
    .ncb-footer { position: relative; z-index: 1; padding: 16px 24px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; background: #fff; border-radius: 0 0 18px 18px; }
    .ncb-section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #16a34a; margin: 16px 0 10px; padding-bottom: 6px; border-bottom: 1px solid #f1f5f9; }
    .ncb-section-label:first-child { margin-top: 0; }
    .ncb-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 12px; }
    .ncb-group { margin-bottom: 12px; position: relative; }
    .ncb-group label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }

    .ncb-group input, .ncb-group textarea {
        width: 100%; padding: 10px 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; color: #1e293b; background-color: #fff; transition: all 0.2s ease;
        box-sizing: border-box; font-family: inherit;
    }
    .ncb-group input:focus, .ncb-group textarea:focus { outline: none; border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,0.08); }
    .ncb-group textarea { resize: vertical; min-height: 64px; }
    .ncb-group input.over-cap { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.08); }
    .ncb-group input.warn-cap { border-color: #d97706; box-shadow: 0 0 0 3px rgba(217,119,6,0.08); }

    /* Custom Select Trigger Rules */
    .ncb-select-trigger {
        width: 100%; min-height: 41px; padding: 10px 13px; border: 1.5px solid #e2e8f0; border-radius: 10px;
        font-size: 13px; color: #1e293b; background-color: #fff; transition: all 0.2s ease;
        box-sizing: border-box; font-family: inherit; cursor: pointer;
        display: flex; justify-content: space-between; align-items: center; user-select: none;
    }
    .ncb-select-trigger.placeholder { color: #94a3b8; }
    .ncb-select-trigger:hover { border-color: #cbd5e1; }
    .ncb-select-trigger.active { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,0.08); outline: none; }
    .ncb-select-arrow { transition: transform 0.2s ease; color: #64748b; flex-shrink: 0; }
    .ncb-select-arrow.rotate { transform: rotate(180deg); }

    .ncb-select-menu {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 10px rgba(0,0,0,0.05);
        max-height: 200px; overflow-y: auto; z-index: 600; padding: 4px;
        scrollbar-width: thin;
    }
    .ncb-select-option {
        padding: 8px 12px; font-size: 13px; color: #1e293b; border-radius: 6px;
        cursor: pointer; transition: background 0.15s ease, color 0.15s ease;
    }
    .ncb-select-option:hover { background: #f1f5f9; color: #16a34a; }
    .ncb-select-option.selected { background: #f0fdf4; color: #16a34a; font-weight: 600; }

    /* ── CUSTOM FLATPICKR DATEPICKER POPUP STYLES ── */
    /* ── CUSTOM FLATPICKR DATEPICKER POPUP STYLES ── */
.ncb-date-wrapper { position: relative; width: 100%; }
.ncb-date-icon { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); color: #16a34a; pointer-events: none; }

.flatpickr-calendar {
    background: #ffffff !important;
    border-radius: 14px !important;
    border: 1.5px solid #e2e8f0 !important;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12) !important;
    font-family: inherit !important;
    padding: 12px 14px !important;
    width: 315px !important;
    box-sizing: border-box !important;
    z-index: 9999 !important;
}
.flatpickr-calendar.arrowTop:before, .flatpickr-calendar.arrowTop:after { display: none !important; }

/* Keep flatpickr's native grid alignment intact */
.flatpickr-innerContainer, .flatpickr-rContainer {
    width: 100% !important;
}
.flatpickr-days {
    width: 100% !important;
}
.dayContainer {
    width: 100% !important;
    min-width: 100% !important;
    max-width: 100% !important;
}

/* Header & Navigation Styling */
.flatpickr-months {
    display: flex !important;
    align-items: center !important;
    margin-bottom: 8px !important;
}
.flatpickr-months .flatpickr-month {
    background: #ffffff !important;
    color: #1e293b !important;
    fill: #1e293b !important;
    height: 34px !important;
}
.flatpickr-current-month {
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 700 !important; color: #1e293b !important; }
.flatpickr-current-month input.cur-year { font-weight: 700 !important; color: #1e293b !important; }
.flatpickr-months .flatpickr-prev-month:hover svg, .flatpickr-months .flatpickr-next-month:hover svg { fill: #16a34a !important; }

/* Weekdays & Days spacing */
span.flatpickr-weekday {
    color: #64748b !important;
    font-weight: 700 !important;
    font-size: 11px !important;
}
.flatpickr-day {
    border-radius: 8px !important;
    color: #334155 !important;
    font-weight: 500 !important;
    height: 36px !important;
    line-height: 36px !important;
    margin-top: 2px !important;
}
.flatpickr-day:hover { background: #f1f5f9 !important; border-color: transparent !important; color: #16a34a !important; }
.flatpickr-day.selected, .flatpickr-day.selected:hover { background: #16a34a !important; border-color: #16a34a !important; color: #ffffff !important; font-weight: 700 !important; }
.flatpickr-day.today { border-color: #16a34a !important; font-weight: 700 !important; color: #16a34a !important; }
.flatpickr-day.today.selected { color: #ffffff !important; }


    .ncb-btn-cancel { appearance: none; -webkit-appearance: none; padding: 10px 18px; font-size: 13px; font-weight: 600; border-radius: 10px; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer; transition: all 0.15s ease; }
    .ncb-btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
    .ncb-btn-submit { appearance: none; -webkit-appearance: none; padding: 10px 22px; font-size: 13px; font-weight: 700; border-radius: 10px; border: none; background: #16a34a; color: #fff; cursor: pointer; box-shadow: 0 4px 12px rgba(22,163,74,0.25); transition: all 0.2s ease; }
    .ncb-btn-submit:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(22,163,74,0.3); }

    .ncb-cap-wrap { margin-bottom: 14px; }
    .ncb-cap-bar-track { width: 100%; height: 10px; background: #e5e7eb; border-radius: 5px; overflow: hidden; position: relative; }
    .ncb-cap-bar-fill { height: 100%; border-radius: 5px; transition: width 0.4s ease, background 0.3s ease; }
    .ncb-cap-bar-fill.ok { background: linear-gradient(90deg, #16a34a, #22c55e); }
    .ncb-cap-bar-fill.warn { background: linear-gradient(90deg, #d97706, #f59e0b); }
    .ncb-cap-bar-fill.over { background: linear-gradient(90deg, #dc2626, #ef4444); }
    .ncb-cap-text { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; font-size: 11px; color: #64748b; font-weight: 600; }
    .ncb-cap-remaining { color: #16a34a; font-weight: 700; }
    .ncb-cap-remaining.warn { color: #d97706; }
    .ncb-cap-remaining.over { color: #dc2626; }
    .ncb-cap-warning { display: none; margin-top: 8px; margin-bottom: 14px; padding: 10px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; line-height: 1.4; }
    .ncb-cap-warning.warn { display: flex; align-items: center; gap: 6px; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .ncb-cap-warning.over { display: flex; align-items: center; gap: 6px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>

<!-- ── NEW CROP BATCH MODAL ── -->
<div class="ncb-overlay" id="addModal">
    <div class="ncb-modal">
        <div class="ncb-header">
            <h3>New Crop Batch</h3>
            <button type="button" class="ncb-close" onclick="closeModal('addModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="{{ route('farmer.crops.store') }}" method="POST">
            @csrf
            <div class="ncb-body">
                <div class="ncb-section-label">Crop Information</div>
                <div class="ncb-row">
                    
                    <!-- Crop Type Select -->
                    <div class="ncb-group" x-data="{ open: false, value: '', label: 'Select a crop' }">
                        <label>Crop Type *</label>
                        <input type="hidden" name="crop_id" :value="value" required>
                        <div class="ncb-select-trigger" :class="{ 'active': open, 'placeholder': !value }" @click="open = !open" @click.outside="open = false" tabindex="0">
                            <span x-text="label">Select a crop</span>
                            <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <div class="ncb-select-menu" x-show="open" x-cloak style="display: none;">
                            @foreach($allCrops as $crop)
                                <div class="ncb-select-option" :class="{ 'selected': value === '{{ $crop->id }}' }"
                                     @click="value = '{{ $crop->id }}'; label = '{{ $crop->name }}'; open = false">
                                    {{ $crop->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="ncb-group">
                        <label>Batch Name</label>
                        <input type="text" name="batch_name" placeholder="e.g. North Field A">
                    </div>
                </div>

                <div class="ncb-section-label">Land & Season</div>
                @if(($stats['land_area'] ?? 0) > 0)
                <div class="ncb-cap-wrap">
                    <div class="ncb-cap-bar-track">
                        <div class="ncb-cap-bar-fill ok" id="capBarFill" style="width: {{ $allocPct }}%"></div>
                    </div>
                    <div class="ncb-cap-text">
                        <span>{{ number_format($stats['used_land_area'] ?? 0, 1) }} {{ $unitLabel }} used</span>
                        <span class="ncb-cap-remaining" id="capRemaining">{{ number_format($stats['remaining_land'] ?? 0, 1) }} {{ $unitLabel }} remaining</span>
                    </div>
                </div>
                <div class="ncb-cap-warning" id="capWarning"></div>
                @endif
                <div class="ncb-row">
                    <div class="ncb-group">
                        <label>Land Area *</label>
                        <input type="number" name="land_area" id="addLandArea" step="0.01" min="0.01" value="{{ number_format($stats['remaining_land'] ?? 0, 2) }}" required oninput="checkLandCapacity(this.value)">
                    </div>

                    <!-- Area Unit Select -->
                    @php 
                        $defaultUnit = $user->default_land_area_unit ?? 'ha';
                        $unitLabels = ['ha' => 'Hectares (ha)', 'sq_m' => 'Square Meters (m²)', 'sq_ft' => 'Square Feet (ft²)'];
                    @endphp
                    <div class="ncb-group" x-data="{ open: false, value: '{{ $defaultUnit }}', label: '{{ $unitLabels[$defaultUnit] ?? 'Hectares (ha)' }}' }">
                        <label>Area Unit *</label>
                        <input type="hidden" name="land_area_unit" id="landAreaUnit" :value="value" required>
                        <div class="ncb-select-trigger" :class="{ 'active': open }" @click="open = !open" @click.outside="open = false" tabindex="0">
                            <span x-text="label">{{ $unitLabels[$defaultUnit] ?? 'Hectares (ha)' }}</span>
                            <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <div class="ncb-select-menu" x-show="open" x-cloak style="display: none;">
                            <div class="ncb-select-option" :class="{ 'selected': value === 'ha' }" @click="value = 'ha'; label = 'Hectares (ha)'; open = false; checkLandCapacity(document.getElementById('addLandArea').value)">Hectares (ha)</div>
                            <div class="ncb-select-option" :class="{ 'selected': value === 'sq_m' }" @click="value = 'sq_m'; label = 'Square Meters (m²)'; open = false; checkLandCapacity(document.getElementById('addLandArea').value)">Square Meters (m²)</div>
                            <div class="ncb-select-option" :class="{ 'selected': value === 'sq_ft' }" @click="value = 'sq_ft'; label = 'Square Feet (ft²)'; open = false; checkLandCapacity(document.getElementById('addLandArea').value)">Square Feet (ft²)</div>
                        </div>
                    </div>
                </div>

                <div class="ncb-row">
                    <!-- Season Select -->
                    <div class="ncb-group" x-data="{ open: false, value: 'wet', label: 'Wet Season (Jun–Oct)' }">
                        <label>Season *</label>
                        <input type="hidden" name="season" :value="value" required>
                        <div class="ncb-select-trigger" :class="{ 'active': open }" @click="open = !open" @click.outside="open = false" tabindex="0">
                            <span x-text="label">Wet Season (Jun–Oct)</span>
                            <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <div class="ncb-select-menu" x-show="open" x-cloak style="display: none;">
                            <div class="ncb-select-option" :class="{ 'selected': value === 'wet' }" @click="value = 'wet'; label = 'Wet Season (Jun–Oct)'; open = false">Wet Season (Jun–Oct)</div>
                            <div class="ncb-select-option" :class="{ 'selected': value === 'dry' }" @click="value = 'dry'; label = 'Dry Season (Nov–May)'; open = false">Dry Season (Nov–May)</div>
                        </div>
                    </div>

                    <!-- Custom Styled Planting Date -->
                    <div class="ncb-group">
                        <label>Planting Date *</label>
                        <div class="ncb-date-wrapper">
                            <input type="text" class="custom-datepicker" name="planting_date" value="{{ now()->format('Y-m-d') }}" required placeholder="Select date">
                            <svg class="ncb-date-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="ncb-section-label">Planning (Optional)</div>
                <div class="ncb-row">
                    <!-- Custom Styled Expected Harvest Date -->
                    <div class="ncb-group">
                        <label>Expected Harvest Date</label>
                        <div class="ncb-date-wrapper">
                            <input type="text" class="custom-datepicker" name="expected_harvest_date" placeholder="Select date">
                            <svg class="ncb-date-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="ncb-group">
                        <label>Estimated Cost (₱)</label>
                        <input type="number" name="estimated_cost" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
                <div class="ncb-row">
                    <div class="ncb-group">
                        <label>Estimated Yield</label>
                        <input type="number" name="estimated_yield" step="0.01" min="0" placeholder="0">
                    </div>
                    <div class="ncb-group">
                        <label>Yield Unit</label>
                        <input type="text" name="yield_unit" placeholder="e.g. sacks, kg, tons">
                    </div>
                </div>
                <div class="ncb-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any additional notes about this crop batch..."></textarea>
                </div>
            </div>
            <div class="ncb-footer">
                <button type="button" class="ncb-btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="ncb-btn-submit">Add Crop Batch</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof flatpickr !== "undefined") {
            flatpickr(".custom-datepicker", {
                dateFormat: "Y-m-d",
                disableMobile: "true"
            });
        }
    });

    var _capTotal = parseFloat("{{ $stats['land_area'] ?? 0 }}") || 0;
    var _capUsed = parseFloat("{{ $stats['used_land_area'] ?? 0 }}") || 0;
    var _capUnit = {!! json_encode($unitLabel ?? 'ha') !!};

    function checkLandCapacity(val) {
        const input = document.getElementById('addLandArea');
        const bar = document.getElementById('capBarFill');
        const rem = document.getElementById('capRemaining');
        const warn = document.getElementById('capWarning');
        if (!input || !bar || _capTotal <= 0) return;
        const entered = parseFloat(val) || 0;
        const newUsed = Math.round((_capUsed + entered) * 100) / 100;
        const newPct = Math.min(100, Math.round((newUsed / _capTotal) * 100));
        const newRem = Math.round(Math.max(0, _capTotal - newUsed) * 100) / 100;
        bar.style.width = newPct + '%';
        input.classList.remove('over-cap', 'warn-cap');
        bar.classList.remove('ok', 'warn', 'over');
        rem.classList.remove('warn', 'over');
        warn.className = 'ncb-cap-warning';
        if (newUsed > _capTotal) {
            bar.classList.add('over');
            input.classList.add('over-cap');
            rem.classList.add('over');
            rem.textContent = 'Exceeded by ' + Math.abs(_capTotal - newUsed).toFixed(2) + ' ' + _capUnit;
            warn.className = 'ncb-cap-warning over';
            warn.innerHTML = '<i data-lucide="triangle-alert" width="14" height="14" style="flex-shrink:0"></i><span>Land area exceeds your total farm size! You cannot plant more than ' + _capTotal + ' ' + _capUnit + '.</span>';
            if (window.lucide) lucide.createIcons();
        } else if (newPct >= 80) {
            bar.classList.add('warn');
            input.classList.add('warn-cap');
            rem.classList.add('warn');
            rem.textContent = newRem.toFixed(1) + ' ' + _capUnit + ' remaining';
            warn.className = 'ncb-cap-warning warn';
            warn.innerHTML = '<i data-lucide="triangle-alert" width="14" height="14" style="flex-shrink:0"></i><span>Running low on land — only ' + newRem.toFixed(1) + ' ' + _capUnit + ' will remain.</span>';
            if (window.lucide) lucide.createIcons();
        } else {
            bar.classList.add('ok');
            rem.textContent = newRem.toFixed(1) + ' ' + _capUnit + ' remaining';
        }
    }
</script>