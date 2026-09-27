@props([
    'name' => '',
    'size' => null,
    'unit' => null,
])

{{-- Live preview of the catalog card title: plain name + the measure that
     the display_name accessor appends server-side. --}}

@once
<style>
  .name-preview-box {
    display: flex;
    align-items: baseline;
    gap: 10px;
    flex-wrap: wrap;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    padding: 10px 14px;
  }
  .name-preview-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
  }
  .name-preview-value {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
  }
  .name-preview-value.is-empty { font-weight: 400; color: #94a3b8; }
</style>

<script>
(function () {
  if (window.aquiNamePreviewReady) return;
  window.aquiNamePreviewReady = true;

  // Mirrors Product::normalizeUnit() so the preview matches what the server
  // renders in display_name.
  var UNITS = {
    'kg': 'kg', 'kgs': 'kg', 'kilogram': 'kg', 'kilograms': 'kg',
    'g': 'g', 'gram': 'g', 'grams': 'g',
    'ml': 'mL',
    'l': 'L', 'ltr': 'L', 'liter': 'L', 'liters': 'L', 'litre': 'L', 'litres': 'L',
    'piece': 'piece', 'pieces': 'piece',
    'pcs': 'pcs'
  };

  function formatSize(raw) {
    if (raw === null || raw === undefined) return null;
    var value = parseFloat(raw);
    if (isNaN(value)) return null;
    var text = value.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    return text === '' ? '0' : text;
  }

  function update() {
    var output = document.querySelector('[data-name-preview]');
    if (!output) return;

    var nameField = document.getElementById('name');
    var sizeField = document.getElementById('package_size');
    var unitField = document.getElementById('package_unit');

    var name = nameField ? nameField.value.trim() : '';
    var size = formatSize(sizeField ? sizeField.value : '');
    var unit = unitField ? unitField.value.trim() : '';
    var label = unit ? (UNITS[unit.toLowerCase()] || unit) : null;

    var measure = size === null ? null : (label ? size + ' ' + label : size);
    var composed = measure === null ? name : (name === '' ? measure : name + ' — ' + measure);

    output.textContent = composed;
    output.classList.toggle('is-empty', composed === '');
  }

  document.addEventListener('DOMContentLoaded', function () {
    ['name', 'package_size', 'package_unit'].forEach(function (fieldId) {
      var field = document.getElementById(fieldId);
      if (!field) return;
      field.addEventListener('input', update);
      field.addEventListener('change', update);
    });
    update();
  });
})();
</script>
@endonce

<div class="form-group full-width">
    <div class="name-preview-box">
        <span class="name-preview-label">How it will appear in the catalog</span>
        <span class="name-preview-value is-empty" data-name-preview>{{ trim($name) }}</span>
    </div>
    <span class="hint">The size and unit are appended automatically, so search and compact lists can use the plain name on its own.</span>
</div>
