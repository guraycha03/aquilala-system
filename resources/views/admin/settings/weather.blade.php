@extends('layouts.sidebar')

@section('title', 'Weather Log')

@section('content')
  @php
    $types     = \App\Models\WeatherLog::TYPES;
    $severities = \App\Models\WeatherLog::SEVERITIES;
    $events    = $events ?? collect();
    $stats     = $stats ?? ['total' => 0, 'severe' => 0, 'in_span' => 0, 'types' => collect()];
    $today     = now()->format('Y-m-d');
  @endphp

  @include('partials.settings-styles')

  <style>
    .wx-table { width: 100%; border-collapse: collapse; text-align: left; }
    .wx-table thead tr { background: #fafbfa; }
    .wx-table th {
      padding: 12px 16px; color: #94a3b8; font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: .08em; border-bottom: 1px solid #eef1ef;
    }
    .wx-table td { padding: 13px 16px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    .wx-table tbody tr:last-child td { border-bottom: none; }
    .wx-table tbody tr:hover { background: #fafbfa; }
    .wx-empty { padding: 34px 20px; text-align: center; color: #64748b; font-size: 13.5px; }

    .wx-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; white-space: nowrap;
    }
    .wx-badge .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    .wx-title { font-weight: 700; color: #143d23; font-size: 13.5px; }
    .wx-notes { color: #64748b; font-size: 12.5px; margin-top: 3px; line-height: 1.5; white-space: pre-wrap; }
    .wx-dates { font-variant-numeric: tabular-nums; white-space: nowrap; color: #475569; font-weight: 600; }
    .wx-by { color: #94a3b8; font-size: 12px; white-space: nowrap; }

    .wx-del {
      background: none; border: 1px solid #fecaca; color: #dc2626;
      border-radius: 8px; padding: 5px 9px; font-size: 12px; font-weight: 600;
      cursor: pointer; transition: all .15s ease;
    }
    .wx-del:hover { background: #fef2f2; }
    .wx-del:disabled { opacity: .5; cursor: not-allowed; }

    .wx-why {
      display: flex; gap: 11px; align-items: flex-start;
      background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px;
      padding: 14px 16px; font-size: 13px; color: #166534; line-height: 1.65;
      max-width: 860px;
    }
    .wx-why svg { flex-shrink: 0; margin-top: 3px; }
    .wx-why b { color: #14532d; }

    .wx-field-error { color: #dc2626; font-size: 12px; margin-top: 5px; font-weight: 600; }
    input[type="date"], select, textarea {
      width: 100%; background: #ffffff; border: 1px solid #e6ebe8; border-radius: 10px;
      padding: 10px 12px; font-size: 13px; color: #334155; outline: none; font-family: inherit;
    }
    input[type="date"]:focus, select:focus, textarea:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.08); }
    textarea { resize: vertical; min-height: 78px; }
    .wx-cta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 4px; }
    .wx-link {
      display: inline-flex; align-items: center; gap: 7px; text-decoration: none;
      font-size: 13px; font-weight: 700; color: #6d28d9;
      background: #f5f3ff; border: 1.5px solid #ddd6fe; border-radius: 10px; padding: 10px 15px;
      transition: all .2s ease;
    }
    .wx-link:hover { background: #ede9fe; transform: translateY(-1px); }
  </style>

  <div class="set-page">
    <div class="set-content">
      <div class="header-row">
        <div>
          <h1>Weather Log</h1>
          <p>Dated records of weather events that affected the association's farms.</p>
        </div>
        <div class="header-actions">
          <a href="{{ route('admin.harvests.ai') }}" class="wx-link">
            <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>
            See how this feeds AI Insights
          </a>
        </div>
      </div>


      <div class="set-panel">
        <div class="set-body">

          <div class="set-section">
            <div class="wx-why">
              <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <div>
                <b>Why this exists.</b> A harvest report says <i>what</i> was harvested and <i>when</i>,
                but nothing about the weather. Log a typhoon or a flood here — with its real dates — and the
                <a href="{{ route('admin.harvests.ai') }}" style="color:#166534;font-weight:700;">AI Harvest Insights</a>
                page can line that event up against the harvest periods it falls inside, so a drop in yield can be
                explained rather than merely measured. This page is entered by hand on purpose: today's forecast
                cannot tell you what happened in July 2025, and you already know when the typhoon came.
              </div>
            </div>
          </div>

          <div class="set-section">
            <div class="set-section-title">At a glance</div>
            <div class="set-grid">
              <div class="set-card">
                <div class="set-field">
                  <label>Events logged</label>
                  <div style="font-size:26px;font-weight:800;color:#143d23;line-height:1;">{{ $stats['total'] }}</div>
                  <div class="hint">Every weather event recorded so far.</div>
                </div>
              </div>
              <div class="set-card">
                <div class="set-field">
                  <label>Severe or extreme</label>
                  <div style="font-size:26px;font-weight:800;color:#b91c1c;line-height:1;">{{ $stats['severe'] }}</div>
                  <div class="hint">The events most likely to explain a yield movement.</div>
                </div>
              </div>
              <div class="set-card">
                <div class="set-field">
                  <label>Falling inside harvest data</label>
                  <div style="font-size:26px;font-weight:800;color:#16a34a;line-height:1;">{{ $stats['in_span'] }}</div>
                  <div class="hint">Events dated within your recorded harvest range — these are the ones the AI can correlate.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="set-section">
            <div class="set-section-title">Log an event</div>
            <form id="wxForm" class="set-grid" novalidate>
              <div class="set-card">
                <div class="set-field">
                  <label for="wx_title">What happened</label>
                  <input type="text" id="wx_title" name="title" maxlength="120" placeholder="e.g. Typhoon Kristine" required>
                  <div class="hint">A name people will recognise, or a short description.</div>
                  <div class="wx-field-error" data-error="title" hidden></div>
                </div>
                <div class="set-field">
                  <label for="wx_type">Event type</label>
                  <select id="wx_type" name="event_type">
                    @foreach($types as $key => $label)
                      <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                  </select>
                  <div class="hint">Groups similar events so the analysis can speak about them consistently.</div>
                </div>
              </div>

              <div class="set-card">
                <div class="set-field">
                  <label for="wx_severity">Severity</label>
                  <select id="wx_severity" name="severity">
                    @foreach($severities as $key => $label)
                      <option value="{{ $key }}" @selected($key === 'moderate')>{{ $label }}</option>
                    @endforeach
                  </select>
                  <div class="hint">How badly it hit. Severe and extreme events are called out first.</div>
                </div>
                <div class="set-field">
                  <label for="wx_start">Date range</label>
                  <div class="season-dates">
                    <input type="date" id="wx_start" name="start_date" max="{{ $today }}" required aria-label="Start date">
                    <span class="sep">to</span>
                    <input type="date" id="wx_end" name="end_date" max="{{ $today }}" aria-label="End date (optional)">
                  </div>
                  <div class="hint">Leave the end date empty for a single-day event.</div>
                  <div class="wx-field-error" data-error="start_date" hidden></div>
                  <div class="wx-field-error" data-error="end_date" hidden></div>
                </div>
              </div>

              <div class="set-card" style="grid-column: 1 / -1;">
                <div class="set-field">
                  <label for="wx_notes">Notes <span style="font-weight:500;color:#94a3b8;">(optional)</span></label>
                  <textarea id="wx_notes" name="notes" maxlength="1000" placeholder="Areas affected, crops damaged, how long fields stayed flooded…"></textarea>
                  <div class="hint">Anything you would want the AI to know when it explains a harvest period. Max 1,000 characters.</div>
                </div>
                <div class="wx-cta">
                  <button type="submit" id="wxSave" class="btn-set">
                    <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Add to log
                  </button>
                  <span style="font-size:12.5px;color:#94a3b8;">Saved events appear in the list below immediately.</span>
                </div>
              </div>
            </form>
          </div>

          <div class="set-section" style="max-width:1100px;">
            <div class="set-section-title">Logged events</div>
            <div class="set-card" style="padding:0;overflow-x:auto;">
              <table class="wx-table" id="wxTable">
                <thead>
                  <tr>
                    <th>Event</th>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>Dates</th>
                    <th>Logged by</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($events as $event)
                    <tr data-id="{{ $event->id }}">
                      <td>
                        <div class="wx-title">{{ $event->title }}</div>
                        @if($event->notes)<div class="wx-notes">{{ $event->notes }}</div>@endif
                      </td>
                      <td>{{ $event->typeLabel() }}</td>
                      <td><span class="wx-badge" style="{{ $severities[$event->severity] === 'Extreme' || $severities[$event->severity] === 'Severe' ? 'background:#fef2f2;color:#b91c1c;' : ($severities[$event->severity] === 'Minor' ? 'background:#f1f5f9;color:#64748b;' : 'background:#fffbeb;color:#b45309;') }}"><span class="dot"></span>{{ $event->severityLabel() }}</span></td>
                      <td class="wx-dates">{{ $event->dateRangeLabel() }}</td>
                      <td class="wx-by">{{ $event->logger?->name ?? '—' }}</td>
                      <td style="text-align:right;"><button type="button" class="wx-del" data-url="{{ route('admin.settings.weather.destroy', $event) }}">Remove</button></td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="6" class="wx-empty">
                        No weather events logged yet.<br>
                        <span style="font-size:12.5px;color:#94a3b8;">Add your first event above so harvest explanations have something to point at.</span>
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <div class="set-toast" id="setToast">
    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
    <span id="setToastMsg">Settings saved successfully.</span>
  </div>

  <form id="csrfForm" style="display:none;">@csrf</form>

  <script>
    (function () {
      function csrfToken() {
        var el = document.getElementById('csrfForm').querySelector('input[name=_token]');
        return el ? el.value : '';
      }

      var toast = document.getElementById('setToast');
      var toastMsg = document.getElementById('setToastMsg');
      var toastTimer = null;
      function showToast(msg) {
        toastMsg.textContent = msg;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2600);
      }

      var TYPE_LABELS = @json(\App\Models\WeatherLog::TYPES);
      var SEVERITY_LABELS = @json(\App\Models\WeatherLog::SEVERITIES);
      var SEVERITY_STYLE = {
        minor:    'background:#f1f5f9;color:#64748b;',
        moderate: 'background:#fffbeb;color:#b45309;',
        severe:   'background:#fef2f2;color:#b91c1c;',
        extreme:  'background:#fef2f2;color:#b91c1c;'
      };

      function esc(s) {
        return String(s == null ? '' : s)
          .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;');
      }

      // "Sep 10 – 13, 2026", or the same day twice when no end date is set.
      function formatRange(start, end) {
        var d1 = new Date(start + 'T00:00:00');
        var opts = { month: 'short', day: 'numeric', year: 'numeric' };
        if (!end || end === start) return d1.toLocaleDateString('en-US', opts);
        var d2 = new Date(end + 'T00:00:00');
        if (d1.getMonth() === d2.getMonth() && d1.getFullYear() === d2.getFullYear()) {
          return d1.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' – ' +
                 d2.getDate() + ', ' + d2.getFullYear();
        }
        return d1.toLocaleDateString('en-US', opts) + ' – ' + d2.toLocaleDateString('en-US', opts);
      }

      function clearErrors() {
        document.querySelectorAll('.wx-field-error').forEach(function (el) {
          el.hidden = true; el.textContent = '';
        });
      }

      function showErrors(errors) {
        Object.keys(errors).forEach(function (key) {
          var el = document.querySelector('[data-error="' + key + '"]');
          if (el) { el.textContent = errors[key][0]; el.hidden = false; }
        });
      }

      function rowHtml(ev) {
        return '<tr data-id="' + ev.id + '">' +
          '<td><div class="wx-title">' + esc(ev.title) + '</div>' +
          (ev.notes ? '<div class="wx-notes">' + esc(ev.notes) + '</div>' : '') + '</td>' +
          '<td>' + esc(ev.type_label) + '</td>' +
          '<td><span class="wx-badge" style="' + (SEVERITY_STYLE[ev.severity] || '') + '">' +
            '<span class="dot"></span>' + esc(ev.severity_label) + '</span></td>' +
          '<td class="wx-dates">' + esc(ev.date_label) + '</td>' +
          '<td class="wx-by">' + esc(ev.logger || '—') + '</td>' +
          '<td style="text-align:right;"><button type="button" class="wx-del" data-url="' + esc(ev.delete_url) + '">Remove</button></td>' +
        '</tr>';
      }

      function refreshEmptyState() {
        var tbody = document.querySelector('#wxTable tbody');
        var hasData = !!tbody.querySelector('tr[data-id]');
        var empty = tbody.querySelector('.wx-empty');
        if (hasData && empty) empty.closest('tr').remove();
        if (!hasData && !empty) {
          var tr = document.createElement('tr');
          tr.innerHTML = '<td colspan="6" class="wx-empty">No weather events logged yet.</td>';
          tbody.appendChild(tr);
        }
      }

      // ── Add ──────────────────────────────────────────────────
      document.getElementById('wxForm').addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var btn = document.getElementById('wxSave');
        btn.disabled = true;

        var f = e.target;
        var payload = {
          title: f.title.value.trim(),
          event_type: f.event_type.value,
          severity: f.severity.value,
          start_date: f.start_date.value,
          end_date: f.end_date.value || null,
          notes: f.notes.value.trim() || null
        };

        fetch('{{ route('admin.settings.weather.store') }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
          body: JSON.stringify(payload)
        })
          .then(function (r) {
            return r.json().then(function (d) { return { ok: r.ok, status: r.status, data: d }; });
          })
          .then(function (res) {
            if (!res.ok) {
              showErrors(res.data && res.data.errors ? res.data.errors : {});
              showToast('Check the highlighted fields and try again.');
              return;
            }
            var tbody = document.querySelector('#wxTable tbody');
            tbody.insertAdjacentHTML('afterbegin', rowHtml(res.data.event));
            refreshEmptyState();
            f.reset();
            showToast(res.data.flash || 'Weather event added to the log.');
          })
          .catch(function () { showToast('Could not save — check your connection.'); })
          .finally(function () { btn.disabled = false; });
      });

      // ── Remove ───────────────────────────────────────────────
      document.getElementById('wxTable').addEventListener('click', function (e) {
        var btn = e.target.closest('.wx-del');
        if (!btn) return;
        if (!window.confirm('Remove this weather event from the log?')) return;

        btn.disabled = true;
        fetch(btn.dataset.url, {
          method: 'DELETE',
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() }
        })
          .then(function (r) {
            if (!r.ok) throw new Error('failed');
            btn.closest('tr').remove();
            refreshEmptyState();
            showToast('Weather event removed from the log.');
          })
          .catch(function () {
            btn.disabled = false;
            showToast('Could not remove that event — please try again.');
          });
      });
    })();
  </script>
@endsection
