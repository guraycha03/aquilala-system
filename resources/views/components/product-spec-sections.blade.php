@props([
    // Existing rows when editing, null when creating.
    'fertilizer' => null,
    'registration' => null,
])

@php
    // On the create form both props are null, so the date inputs have to fall
    // back to empty. A ternary cannot guard that, hence the explicit lookup.
    $manufacturingDate = $registration?->manufacturing_date
        ? \Illuminate\Support\Carbon::parse($registration->manufacturing_date)->format('Y-m-d')
        : '';
    $expirationDate = $registration?->expiration_date
        ? \Illuminate\Support\Carbon::parse($registration->expiration_date)->format('Y-m-d')
        : '';
@endphp

{{-- Optional spec blocks: NPK analysis + application guidance, and regulatory
     registration. Both are stored in their own tables and only written when at
     least one field in the block is filled in, so leaving them blank keeps the
     product clean. --}}

<!-- SECTION: FERTILIZER ANALYSIS -->
<div class="section-header">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v18M15 3v18M3 9h18M3 15h18"></path></svg>
    Fertilizer Analysis <span style="font-weight: 400; font-size: 13px; color: #64748b;">(Optional — fertilizers only)</span>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="nitrogen_percentage">Nitrogen (N) %</label>
        <input type="number" class="form-control" id="nitrogen_percentage" name="nitrogen_percentage" value="{{ old('nitrogen_percentage', $fertilizer->nitrogen_percentage ?? '') }}" placeholder="e.g. 46" step="0.01" min="0" max="100">
        <span class="hint">Use 0 when the grade genuinely has none, e.g. 0-20-0.</span>
    </div>

    <div class="form-group">
        <label for="phosphorus_percentage">Phosphorus (P) %</label>
        <input type="number" class="form-control" id="phosphorus_percentage" name="phosphorus_percentage" value="{{ old('phosphorus_percentage', $fertilizer->phosphorus_percentage ?? '') }}" placeholder="e.g. 20" step="0.01" min="0" max="100">
    </div>

    <div class="form-group">
        <label for="potassium_percentage">Potassium (K) %</label>
        <input type="number" class="form-control" id="potassium_percentage" name="potassium_percentage" value="{{ old('potassium_percentage', $fertilizer->potassium_percentage ?? '') }}" placeholder="e.g. 0" step="0.01" min="0" max="100">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="form_type">Form</label>
        <input type="text" class="form-control" id="form_type" name="form_type" value="{{ old('form_type', $fertilizer->form_type ?? '') }}" placeholder="e.g. Granular, Prilled, Liquid" list="fertilizer_forms" maxlength="100">
        <datalist id="fertilizer_forms">
            @foreach(['Granular', 'Prilled', 'Liquid', 'Powder', 'Foliar', 'Suspension', 'Crystals'] as $form)
                <option value="{{ $form }}"></option>
            @endforeach
        </datalist>
    </div>

    <div class="form-group">
        <label for="fertilizer_rate_min">Application Rate (min)</label>
        <input type="number" class="form-control" id="fertilizer_rate_min" name="fertilizer_rate_min" value="{{ old('fertilizer_rate_min', $fertilizer->application_rate_min ?? '') }}" placeholder="e.g. 25" step="0.01" min="0">
    </div>

    <div class="form-group">
        <label for="fertilizer_rate_max">Application Rate (max)</label>
        <input type="number" class="form-control" id="fertilizer_rate_max" name="fertilizer_rate_max" value="{{ old('fertilizer_rate_max', $fertilizer->application_rate_max ?? '') }}" placeholder="e.g. 50" step="0.01" min="0">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="fertilizer_rate_unit">Rate Unit</label>
        <input type="text" class="form-control" id="fertilizer_rate_unit" name="fertilizer_rate_unit" value="{{ old('fertilizer_rate_unit', $fertilizer->application_rate_unit ?? '') }}" placeholder="e.g. kg/ha" list="fertilizer_rate_units" maxlength="50">
        <datalist id="fertilizer_rate_units">
            @foreach(['kg/ha', 'g/ha', 'L/ha', 'mL/ha', 'kg/pack', 'L/pack'] as $rateUnit)
                <option value="{{ $rateUnit }}"></option>
            @endforeach
        </datalist>
    </div>

    <div class="form-group">
        <label for="fertilizer_method">Application Method</label>
        <input type="text" class="form-control" id="fertilizer_method" name="fertilizer_method" value="{{ old('fertilizer_method', $fertilizer->application_method ?? '') }}" placeholder="e.g. Broadcasting at planting" maxlength="255">
    </div>

    <div class="form-group">
        <label for="fertilizer_timing">Application Timing</label>
        <input type="text" class="form-control" id="fertilizer_timing" name="fertilizer_timing" value="{{ old('fertilizer_timing', $fertilizer->application_timing ?? '') }}" placeholder="e.g. Basal and early vegetative growth" maxlength="255">
    </div>
</div>

<!-- SECTION: REGISTRATION -->
<div class="section-header">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"></path><path d="M12 3l7 3v6c0 4.5-3 8.2-7 9-4-.8-7-4.5-7-9V6l7-3z"></path></svg>
    Registration &amp; Batch <span style="font-weight: 400; font-size: 13px; color: #64748b;">(Optional — chemicals, pesticides, seeds)</span>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="registration_status">Registration Status</label>
        <input type="text" class="form-control" id="registration_status" name="registration_status" value="{{ old('registration_status', $registration->registration_status ?? '') }}" placeholder="e.g. Registered, Pending" list="registration_statuses" maxlength="50">
        <datalist id="registration_statuses">
            @foreach(['Registered', 'Pending', 'Expired', 'Cancelled', 'Not Required'] as $regStatus)
                <option value="{{ $regStatus }}"></option>
            @endforeach
        </datalist>
    </div>

    <div class="form-group">
        <label for="registration_authority">Registration Authority</label>
        <input type="text" class="form-control" id="registration_authority" name="registration_authority" value="{{ old('registration_authority', $registration->registration_authority ?? '') }}" placeholder="e.g. Fertilizer and Pesticide Authority" maxlength="255">
    </div>

    <div class="form-group">
        <label for="registration_number">Registration Number</label>
        <input type="text" class="form-control" id="registration_number" name="registration_number" value="{{ old('registration_number', $registration->registration_number ?? '') }}" placeholder="e.g. 1234-ABC-5678" maxlength="255">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="lot_number">Lot Number</label>
        <input type="text" class="form-control" id="lot_number" name="lot_number" value="{{ old('lot_number', $registration->lot_number ?? '') }}" placeholder="e.g. LOT-2026-014" maxlength="255">
    </div>

    <div class="form-group">
        <label for="batch_number">Batch Number</label>
        <input type="text" class="form-control" id="batch_number" name="batch_number" value="{{ old('batch_number', $registration->batch_number ?? '') }}" placeholder="e.g. BATCH-0142" maxlength="255">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="manufacturing_date">Manufacturing Date</label>
        <input type="date" class="form-control" id="manufacturing_date" name="manufacturing_date" value="{{ old('manufacturing_date', $manufacturingDate) }}">
    </div>

    <div class="form-group">
        <label for="expiration_date">Expiration Date</label>
        <input type="date" class="form-control" id="expiration_date" name="expiration_date" value="{{ old('expiration_date', $expirationDate) }}">
        <span class="hint">Cannot be earlier than the manufacturing date.</span>
    </div>
</div>
