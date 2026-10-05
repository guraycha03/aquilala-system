@props([
    'allCrops' => [],
    'crops' => [],
    'stats' => ['land_area' => 0, 'used_land_area' => 0, 'remaining_land' => 0],
    'user' => null,
    'allocPct' => 0,
    'unitLabel' => 'ha',
    'cropDefaults' => [],
    'seedProducts' => [],
    'productYields' => [],
    'productCosts' => [],
    'cropProductSlugs' => [],
    'cropProductKeywords' => [],
    'cropVarieties' => [],
    'cropInfo' => [],
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
    /* The shared sidebar layout sets a global `.ncb-select-menu { display: none }` for the
       table-toolbar filters. That rule would keep these Alpine `x-show` menus hidden even when
       Alpine removes the inline `display` on open, so force the modal's menus to render as block.
       `.ncb-group` is only used inside this modal, so the table-toolbar (`.ncb-group-inline`) is unaffected. */
    .ncb-group > .ncb-select-menu { display: block; }
    .ncb-select-option {
        padding: 8px 12px; font-size: 13px; color: #1e293b; border-radius: 6px;
        cursor: pointer; transition: background 0.15s ease, color 0.15s ease;
    }
    .ncb-select-option:hover { background: #f1f5f9; color: #16a34a; }
    .ncb-select-option.selected { background: #f0fdf4; color: #16a34a; font-weight: 600; }

    /* Yield Unit search-as-you-type combobox */
    .ncb-unit-wrap { position: relative; }
    .ncb-unit-wrap input { padding-right: 34px; }
    .ncb-unit-arrow {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        color: #64748b; cursor: pointer; transition: transform 0.2s ease; pointer-events: none;
    }
    .ncb-unit-menu {
        position: absolute; top: calc(100% + 4px); left: 0; right: 0;
        background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 4px 10px rgba(0,0,0,0.05);
        max-height: 200px; overflow-y: auto; z-index: 600; padding: 4px;
        display: none; scrollbar-width: thin;
    }
    .ncb-unit-option {
        padding: 8px 12px; font-size: 13px; color: #1e293b; border-radius: 6px;
        cursor: pointer; transition: background 0.15s ease, color 0.15s ease;
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
    }
    .ncb-unit-option:hover { background: #f1f5f9; color: #16a34a; }
    .ncb-unit-option .ncb-unit-alias { font-size: 10.5px; color: #94a3b8; font-weight: 600; }
    .ncb-unit-empty { padding: 10px 12px; font-size: 12.5px; color: #94a3b8; font-style: italic; }

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

    .ncb-yield-hint { display: block; margin-top: 8px; margin-bottom: 8px; font-size: 13px; line-height: 1.55; color: #16a34a; font-weight: 600; min-height: 16px; }
    .ncb-yield-hint:empty { min-height: 0; }
    .ncb-note { display: block; margin-top: 8px; margin-bottom: 8px; font-size: 12.5px; color: #64748b; line-height: 1.5; }

    .ncb-cost-card {
        display: flex; align-items: flex-start; gap: 9px;
        margin-top: 12px; margin-bottom: 12px;
        padding: 11px 13px;
        background: #f0fdf4; border: 1px solid #bbe7c6; border-radius: 10px;
        font-size: 13px; line-height: 1.55; color: #166534; font-weight: 600;
        box-sizing: border-box;
    }
    .ncb-cost-card .ncb-cost-icon { flex-shrink: 0; color: #0284c7; margin-top: 2px; }
    .ncb-cost-card .ncb-cost-text { flex: 1; min-width: 0; }
    .ncb-source-link { display: flex; align-items: center; gap: 6px; width: fit-content; max-width: 100%; margin-top: 8px; color: inherit; font-weight: 700; text-decoration: none; border-bottom: 1px dotted currentColor; }
    .ncb-source-link:hover { text-decoration: underline; }
    .ncb-source-link svg { flex-shrink: 0; }
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
                                     @click="value = '{{ $crop->id }}'; label = '{{ $crop->name }}'; open = false; window.pickCrop({{ $crop->id }})">
                                    {{ $crop->name }}
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="ncb-group">
                        <label>Batch Name</label>
                        <input type="text" name="batch_name" id="batchName" placeholder="e.g. North Field A" autocomplete="off">
                        <span class="ncb-note" id="batchNameHint" style="margin-bottom: 0;">Auto-generated from Crop + Field + Planting Date, e.g. <b>TOMATO-FIELD1-160926</b>. You can edit it.</span>
                    </div>
                </div>

                <!-- Variety step (required; defaults to Local / General variety) -->
                <div class="ncb-group" id="varietyGroup">
                    <label>Variety *</label>
                    <input type="hidden" name="variety" id="varietyValue" value="{{ \App\Services\CropLibraryService::GENERAL_VARIETY }}" required>
                    <input type="hidden" name="variety_key" id="varietyKeyValue" value="{{ \App\Services\CropLibraryService::GENERAL_VARIETY_KEY }}">
                    <div class="ncb-select-trigger placeholder" id="varietyTrigger" tabindex="0">
                        <span id="varietyLabel">{{ \App\Services\CropLibraryService::GENERAL_VARIETY }}</span>
                        <svg class="ncb-select-arrow" id="varietyArrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="ncb-select-menu" id="varietyMenu" style="display: none;"></div>
                    <span class="ncb-note" id="varietyNote">Choose a crop first. <b>{{ \App\Services\CropLibraryService::GENERAL_VARIETY }}</b> is preselected — keep it when you are using local or free-distributed planting material.</span>
                </div>

                <!-- Crop agronomy info panel (fills once a crop is picked) -->
                <div class="ncb-cost-card" id="cropInfoCard" style="display: none; background: #eff6ff; border-color: #bfdbfe; color: #1e3a8a;">
                    <img class="ncb-cost-icon" src="{{ asset('images/information.png') }}" alt="info" width="16" height="16">
                    <span class="ncb-cost-text" id="cropInfoText"></span>
                </div>

                <!-- Optional Field: Specific Product -->
                <div class="ncb-group" x-data="{ open: false, selProd: '', label: 'None — crop type only (e.g. free seeds distributed)' }">
                    <label>Specific Product (Optional)</label>
                    <input type="hidden" name="product_id" id="cropProductId" :value="selProd">
                    <div class="ncb-select-trigger" :class="{ 'active': open, 'placeholder': !selProd }" @click="open = !open" @click.outside="open = false" tabindex="0">
                        <span x-text="label">None — crop type only (e.g. free seeds distributed)</span>
                        <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                    <div class="ncb-select-menu" id="prodList" x-show="open" x-cloak style="display: none;">
                        <div class="ncb-select-option" id="prodNone" :class="{ 'selected': selProd === '' }" 
                             @click="selProd = ''; label = 'None — crop type only (e.g. free seeds distributed)'; open = false; window.pickProduct(0)">None — crop type only (e.g. free seeds distributed)</div>
                        @forelse($seedProducts as $product)
                            <div class="ncb-select-option" data-pid="{{ $product['id'] }}" data-name="{{ mb_strtolower($product['name']) }}" data-catslug="{{ $product['category_slug'] }}" data-cropids="{{ implode(',', $product['crop_ids']) }}"
                                 :class="{ 'selected': selProd === '{{ $product['id'] }}' }"
                                 @click="selProd = '{{ $product['id'] }}'; label = '{{ addslashes($product['display_name']) }}'; open = false; window.pickProduct({{ $product['id'] }})">
                                {{ $product['display_name'] }}
                            </div>
                        @empty
                            <div class="ncb-select-option" style="color: #94a3b8; cursor: default;">No seed/planting products available in the stores yet.</div>
                        @endforelse
                        <div class="ncb-select-option" id="prodNoMatch" style="color: #94a3b8; cursor: default; display: none;">No applicable store products for this crop yet — leave None or pick another crop.</div>
                    </div>
                    <span class="ncb-note">Optional — picks only show products applicable to the selected crop. Right now the list is filtered as you choose a crop type.</span>
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
                        <input type="number" name="land_area" id="addLandArea" step="0.01" min="0.01" value="{{ number_format($stats['remaining_land'] ?? 0, 2) }}" required oninput="checkLandCapacity(this.value); window.recalcYield()">
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
                            <div class="ncb-select-option" :class="{ 'selected': value === 'ha' }" @click="value = 'ha'; label = 'Hectares (ha)'; open = false; checkLandCapacity(document.getElementById('addLandArea').value); window.recalcYield()">Hectares (ha)</div>
                            <div class="ncb-select-option" :class="{ 'selected': value === 'sq_m' }" @click="value = 'sq_m'; label = 'Square Meters (m²)'; open = false; checkLandCapacity(document.getElementById('addLandArea').value); window.recalcYield()">Square Meters (m²)</div>
                            <div class="ncb-select-option" :class="{ 'selected': value === 'sq_ft' }" @click="value = 'sq_ft'; label = 'Square Feet (ft²)'; open = false; checkLandCapacity(document.getElementById('addLandArea').value); window.recalcYield()">Square Feet (ft²)</div>
                        </div>
                    </div>
                </div>

                <div class="ncb-row">
                    <!-- Season Select -->
                    <div class="ncb-group" x-data="{ open: false, value: 'wet', label: 'Wet Season (Jun–Jan)' }">
                        <label>Season *</label>
                        <input type="hidden" name="season" :value="value" required>
                        <div class="ncb-select-trigger" :class="{ 'active': open }" @click="open = !open" @click.outside="open = false" tabindex="0">
                            <span x-text="label">Wet Season (Jun–Jan)</span>
                            <svg class="ncb-select-arrow" :class="{ 'rotate': open }" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                        <div class="ncb-select-menu" x-show="open" x-cloak style="display: none;">
                            <div class="ncb-select-option" :class="{ 'selected': value === 'wet' }" @click="value = 'wet'; label = 'Wet Season (Jun–Jan)'; open = false">Wet Season (Jun–Jan)</div>
                            <div class="ncb-select-option" :class="{ 'selected': value === 'dry' }" @click="value = 'dry'; label = 'Dry Season (Feb–May)'; open = false">Dry Season (Feb–May)</div>
                        </div>
                    </div>

                    <!-- Custom Styled Planting Date -->
                    <div class="ncb-group">
                        <label>Planting Date *</label>
                        <div class="ncb-date-wrapper">
                            <input type="text" class="custom-datepicker" id="plantingDate" name="planting_date" value="{{ now()->format('Y-m-d') }}" required placeholder="Select date">
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
                            <input type="text" class="custom-datepicker" id="estHarvest" name="expected_harvest_date" placeholder="Select date">
                            <svg class="ncb-date-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <span class="ncb-yield-hint" id="harvestHint"></span>
                    </div>
                    <div class="ncb-group">
                        <label>Estimated Cost (₱)</label>
                        <input type="number" name="estimated_cost" id="estCost" step="0.01" min="0" placeholder="Auto-fills when a product is selected">
                    </div>
                </div>
                <div class="ncb-cost-card">
                    <img class="ncb-cost-icon" src="{{ asset('images/information.png') }}" alt="info" width="16" height="16">
                    <span class="ncb-cost-text" id="costHint"></span>
                </div>
                <div class="ncb-row">
                    <div class="ncb-group">
                        <label>Estimated Yield</label>
                        <input type="number" name="estimated_yield" id="estYield" step="0.01" min="0" placeholder="Auto-fills when a crop is selected">
                        <span class="ncb-yield-hint" id="yieldHint"></span>
                    </div>
                    <div class="ncb-group">
                        <label>Yield Unit <span style="font-weight:500;color:#94a3b8;">(search as you type)</span></label>
                        <div class="ncb-unit-wrap">
                            <input type="text" name="yield_unit" id="estUnit" placeholder="e.g. kg, tons, sacks, nuts" autocomplete="off" spellcheck="false">
                            <svg class="ncb-unit-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <div class="ncb-unit-menu" id="unitMenu"></div>
                        </div>
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

    /* ── Yield auto-estimation ── */
    var _CROP_DEFAULTS = @json($cropDefaults);
    var _PRODUCT_YIELDS = @json($productYields);
    var _PRODUCT_COSTS = @json($productCosts);
    var _CROP_PRODUCT_SLUGS = @json($cropProductSlugs);
    var _CROP_PRODUCT_KEYWORDS = @json($cropProductKeywords);
    var _CROP_VARIETIES = @json($cropVarieties);
    var _CROP_INFO = @json($cropInfo);
    var _GENERAL_VARIETY = @json(\App\Services\CropLibraryService::GENERAL_VARIETY);
    var _GENERAL_VARIETY_KEY = @json(\App\Services\CropLibraryService::GENERAL_VARIETY_KEY);
    var _PICKED_CROP = '';
    var _PICKED_PRODUCT = '';
    var _PICKED_VARIETY_KEY = _GENERAL_VARIETY_KEY;
    var _harvestAuto = false;

    /* ── Variety step: options come from the crop library per crop ── */
    var _varietyMenu = document.getElementById('varietyMenu');
    var _varietyTrigger = document.getElementById('varietyTrigger');
    var _varietyValue = document.getElementById('varietyValue');
    var _varietyKeyValue = document.getElementById('varietyKeyValue');
    var _varietyLabel = document.getElementById('varietyLabel');
    var _varietyNote = document.getElementById('varietyNote');
    var _varietyArrow = document.getElementById('varietyArrow');
    var _varietyOptions = [];

    function closeVarietyMenu() {
        if (!_varietyMenu) return;
        _varietyMenu.style.display = 'none';
        _varietyTrigger && _varietyTrigger.classList.remove('active');
        _varietyArrow && _varietyArrow.classList.remove('rotate');
    }

    function selectVariety(opt) {
        if (!opt) return;
        _PICKED_VARIETY_KEY = opt.key;
        if (_varietyValue) _varietyValue.value = opt.name;
        if (_varietyKeyValue) _varietyKeyValue.value = opt.key;
        if (_varietyLabel) {
            _varietyLabel.textContent = opt.name;
            _varietyTrigger.classList.toggle('placeholder', opt.key === _GENERAL_VARIETY_KEY);
        }
        if (_varietyNote) {
            _varietyNote.textContent = opt.note || 'Preselected default — keep it when you are using local or free-distributed planting material.';
        }
        if (_varietyMenu) {
            Array.prototype.forEach.call(_varietyMenu.querySelectorAll('.ncb-select-option'), function (el) {
                el.classList.toggle('selected', el.getAttribute('data-key') === opt.key);
            });
        }
        window.recalcYield && window.recalcYield();
        window.recalcHarvest && window.recalcHarvest();
        showCropInfo(_PICKED_CROP);
    }

    function fillVarietyOptions(cropId) {
        if (!_varietyMenu) return;
        _varietyOptions = _CROP_VARIETIES[cropId] || [{ key: _GENERAL_VARIETY_KEY, name: _GENERAL_VARIETY, note: null }];
        _varietyMenu.innerHTML = '';
        _varietyOptions.forEach(function (opt) {
            var div = document.createElement('div');
            div.className = 'ncb-select-option';
            div.setAttribute('data-key', opt.key);
            var name = document.createElement('span');
            name.textContent = opt.name;
            div.appendChild(name);
            if (opt.note) {
                var note = document.createElement('div');
                note.style.cssText = 'font-size:11px;color:#94a3b8;line-height:1.4;margin-top:2px;';
                note.textContent = opt.note;
                div.appendChild(note);
            }
            div.addEventListener('click', function () {
                selectVariety(opt);
                closeVarietyMenu();
            });
            _varietyMenu.appendChild(div);
        });
        /* Always default to Local / General variety. */
        selectVariety(_varietyOptions[0]);
        _PICKED_VARIETY_KEY = _varietyOptions[0] ? _varietyOptions[0].key : _GENERAL_VARIETY_KEY;
    }

    if (_varietyTrigger) {
        _varietyTrigger.addEventListener('click', function () {
            var open = _varietyMenu.style.display !== 'block';
            _varietyMenu.style.display = open ? 'block' : 'none';
            _varietyTrigger.classList.toggle('active', open);
            _varietyArrow.classList.toggle('rotate', open);
        });
        document.addEventListener('click', function (e) {
            var group = document.getElementById('varietyGroup');
            if (group && !group.contains(e.target)) closeVarietyMenu();
        });
    }

    /* The picked variety, or null for the general default. */
    function _pickedVariety() {
        if (!_PICKED_CROP || !_PICKED_VARIETY_KEY || _PICKED_VARIETY_KEY === _GENERAL_VARIETY_KEY) return null;
        var list = _CROP_VARIETIES[_PICKED_CROP] || [];
        for (var i = 0; i < list.length; i++) {
            if (list[i].key === _PICKED_VARIETY_KEY) return list[i];
        }
        return null;
    }

    /* ── Crop agronomy info panel (breed-specific when a variety is picked) ── */
    function _esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    /* Citation line: link icon + "Source: …", on its own line, opening the
       official reference in a new tab. Text and URL are escaped. */
    function _sourceLinkHtml(text, url) {
        if (!text) return '';
        var icon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><path d="M8 12h8"/></svg>';
        if (!url) return '<span class="ncb-source-link">' + icon + 'Source: ' + _esc(text) + '</span>';
        return '<a class="ncb-source-link" href="' + _esc(url) + '" target="_blank" rel="noopener noreferrer">' +
            icon + 'Source: ' + _esc(text) + '</a>';
    }
    function showCropInfo(cropId) {
        var card = document.getElementById('cropInfoCard');
        var text = document.getElementById('cropInfoText');
        if (!card || !text) return;
        var info = _CROP_INFO[cropId];
        if (!info) { card.style.display = 'none'; text.textContent = ''; return; }
        var parts = [];

        /* Breed-specific line: the picked variety's own maturity, yield and note. */
        var variety = (String(cropId) === String(_PICKED_CROP)) ? _pickedVariety() : null;
        if (variety && (variety.maturity_days || variety.yield_kg_ha)) {
            var bits = [];
            if (variety.maturity_days) bits.push(variety.maturity_days + ' days from planting to harvest');
            if (variety.yield_kg_ha) bits.push('about ' + Number(variety.yield_kg_ha).toLocaleString() + ' kg/ha');
            var line = variety.name + ' (' + info.name + '): ' + bits.join(', ');
            if (variety.note) line += ' — ' + variety.note;
            parts.push(line);
        }

        if (info.days_to_harvest) parts.push('Crop window: ' + info.days_to_harvest);
        if (info.spacing) parts.push('Spacing: ' + info.spacing);
        if (info.planting_window) parts.push('Planting: ' + info.planting_window);
        if (info.yield_per_ha) parts.push('Typical yield: ' + Number(info.yield_per_ha).toLocaleString() + ' ' + (info.yield_unit || 'kg') + '/ha');
        text.innerHTML = (parts.length ? _esc(parts.join('  ·  ')) : '') +
            (info.source ? _sourceLinkHtml(info.source, info.source_url) : '');
        card.style.display = 'flex';
    }

    /* ── Auto batch name: {CROP}-FIELD{N}-{DDMMYY} (e.g. TOMATO-FIELD1-160926) ── */
    var _BATCH_COUNTS = @json(collect($crops)->reject(fn ($c) => ! $c->crop)->groupBy(fn ($c) => $c->crop->name)->map->count() ?? []);
    var _batchNameEl = document.getElementById('batchName');
    var _batchAuto = true;
    function _batchSlug(name) { return (name || '').replace(/[^a-zA-Z0-9]+/g, '').toUpperCase(); }
    function _batchDmy(ymd) {
        var p = (ymd || '').split('-');
        return p.length === 3 ? ((p[2] || '') + (p[1] || '') + (p[0] || '').slice(-2)) : '';
    }
    window.autoBatchName = function () {
        if (!_batchNameEl || !_batchAuto) return;
        var crop = _CROP_DEFAULTS[_PICKED_CROP];
        if (!crop || !crop.name) return;
        var plantEl = document.getElementById('plantingDate');
        var d = _batchDmy(plantEl ? plantEl.value : '');
        if (!d) return;
        var field = (_BATCH_COUNTS[crop.name] || 0) + 1;
        _batchNameEl.value = _batchSlug(crop.name) + '-FIELD' + field + '-' + d;
    };
    if (_batchNameEl) {
        _batchNameEl.addEventListener('input', function () {
            if (!_batchNameEl.value.trim()) { _batchAuto = true; window.autoBatchName(); }
            else { _batchAuto = false; }
        });
    }

    /* Mass units convertible to kilograms (used when the user switches the
       Yield Unit — the estimate is re-converted so the amount stays equal). */
    var _KG_PER_UNIT = { kg: 1, tons: 1000, grams: 0.001, quintal: 100, sacks: 50, cavans: 50 };
    var _lastKgEst = null;

    /* ── Expected Harvest Date auto-estimation ── */
    var _HARVEST_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    function _harvestFmt(d) { return _HARVEST_MONTHS[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear(); }
    function _harvestYmd(d) { return d.getFullYear() + '-' + (d.getMonth() + 1 < 10 ? '0' : '') + (d.getMonth() + 1) + '-' + (d.getDate() < 10 ? '0' : '') + d.getDate(); }

    window.recalcHarvest = function () {
        var el = document.getElementById('estHarvest');
        var hintEl = document.getElementById('harvestHint');
        if (!el || !hintEl) return;

        var crop = _CROP_DEFAULTS[_PICKED_CROP];
        if (!_PICKED_CROP || !crop) {
            hintEl.textContent = 'Optional — choose a crop type and we\'ll estimate the harvest date for you.';
            return;
        }

        var mdays = parseFloat(crop.maturity_days) || 0;
        var who = crop.name;
        var basis = crop.harvest_days ? ' — typical window ' + crop.harvest_days : '';
        var source = crop.source || 'Sorsogon crop library';

        /* Product > Variety > Crop, same precedence as the yield estimate. */
        var variety = _pickedVariety();
        if (variety && variety.maturity_days) {
            mdays = parseFloat(variety.maturity_days) || mdays;
            who = variety.name + ' (' + crop.name + ')';
            basis = ' — breed-specific maturity';
        }
        if (_PICKED_PRODUCT && _PRODUCT_YIELDS[_PICKED_PRODUCT] && _PRODUCT_YIELDS[_PICKED_PRODUCT].maturity_days) {
            var pv = _PRODUCT_YIELDS[_PICKED_PRODUCT];
            mdays = parseFloat(pv.maturity_days) || 0;
            who = pv.variety + ' ' + crop.name;
            basis = ' — maturity of the selected seed product';
        }

        var plantEl = document.getElementById('plantingDate');
        var date = new Date((plantEl ? plantEl.value : '') + 'T00:00:00');
        if (!mdays || isNaN(date.getTime())) {
            hintEl.textContent = mdays
                ? 'Enter a planting date above and we\'ll estimate when harvest comes.'
                : 'No maturity data for this crop yet — enter the date yourself.';
            return;
        }

        date.setDate(date.getDate() + mdays);
        var suggested = _harvestFmt(date);

        if (!el.value || _harvestAuto) {
            el.value = _harvestYmd(date);
            _harvestAuto = true;
            hintEl.innerHTML = _esc('Auto-set: ' + who + ' takes ' + mdays + ' days from planting to harvest' +
                basis + ' → ' + suggested + '.') + _sourceLinkHtml(source, crop.source_url);
        } else {
            hintEl.textContent = 'You set your own harvest date. ' + who + ' takes ' + mdays +
                ' days from planting → suggested ' + suggested + '.';
        }
    };

    window.pickCrop = function (id) {
        _PICKED_CROP = String(id);
        fillVarietyOptions(_PICKED_CROP);
        showCropInfo(_PICKED_CROP);
        filterProducts();
        window.recalcYield();
        window.recalcHarvest();
        window.autoBatchName();
    };

    window.pickProduct = function (id) {
        _PICKED_PRODUCT = id ? String(id) : '';
        window.recalcYield();
        window.recalcHarvest();
    };

    /* Filter the Specific Product dropdown so only seeds genuinely meant for the
       selected crop show. A product is applicable when:
         - it is directly linked to the crop (product_applications.crop_id), or
         - its name contains one of the crop's curated keywords, or
         - the crop declares no keywords AND its category is an allowed fallback. */
    function filterProducts() {
        var cropId = _PICKED_CROP;
        var allowed = _CROP_PRODUCT_SLUGS[cropId] || [];
        var keywords = _CROP_PRODUCT_KEYWORDS[cropId] || [];
        var opts = document.querySelectorAll('#prodList .ncb-select-option[data-pid]');
        var anyVisible = false;
        for (var i = 0; i < opts.length; i++) {
            var opt = opts[i];
            var cat = opt.getAttribute('data-catslug') || '';
            var name = opt.getAttribute('data-name') || '';
            var cropIds = (opt.getAttribute('data-cropids') || '')
                .split(',').filter(function (x) { return x.trim() !== ''; });

            var linked = cropIds.indexOf(cropId) !== -1;
            var keywordHit = keywords.length > 0 && keywords.indexOf('') === -1 &&
                keywords.some(function (kw) { return name.indexOf(kw) !== -1; });
            var catFallback = keywords.length === 0 && allowed.indexOf(cat) !== -1;

            var show = linked || keywordHit || catFallback;
            opt.style.display = show ? 'block' : 'none';
            if (show) anyVisible = true;
        }
        var emptyEl = document.getElementById('prodNoMatch');
        if (emptyEl) emptyEl.style.display = (cropId && !anyVisible) ? 'block' : 'none';
        if (_PICKED_PRODUCT) {
            var selOpt = document.querySelector('#prodList .ncb-select-option[data-pid="' + _PICKED_PRODUCT + '"]');
            if (selOpt && selOpt.style.display === 'none') {
                var noneBtn = document.getElementById('prodNone');
                if (noneBtn) noneBtn.dispatchEvent(new MouseEvent('click', { bubbles: true }));
            }
        }
    }

    window.recalcYield = function () {
        var yieldEl = document.getElementById('estYield');
        var unitEl = document.getElementById('estUnit');
        var hintEl = document.getElementById('yieldHint');
        if (!yieldEl || !unitEl || !hintEl) return;

        window.recalcCost();

        var areaInput = document.getElementById('addLandArea');
        var unitInput = document.getElementById('landAreaUnit');
        var area = parseFloat(areaInput ? areaInput.value : 0) || 0;
        var unit = unitInput ? unitInput.value : 'ha';
        var crop = _CROP_DEFAULTS[_PICKED_CROP];

        if (!crop || !_PICKED_CROP) {
            hintEl.textContent = _PICKED_CROP
                ? 'No built-in yield rate for this crop yet — enter the estimate manually, or leave blank (optional).'
                : '';
            return;
        }

        var base = parseFloat(crop.default_yield_per_ha) || 0;
        var yieldUnit = crop.yield_unit || 'kg';
        var srcDesc = 'the typical Sorsogon yield for ' + crop.name;

        /* Product > Variety > Crop */
        var variety = _pickedVariety();
        if (variety && variety.yield_kg_ha) {
            base = parseFloat(variety.yield_kg_ha) || base;
            srcDesc = 'the ' + variety.name + ' variety yield';
        }

        if (_PICKED_PRODUCT && _PRODUCT_YIELDS[_PICKED_PRODUCT]) {
            var pv = _PRODUCT_YIELDS[_PICKED_PRODUCT];
            base = parseFloat(pv.default_yield_per_ha) || base;
            yieldUnit = pv.yield_unit || yieldUnit;
            srcDesc = 'the ' + pv.variety + ' seed-product yield';
        }

        if (!base) {
            hintEl.textContent = 'No built-in yield rate for this crop yet — enter the estimate manually, or leave blank (optional).';
            return;
        }

        var ha = unit === 'ha' ? area
            : (unit === 'sq_m' ? area / 10000 : area / 107639.104);
        var kgEst = base * ha;
        _lastKgEst = kgEst;

        var curUnit = (unitEl.value || '').trim().toLowerCase();
        var est, outUnit;
        if (!curUnit) {
            outUnit = _KG_PER_UNIT[yieldUnit] !== undefined ? yieldUnit : 'kg';
            est = kgEst / (_KG_PER_UNIT[outUnit] || 1);
            if (outUnit === 'kg' && est >= 1000) { est = est / 1000; outUnit = 'tons'; }
        } else if (_KG_PER_UNIT[curUnit] !== undefined) {
            outUnit = curUnit;
            est = kgEst / _KG_PER_UNIT[curUnit];
        } else {
            hintEl.textContent = 'Your estimate is about ' + Number(kgEst).toLocaleString() + ' kg — ' +
                'pick a standard unit above or just type your own.';
            return;
        }
        est = (est < 10) ? Math.round(est * 100) / 100 : Math.round(est);

        yieldEl.value = est;
        unitEl.value = outUnit;

        var areaLabel = unit === 'ha' ? area + ' ha' : (unit === 'sq_m' ? area + ' m²' : area + ' ft²');
        hintEl.innerHTML = _esc('Auto-estimated ~' + Number(est).toLocaleString() + ' ' + outUnit + ' using ' +
            srcDesc + ' of ' + Number(base).toLocaleString() + ' ' + yieldUnit + '/ha × ' + areaLabel + '.') +
            _sourceLinkHtml(crop.source || 'Sorsogon crop library', crop.source_url);
    };

    /* ── Estimated cost auto-calculation ── */
    window.recalcCost = function () {
        var costEl = document.getElementById('estCost');
        var hintEl = document.getElementById('costHint');
        if (!costEl || !hintEl) return;

        var info = _PRODUCT_COSTS[_PICKED_PRODUCT];
        if (!_PICKED_PRODUCT || !info) {
            hintEl.textContent = _PICKED_PRODUCT
                ? 'We don\'t have a planting rate for this product yet — no problem, just type the cost you expect.'
                : 'Optional: Choose a seed product above and we\'ll estimate the total cost for your land area automatically.';
            return;
        }

        var areaInput = document.getElementById('addLandArea');
        var unitInput = document.getElementById('landAreaUnit');
        var area = parseFloat(areaInput ? areaInput.value : 0) || 0;
        var unit = unitInput ? unitInput.value : 'ha';
        if (!area) return;

        var ha = unit === 'ha' ? area : (unit === 'sq_m' ? area / 10000 : area / 107639.104);
        var rateUnit = info.rate_unit || 'kg/ha';
        var factor = 1;
        if (info.package_unit === 'g' && rateUnit.indexOf('kg') === 0) factor = 1000;
        else if (info.package_unit === 'kg' && rateUnit.indexOf('g') === 0) factor = 0.001;
        var pkgSize = parseFloat(info.package_size) || 1;
        var price = parseFloat(info.price) || 0;
        var rateMin = parseFloat(info.rate_min) || 0;
        var rateMax = parseFloat(info.rate_max) || rateMin;
        var label = (info.package_label || 'package').toLowerCase();

        function packsFor(rate) { return rate > 0 ? Math.ceil((ha * rate * factor) / pkgSize) : 1; }

        var packs = packsFor(rateMax);
        var cost = Math.round(packs * price * 100) / 100;
        costEl.value = cost.toFixed(2);

        var fmt = function (n) { return Number(n).toLocaleString('en-US'); };
        var plural = function (n, s) { return n === 1 ? s : (s.slice(-1) === 's' ? s : s + 's'); };
        var needUnits = Math.round(ha * rateMax * factor);

        if (rateMax > 0) {
            var rateLabel = rateMax === rateMin
                ? fmt(rateMax) + ' ' + rateUnit
                : fmt(rateMin) + '–' + fmt(rateMax) + ' ' + rateUnit;
            var perWord = (info.price_unit || '').replace(/^per\s+/i, '') ||
                (info.package_unit === 'piece' ? 'piece' : label);
            var nounWord = plural(packs, info.package_unit === 'piece' ? 'unit' : label);

            hintEl.textContent = 'Your seeds for this batch will cost about ₱' + fmt(cost) + '. ' +
                'For ' + fmt(ha) + ' hectar' + (ha === 1 ? 'e' : 'es') + ' at the usual planting rate of ' + rateLabel +
                ', you\'ll need around ' + fmt(needUnits) + ' ' + plural(needUnits, info.package_unit) +
                ' — about ' + packs + ' ' + nounWord + ' at ₱' + fmt(price) + ' per ' + perWord + '.';
        } else {
            hintEl.textContent = 'We don\'t have a planting rate for this product yet — this is just the price of one ' +
                label + ': ₱' + fmt(price) + '. Enter the real cost you expect below.';
        }
    };

    /* ── Yield Unit search-as-you-type combobox ── */
    var _UNIT_OPTIONS = [
        ['kg', 'kilograms'],
        ['tons', 'tonnes / metric tons'],
        ['sacks', 'sacks (rice ≈ 50 kg)'],
        ['cavans', 'cavan (palay ≈ 50 kg)'],
        ['quintal', '100 kg'],
        ['pieces', 'count'],
        ['nuts', 'coconut / pili nuts'],
        ['bunches', 'bananas'],
        ['heads', 'cabbage / lettuce heads'],
        ['bundles', 'bounded leafy greens'],
        ['crates', 'packed produce'],
        ['bales', 'abaca / hay'],
        ['stalks', 'corn plants'],
        ['trees', 'perennial trees'],
        ['liters', 'liquid measure'],
        ['grams', 'small weights'],
    ];
    var _estUnitEl = document.getElementById('estUnit');
    var _unitMenuEl = document.getElementById('unitMenu');

    function renderUnitMenu() {
        var f = (_estUnitEl.value || '').trim().toLowerCase();
        var items = _UNIT_OPTIONS.filter(function (u) { return !f || u[0].indexOf(f) !== -1 || u[1].indexOf(f) !== -1; });
        if (!items.length) {
            _unitMenuEl.style.display = 'block';
            _unitMenuEl.innerHTML = '<div class="ncb-unit-empty">No matching units — just type your own.</div>';
            return;
        }
        _unitMenuEl.innerHTML = items.map(function (u) {
            return '<div class="ncb-unit-option" data-unit="' + u[0] + '">' +
                '<span>' + u[0] + '</span><span class="ncb-unit-alias">' + u[1] + '</span></div>';
        }).join('');
        _unitMenuEl.style.display = 'block';
    }

    function closeUnitMenu() { _unitMenuEl.style.display = 'none'; }

    if (_estUnitEl && _unitMenuEl) {
        _unitMenuEl.addEventListener('mousedown', function (e) {
            var opt = e.target.closest('.ncb-unit-option');
            if (!opt) return;
            e.preventDefault();
            _estUnitEl.value = opt.getAttribute('data-unit');
            closeUnitMenu();
            window.recalcYield && window.recalcYield();
        });
        _estUnitEl.addEventListener('focus', function () { renderUnitMenu(); });
        _estUnitEl.addEventListener('input', function () { renderUnitMenu(); });
        _estUnitEl.addEventListener('change', function () { window.recalcYield && window.recalcYield(); });
        _estUnitEl.addEventListener('blur', function () { setTimeout(closeUnitMenu, 150); });
        _estUnitEl.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeUnitMenu(); _estUnitEl.blur(); }
            if (e.key === 'Enter') {
                var sel = _unitMenuEl.querySelector('.ncb-unit-option');
                if (sel) { _estUnitEl.value = sel.getAttribute('data-unit'); }
                closeUnitMenu();
                window.recalcYield && window.recalcYield();
            }
        });
    }

    var _plantingEl = document.getElementById('plantingDate');
    var _harvestEl = document.getElementById('estHarvest');
    if (_plantingEl) {
        _plantingEl.addEventListener('change', function () {
            window.recalcHarvest && window.recalcHarvest();
            window.autoBatchName && window.autoBatchName();
        });
    }
    if (_harvestEl) {
        _harvestEl.addEventListener('change', function () {
            _harvestAuto = false;
            if (!_harvestEl.value) { window.recalcHarvest && window.recalcHarvest(); }
        });
    }

    window.recalcCost && window.recalcCost();
    window.recalcHarvest && window.recalcHarvest();
</script>