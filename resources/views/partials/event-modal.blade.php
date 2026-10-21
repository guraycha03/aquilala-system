{{-- Admin-only create/edit modal for association calendar events. --}}
@php $eventTypes = \App\Models\AssociationEvent::TYPES; @endphp

<style>
    .ev-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); display: none; align-items: center; justify-content: center; z-index: 90; padding: 20px; }
    .ev-modal-backdrop.open { display: flex; }
    .ev-modal { background: #fff; border-radius: 16px; width: 100%; max-width: 540px; max-height: 92vh; overflow-y: auto; box-shadow: 0 24px 60px rgba(0,0,0,.25); }
    .ev-modal-head { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid #e5e7eb; }
    .ev-modal-head h3 { margin: 0; font-size: 17px; font-weight: 800; color: #1e2922; }
    .ev-modal-close { border: 0; background: transparent; font-size: 22px; line-height: 1; color: #94a3b8; cursor: pointer; }
    .ev-modal-body { padding: 20px 22px 22px; }
    .ev-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .ev-grid .full { grid-column: 1 / -1; }
    .ev-field label { display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 5px; }
    .ev-field label .req { color: #dc2626; }
    .ev-field input, .ev-field select, .ev-field textarea {
        width: 100%; padding: 9px 11px; border: 1.5px solid #e2e8f0; border-radius: 9px;
        font-size: 13.5px; font-family: inherit; color: #1e2922; background: #fff; box-sizing: border-box;
    }
    .ev-field input:focus, .ev-field select:focus, .ev-field textarea:focus { outline: none; border-color: #4A7A54; box-shadow: 0 0 0 3px rgba(74,122,84,.12); }
    .ev-field textarea { min-height: 74px; resize: vertical; }
    .ev-hint { font-size: 11.5px; color: #94a3b8; margin-top: 4px; }
    .ev-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
    .ev-save { background: #4A7A54; color: #fff; border: 0; padding: 10px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(74,122,84,.25); }
    .ev-cancel { background: #edf1e9; color: #648268; border: 1.5px solid #e2e8f0; padding: 10px 18px; border-radius: 10px; font-size: 13.5px; font-weight: 700; cursor: pointer; }
    .ev-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 12.5px; border-radius: 8px; padding: 8px 12px; margin-bottom: 14px; }
</style>

<div class="ev-modal-backdrop" id="eventModal" role="dialog" aria-modal="true" aria-labelledby="eventModalTitle">
    <div class="ev-modal">
        <div class="ev-modal-head">
            <h3 id="eventModalTitle">Add Event</h3>
            <button type="button" class="ev-modal-close" id="eventModalClose" aria-label="Close">&times;</button>
        </div>
        <div class="ev-modal-body">
            @if($errors->any())
                <div class="ev-error">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.calendar.events.store') }}" id="eventForm">
                @csrf
                <input type="hidden" name="_method" id="eventMethodField" value="PUT" disabled>

                <div class="ev-grid">
                    <div class="ev-field full">
                        <label>Event title <span class="req">*</span></label>
                        <input type="text" name="title" id="evTitle" maxlength="120" required
                               placeholder="e.g. Free Fertilizer Distribution" value="{{ old('title') }}">
                    </div>

                    <div class="ev-field">
                        <label>Type <span class="req">*</span></label>
                        <select name="event_type" id="evType" required>
                            @foreach($eventTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('event_type', 'other') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ev-field">
                        <label>Date <span class="req">*</span></label>
                        <input type="date" name="event_date" id="evDate" min="{{ now()->toDateString() }}" required
                               value="{{ old('event_date', now()->toDateString()) }}">
                    </div>

                    <div class="ev-field">
                        <label>Start time</label>
                        <input type="time" name="start_time" id="evStart" value="{{ old('start_time') }}">
                        <div class="ev-hint">Leave empty for an all-day event.</div>
                    </div>

                    <div class="ev-field">
                        <label>End time</label>
                        <input type="time" name="end_time" id="evEnd" value="{{ old('end_time') }}">
                        <div class="ev-hint">Optional.</div>
                    </div>

                    <div class="ev-field full">
                        <label>Location</label>
                        <input type="text" name="location" id="evLocation" maxlength="255"
                               placeholder="e.g. Association Warehouse" value="{{ old('location') }}">
                    </div>

                    <div class="ev-field full">
                        <label>Details</label>
                        <textarea name="description" id="evDescription" maxlength="1000"
                                  placeholder="Anything members should know…">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="ev-actions">
                    <button type="button" class="ev-cancel" id="eventModalCancel">Cancel</button>
                    <button type="submit" class="ev-save" id="eventModalSave">Add to Calendar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const modal      = document.getElementById('eventModal');
    const form       = document.getElementById('eventForm');
    const methodF    = document.getElementById('eventMethodField');
    const titleEl    = document.getElementById('eventModalTitle');
    const saveBtn    = document.getElementById('eventModalSave');
    const dateInput  = document.getElementById('evDate');
    const todayStr   = @json(now()->toDateString());
    const storeUrl   = @json(route('admin.calendar.events.store'));

    function open()  { modal.classList.add('open'); }
    function close() { modal.classList.remove('open'); }

    window.openEventModal = function (mode, data) {
        if (mode === 'edit' && data) {
            titleEl.textContent = 'Edit Event';
            saveBtn.textContent = 'Save Changes';
            form.action  = storeUrl + '/' + data.id;
            methodF.disabled = false;
            methodF.value = 'PUT';
            document.getElementById('evTitle').value       = data.title || '';
            document.getElementById('evType').value        = data.type || 'other';
            document.getElementById('evDate').value        = data.date || todayStr;
            // A past event keeps its own date as the floor so it stays editable.
            dateInput.min = (data.date && data.date < todayStr) ? data.date : todayStr;
            document.getElementById('evStart').value       = data.start || '';
            document.getElementById('evEnd').value         = data.end || '';
            document.getElementById('evLocation').value    = data.location || '';
            document.getElementById('evDescription').value = data.description || '';
        } else {
            titleEl.textContent = 'Add Event';
            saveBtn.textContent = 'Add to Calendar';
            form.action  = storeUrl;
            methodF.disabled = true;
            methodF.value = 'PUT';
            dateInput.min = todayStr;
            form.reset();
        }
        open();
    };

    document.querySelectorAll('.js-open-event-modal').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openEventModal(btn.dataset.mode, btn.dataset);
        });
    });

    document.querySelectorAll('.js-open-add-event').forEach(function (btn) {
        btn.addEventListener('click', function () { openEventModal('create'); });
    });

    document.getElementById('eventModalClose').addEventListener('click', close);
    document.getElementById('eventModalCancel').addEventListener('click', close);
    modal.addEventListener('click', function (e) { if (e.target === modal) close(); });

    // Re-open with server-side validation errors visible.
    @if(session('event_edit_id'))
        openEventModal('edit', {
            id:          @json(session('event_edit_id')),
            title:       @json(old('title')),
            type:        @json(old('event_type')),
            date:        @json(old('event_date')),
            start:       @json(old('start_time')),
            end:         @json(old('end_time')),
            location:    @json(old('location')),
            description: @json(old('description'))
        });
    @elseif($errors->any())
        openEventModal('create');
    @endif
})();
</script>
