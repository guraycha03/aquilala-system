@extends('layouts.sidebar')

@section('title', 'General Settings')

@section('content')
  @php
    // SAMPLE defaults — persist to a settings table later.
    $defaults = [
        'org_name'       => 'Aquilala Irrigators Association, Inc.',
        'system_name'    => 'Aquilala IA Management Portal',
        'address'        => 'San Francisco RIS, Brgy. Aquino, Bulan, Sorsogon',
        'official_address' => 'San Francisco RIS, Brgy. Aquino, Bulan, Sorsogon, Philippines',
        'ia_president'   => 'Ana V. Lacsa',
        'sec_reg_no'     => 'CN201228664',
        'contact'        => '0917 000 0000',
        'website'        => 'facebook.com/aquilala',
        'currency'       => 'Philippine Peso (PHP)',
        'timezone'       => 'Asia/Manila (UTC+8)',
    ];
  @endphp

  @include('partials.settings-styles')

  <div class="set-page">
    <div class="set-content">
      <div class="header-row">
        <div>
          <h1>General Settings</h1>
          <p>Platform identity, regional formatting, and the association's official location.</p>
        </div>
        <div class="header-actions">
          <button id="resetBtn" class="btn-set ghost">
            <svg viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
            Reset
          </button>
          <button id="saveBtn" class="btn-set">
            <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Changes
          </button>
        </div>
      </div>


      <div class="set-panel">
        <div class="set-body">

          <div class="set-section">
            <div class="set-section-title">Platform Identity</div>
            <div class="set-grid">
              <div class="set-card">
                <div class="set-field">
                  <label>Organization Name</label>
                  <input type="text" name="org_name" value="{{ $defaults['org_name'] }}">
                </div>
                <div class="set-field">
                  <label>System Name</label>
                  <input type="text" name="system_name" value="{{ $defaults['system_name'] }}">
                </div>
                <div class="set-field">
                  <label>SEC Registration No.</label>
                  <input type="text" name="sec_reg_no" value="{{ $defaults['sec_reg_no'] }}">
                </div>
                <div class="set-field">
                  <label>IA President</label>
                  <input type="text" name="ia_president" value="{{ $defaults['ia_president'] }}">
                  <div class="hint">Association presiding officer shown on admin pages.</div>
                </div>
              </div>
              <div class="set-card">
                <div class="set-field">
                  <label>Contact Number</label>
                  <input type="text" name="contact" value="{{ $defaults['contact'] }}">
                </div>
                <div class="set-field">
                  <label>Website / Page</label>
                  <input type="text" name="website" value="facebook.com/aquilala">
                </div>
              </div>
            </div>
          </div>

          <div class="set-section">
            <div class="set-section-title">Regional Formatting</div>
            <div class="set-grid">
              <div class="set-card">
                <div class="set-field">
                  <label>Currency</label>
                  <select name="currency">
                    <option selected>{{ $defaults['currency'] }}</option>
                    <option>US Dollar (USD)</option>
                  </select>
                  <div class="hint">Used for product prices and cost quotations across the platform.</div>
                </div>
              </div>
              <div class="set-card">
                <div class="set-field">
                  <label>Timezone</label>
                  <select name="timezone">
                    <option selected>{{ $defaults['timezone'] }}</option>
                    <option>UTC</option>
                  </select>
                  <div class="hint">Applies to timestamps, quotation validity, and scheduled alerts.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="set-section">
            <div class="set-section-title">Organization Location</div>
            <div class="set-card map-card">
              <div class="map-wrap" id="mapWrap">
                <div class="map-fallback" id="mapFallback">
                  <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  <span>Loading map… (requires internet)</span>
                </div>
                <div id="orgMap" style="display:none;"></div>
                <div class="map-edit-badge" id="mapEditBadge" style="display:none;">
                  <span class="dot"></span> Editing — click the map to set the pin
                </div>
              </div>
              <div class="map-footer">
                <div class="loc">
                  <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  <span id="mapAddress">Aquilala Irrigators Association, Inc., San Francisco RIS, Brgy. Aquino, Bulan, Sorsogon</span>
                </div>
                <button id="mapEditBtn" class="btn-set ghost">
                  <svg id="mapEditIcon" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                  <span id="mapEditLabel">Edit Location</span>
                </button>
              </div>
            </div>
            <div class="map-tip">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
              The map opens in preview (zoomed to the official location). Click “Edit Location” to drop a pin or search for a new one.
            </div>
            <div class="set-grid" style="margin-top:14px;">
              <div class="set-card">
                <div class="set-field">
                  <label>Official Confirmed Address</label>
                  <div class="locked-input">
                    <input type="text" name="official_address" value="{{ $defaults['official_address'] }}" readonly>
                    <svg class="lock" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  </div>
                  <div class="hint">Locked — only updated by dropping a pin or choosing a searched place. It cannot be typed.</div>
                </div>
              </div>
              <div class="set-card">
                <div class="set-field" style="position:relative;">
                  <label>Search Location</label>
                  <div class="place-search">
                    <input type="text" id="placeInput" name="address" value="{{ $defaults['address'] }}" placeholder="Type a barangay, street, or landmark…" autocomplete="off">
                    <button type="button" id="placeSearchBtn" class="btn-set ghost" style="padding:10px 14px; font-size:13px;">Search</button>
                  </div>
                  <div class="place-results" id="placeResults"></div>
                  <div class="hint">Type a place and pick a match — the pin moves there and it is confirmed as the official address.</div>
                </div>
              </div>
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

      function setDefaults() {
        document.querySelectorAll('[name]').forEach(function (el) {
          var map = {
            org_name: '{{ $defaults["org_name"] }}',
            system_name: '{{ $defaults["system_name"] }}',
            sec_reg_no: '{{ $defaults["sec_reg_no"] }}',
            ia_president: '{{ $defaults["ia_president"] }}',
            address: '{{ $defaults["address"] }}',
            official_address: '{{ $defaults["official_address"] }}',
            contact: '{{ $defaults["contact"] }}',
            website: '{{ $defaults["website"] }}',
            currency: '{{ $defaults["currency"] }}',
            timezone: '{{ $defaults["timezone"] }}'
          };
          if (el.tagName === 'SELECT') {
            var sv = map[el.name];
            if (sv) el.value = sv;
          } else if (el.type === 'text') {
            var v = map[el.name];
            if (v !== undefined) el.value = v;
          }
        });
      }

      document.getElementById('saveBtn').addEventListener('click', function () {
        var btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.textContent = 'Saving…';
        setTimeout(function () {
          btn.disabled = false;
          btn.textContent = 'Save Changes';
          showToast('General settings saved.');
        }, 600);
      });

      // Organization location map (OpenStreetMap + Leaflet, free, no API key)
      var ORG_LAT = 12.6747;
      var ORG_LNG = 123.8767;
      var map = null;
      var marker = null;

      function makePin() {
        return L.divIcon({
          className: 'org-pin',
          html: '<svg viewBox="0 0 24 24" width="36" height="36"><path fill="#143d23" stroke="#ffffff" stroke-width="1.4" d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/><circle cx="12" cy="9" r="2.6" fill="#6aeb2f"/></svg>',
          iconSize: [36, 36],
          iconAnchor: [18, 36],
          popupAnchor: [0, -32]
        });
      }

      function loadLeaflet(callback) {
        if (window.L) { callback(); return; }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = '{{ asset('css/leaflet.css') }}';
        document.head.appendChild(link);
        var s = document.createElement('script');
        s.src = '{{ asset('js/leaflet.js') }}';
        s.onload = callback;
        s.onerror = function () {
          document.getElementById('mapFallback').innerHTML =
            '<span>Map unavailable — check your internet connection.</span>';
        };
        document.head.appendChild(s);
      }

      var editing = false;
      var placeTimer = null;
      var placeCache = [];

      function initMap() {
        var fallback = document.getElementById('mapFallback');
        var mapEl = document.getElementById('orgMap');
        mapEl.style.display = 'block';
        fallback.style.display = 'none';
        map = L.map('orgMap').setView([ORG_LAT, ORG_LNG], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);
        map.on('click', function (e) {
          if (!editing) { showToast('Click “Edit Location” to change the pin.'); return; }
          placePin(e.latlng.lat, e.latlng.lng, false);
          reverseGeocode(e.latlng.lat, e.latlng.lng);
        });
        placePin(ORG_LAT, ORG_LNG, false);
      }

      function placePin(lat, lng, zoomTo) {
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng], { icon: makePin() }).addTo(map)
          .bindPopup('<b>Aquilala Irrigators Association, Inc.</b><br>San Francisco RIS, Brgy. Aquino, Bulan, Sorsogon');
        marker.openPopup();
        if (zoomTo) map.setView([lat, lng], 16);
      }

      function setOfficialAddress(name) {
        document.querySelector('[name=official_address]').value = name;
        document.getElementById('mapAddress').textContent = name;
      }

      function applyPlace(lat, lng, name, toastMsg) {
        placePin(lat, lng, true);
        setOfficialAddress(name);
        closeResults();
        if (toastMsg) showToast(toastMsg);
      }

      function reverseGeocode(lat, lng) {
        fetch('https://nominatim.openstreetmap.org/reverse?format=json&zoom=16&lat=' + lat + '&lon=' + lng)
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.display_name) {
              setOfficialAddress(data.display_name);
              showToast('Official address confirmed from pin.');
            }
          })
          .catch(function () { /* keep current address if reverse lookup fails */ });
      }

      document.getElementById('mapEditBtn').addEventListener('click', function () {
        editing = !editing;
        document.getElementById('mapWrap').classList.toggle('map-editing', editing);
        document.getElementById('mapEditBtn').classList.toggle('editing', editing);
        document.getElementById('mapEditBadge').style.display = editing ? 'flex' : 'none';
        document.getElementById('mapEditLabel').textContent = editing ? 'Done Editing' : 'Edit Location';
        showToast(editing ? 'Editing on — click the map to drop a pin.' : 'Editing off — map changes are locked.');
      });

      document.getElementById('resetBtn').addEventListener('click', function () {
        setDefaults();
        if (map) {
          setOfficialAddress('{{ $defaults["official_address"] }}');
          placePin(ORG_LAT, ORG_LNG, true);
        }
        showToast('Settings restored to defaults. Click Save to apply.');
      });

      function closeResults() {
        var box = document.getElementById('placeResults');
        box.classList.remove('show');
        box.innerHTML = '';
      }

      function renderResults(data, q) {
        var box = document.getElementById('placeResults');
        box.innerHTML = '';
        if (!data || !data.length) {
          box.innerHTML = '<div class="place-result" style="color:#94a3b8;cursor:default;">No matches found for “' + q + '”</div>';
          box.classList.add('show');
          placeCache = [];
          return;
        }
        placeCache = data;
        data.forEach(function (item, i) {
          var row = document.createElement('div');
          row.className = 'place-result';
          row.innerHTML = '<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
            '<div><div class="pr-name">' + item.name + '</div>' +
            '<div class="pr-sub">' + (item.display_name || item.name) + '</div></div>';
          row.addEventListener('click', function () {
            applyPlace(parseFloat(item.lat), parseFloat(item.lon), item.display_name,
              'Official address updated to “' + item.name + '”.');
            document.getElementById('placeInput').value = item.name;
          });
          box.appendChild(row);
        });
        box.classList.add('show');
      }

      function searchPlace(q, immediate) {
        var input = document.getElementById('placeInput');
        var btn = document.getElementById('placeSearchBtn');
        if (immediate) {
          btn.disabled = true;
          btn.textContent = 'Searching…';
        }
        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&addressdetails=1&countrycodes=ph&q=' +
              encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (immediate) {
              if (data && data.length) {
                applyPlace(parseFloat(data[0].lat), parseFloat(data[0].lon), data[0].display_name,
                  'Official address updated to “' + data[0].name + '”.');
                input.value = data[0].name;
              } else {
                showToast('Location not found. Try a more specific address.');
              }
            } else {
              renderResults(data, q);
            }
          })
          .catch(function () { showToast('Search unavailable — check your internet connection.'); })
          .finally(function () {
            if (immediate) { btn.disabled = false; btn.textContent = 'Search'; }
          });
      }

      document.getElementById('placeInput').addEventListener('input', function () {
        clearTimeout(placeTimer);
        var q = this.value.trim();
        if (q.length < 3) { closeResults(); return; }
        placeTimer = setTimeout(function () { searchPlace(q, false); }, 450);
      });

      document.getElementById('placeInput').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          if (placeCache.length) {
            var first = placeCache[0];
            this.value = first.name;
            applyPlace(parseFloat(first.lat), parseFloat(first.lon), first.display_name,
              'Official address updated to “' + first.name + '”.');
          } else {
            searchPlace(this.value.trim(), true);
          }
        }
        if (e.key === 'Escape') closeResults();
      });

      document.getElementById('placeSearchBtn').addEventListener('click', function () {
        var q = document.getElementById('placeInput').value.trim();
        if (!q) { showToast('Type a place to search.'); return; }
        searchPlace(q, true);
      });

      document.addEventListener('click', function (e) {
        if (!e.target.closest('.place-search') && !e.target.closest('.place-results')) closeResults();
      });

      loadLeaflet(initMap);
    })();
  </script>
@endsection