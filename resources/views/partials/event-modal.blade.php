{{-- Admin-only create/edit modal for association calendar events. --}}
@php $eventTypes = \App\Models\AssociationEvent::TYPES; @endphp

<style>
    .ev-modal-backdrop { position: fixed; inset: 0; background: rgba(30, 45, 35, .28); display: none; align-items: center; justify-content: center; z-index: 90; padding: 20px; }
    .ev-modal-backdrop.open { display: flex; }
    .ev-modal {
        background: var(--cal-surface, #fff);
        border: 1px solid var(--cal-line, #e7ece6);
        border-radius: 14px; width: 100%; max-width: 520px;
        max-height: 92vh; overflow-y: auto;
        box-shadow: 0 16px 40px rgba(30, 45, 35, .12);
    }
    .ev-modal-head {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid var(--cal-line-soft, #f0f3ef);
        background: var(--cal-wash, #f8faf8);
    }
    .ev-modal-head h3 { margin: 0; font-size: 15px; font-weight: 750; color: var(--cal-ink, #26342b); }
    .ev-modal-close { border: 0; background: transparent; font-size: 21px; line-height: 1; color: var(--cal-muted, #748078); cursor: pointer; }
    .ev-modal-body { padding: 18px 20px 20px; }
    .ev-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 13px; }
    .ev-grid .full { grid-column: 1 / -1; }
    .ev-field label { display: block; font-size: 12px; font-weight: 700; color: var(--cal-ink, #26342b); margin-bottom: 5px; }
    .ev-field label .req { color: #9b625c; }
    .ev-field input, .ev-field select, .ev-field textarea {
        width: 100%; padding: 8px 10px;
        border: 1px solid var(--cal-line, #e7ece6); border-radius: 8px;
        font-size: 13px; font-family: inherit;
        color: var(--cal-ink, #26342b); background: var(--cal-surface, #fff); box-sizing: border-box;
    }
    .ev-field input:focus, .ev-field select:focus, .ev-field textarea:focus {
        outline: none; border-color: var(--cal-accent, #5c7f66);
        box-shadow: 0 0 0 2px var(--cal-accent-soft, #eef4ee);
    }
    .ev-field textarea { min-height: 72px; resize: vertical; }
    .ev-hint { font-size: 11px; color: var(--cal-muted, #748078); margin-top: 4px; }
    .ev-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 17px; }
    .ev-save {
        background: var(--cal-accent, #5c7f66); color: #fff; border: 1px solid var(--cal-accent, #5c7f66);
        padding: 9px 18px; border-radius: 9px; font-size: 12.5px; font-weight: 700; cursor: pointer;
    }
    .ev-save:hover { background: #50705a; }
    .ev-cancel {
        background: var(--cal-wash, #f8faf8); color: var(--cal-muted, #748078);
        border: 1px solid var(--cal-line, #e7ece6);
        padding: 9px 16px; border-radius: 9px; font-size: 12.5px; font-weight: 700; cursor: pointer;
    }
    .ev-cancel:hover { background: var(--cal-accent-soft, #eef4ee); }
    .ev-error {
        background: #fbf3f2; border: 1px solid #ead9d6; color: #9b625c;
        font-size: 12.5px; border-radius: 8px; padding: 8px 11px; margin-bottom: 13px;
    }
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
