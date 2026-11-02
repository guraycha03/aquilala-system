@extends('layouts.sidebar')

@section('title', 'Add Formula')

@section('content')
<style>
  .form-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
  }
  .form-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
  }
  .form-card-body { padding: 24px; }

  .input-field {
    width: 100%; padding: 12px 14px; border: 1px solid #e2e8f0;
    border-radius: 10px; font-size: 14px; font-family: inherit;
    background: #ffffff; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .input-field:focus {
    outline: none; border-color: #648268;
    box-shadow: 0 0 0 3px rgba(100, 130, 104, 0.12);
  }
  select.input-field {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center;
    padding-right: 40px; cursor: pointer;
  }
  .input-label {
    display: block; font-size: 13px; font-weight: 700;
    color: #2c3530; margin-bottom: 6px;
  }
  .input-hint { font-size: 12px; color: #94a3b8; margin-top: 4px; }

  .btn-primary {
    background: #143d23; color: white; border: none;
    padding: 12px 24px; border-radius: 10px; font-size: 14px;
    font-weight: 700; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px;
    transition: background 0.2s;
  }
  .btn-primary:hover { background: #0d2917; }
  .btn-ghost {
    background: transparent; color: #475569; border: 1px solid #e2e8f0;
    padding: 12px 24px; border-radius: 10px; font-size: 14px;
    font-weight: 600; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.2s;
  }
  .btn-ghost:hover { background: #f8fafc; border-color: #cbd5e1; }

  .preview-box {
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    border-radius: 10px;
    padding: 16px 18px;
    font-size: 13px;
    color: #475569;
    margin-top: 8px;
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'Input Formulas', 'url' => route('store.formulas.index')],
  ['label' => 'Add Formula'],
]"/>

<div class="header-panel" style="margin-bottom: 24px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Add Input Formula</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">
    Define how much of a product is applied per hectare for a given crop and season.
  </p>
</div>

@if($errors->any())
  <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;">
    <ul style="margin: 0; padding-left: 20px; color: #dc2626; font-size: 14px;">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="form-card">
  <div class="form-card-header">
    <h2 style="font-size: 16px; font-weight: 700; color: #1e2922; margin: 0;">Formula Details</h2>
    <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Base application rate for one (1) hectare of land.</p>
  </div>

  <form action="{{ route('store.formulas.store') }}" method="POST" class="form-card-body">
    @csrf

    @php
      $cropChoices = collect($compatibleCrops ?? [])
        ->merge($existingCrops ?? [])
        ->map(fn ($crop) => trim((string) $crop))
        ->filter()
        ->unique()
        ->sort()
        ->values();
    @endphp

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
      <div>
        <label for="crop_type" class="input-label">Crop Type</label>
        <input type="text" name="crop_type" id="crop_type" class="input-field"
               list="crop-choices" placeholder="e.g. Rice"
               value="{{ old('crop_type') }}" required>
        <datalist id="crop-choices">
          @foreach($cropChoices as $crop)
            <option value="{{ $crop }}">{{ $crop }}</option>
          @endforeach
        </datalist>
        <div class="input-hint">Crops listed are those Aquilala already recognises for Bulan, Sorsogon. Pick one or type a new profile.</div>
      </div>

      <div>
        <label for="season" class="input-label">Season</label>
        <select name="season" id="season" class="input-field" required>
          <option value="">— Select season —</option>
          <option value="wet" @selected(old('season') === 'wet')>Wet Season (June – January)</option>
          <option value="dry" @selected(old('season') === 'dry')>Dry Season (February – May)</option>
        </select>
        <div class="input-hint">Input requirements differ between wet and dry cropping cycles.</div>
      </div>

      <div>
        <label for="stage" class="input-label">Application Stage</label>
        <select name="stage" id="stage" class="input-field" required>
          @foreach(\App\Models\DecisionFormula::STAGES as $key => $label)
            <option value="{{ $key }}" @selected(old('stage', 'standard') === $key)>{{ $label }}</option>
          @endforeach
        </select>
        <div class="input-hint">Stage rows for the same crop + season + product are summed by the engine.</div>
      </div>
    </div>

    @php
      $selectedProduct = \App\Models\Product::find(old('product_id', request('product_id')));
    @endphp

    @if($selectedProduct)
      <div style="margin-top: 20px;">
        <label class="input-label">Product</label>
        <div class="input-field" style="display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f8fafc;color:#1e2922;">
          <span>{{ $selectedProduct->name }}</span>
          <span style="color:#64748b;font-size:12px;">{{ $selectedProduct->package_unit ?? $selectedProduct->stock_unit ?? '' }}</span>
        </div>
        <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
        <input type="hidden" name="unit" value="{{ old('unit', $selectedProduct->package_unit ?? $selectedProduct->stock_unit ?? '') }}">
        <div class="input-hint">Selected from your catalog. Unit is taken from the product.</div>
      </div>
    @else
      <div style="margin-top: 20px;">
        <label for="product_id" class="input-label">Product</label>
        <select name="product_id" id="product_id" class="input-field" required>
          <option value="">— Select product from your catalog —</option>
          @foreach($products->groupBy(fn($p) => $p->category_display_name) as $categoryName => $categoryProducts)
            <optgroup label="{{ $categoryName }}">
              @foreach($categoryProducts as $product)
                <option value="{{ $product->id }}" @selected(old('product_id', request('product_id')) == $product->id)>
                  {{ $product->display_name }} — ₱{{ number_format($product->price ?? 0, 2) }}{{ $product->price_unit ? ' / ' . $product->price_unit : '' }}
                </option>
              @endforeach
            </optgroup>
          @endforeach
        </select>
        <div class="input-hint">Only active catalog products can be referenced by the recommendation engine.</div>
      </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
      <div>
        <label for="rate_per_hectare" class="input-label">Application Rate / Hectare</label>
        <input type="number" name="rate_per_hectare" id="rate_per_hectare" class="input-field"
               placeholder="e.g., 2.00" step="0.01" min="0.01" max="999.99"
               value="{{ old('rate_per_hectare') }}" required>
        <div class="input-hint">Recommended quantity = this rate × land area.</div>
      </div>

      <div>
        <label for="unit" class="input-label">Unit</label>
        @if($selectedProduct ?? null)
          <div class="input-field" style="background:#f8fafc;color:#1e2922;">{{ old('unit', $selectedProduct->package_unit ?? $selectedProduct->stock_unit ?? '-') }}</div>
          <div class="input-hint">Taken from the product and cannot be changed here.</div>
        @else
          <input type="text" name="unit" id="unit" class="input-field"
                 list="unit-suggestions" placeholder="Bags" value="{{ old('unit') }}" required>
          <datalist id="unit-suggestions">
            <option value="Bags"></option>
            <option value="Sacks"></option>
            <option value="Kilograms"></option>
            <option value="Liters"></option>
            <option value="Pieces"></option>
            <option value="Sachets"></option>
            <option value="Bottles"></option>
            <option value="Boxes"></option>
          </datalist>
          <div class="input-hint">The unit this rate is expressed in.</div>
        @endif
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
      <div>
        <label for="min_rate_per_hectare" class="input-label">Minimum Rate <span style="color: #94a3b8; font-weight: 400;">(safety bound)</span></label>
        <input type="number" name="min_rate_per_hectare" id="min_rate_per_hectare" class="input-field"
               placeholder="e.g., 1.00" step="0.01" min="0.01" max="999.99"
               value="{{ old('min_rate_per_hectare') }}">
        <div class="input-hint">Minimum safety rate (optional).</div>
      </div>

      <div>
        <label for="max_rate_per_hectare" class="input-label">Maximum Rate <span style="color: #94a3b8; font-weight: 400;">(safety bound)</span></label>
        <input type="number" name="max_rate_per_hectare" id="max_rate_per_hectare" class="input-field"
               placeholder="e.g., 4.00" step="0.01" min="0.01" max="999.99"
               value="{{ old('max_rate_per_hectare') }}">
        <div class="input-hint">Maximum safety rate (optional).</div>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
      <div>
        <label class="input-label">Soil Type (optional)</label>
        <select disabled class="input-field">
          <option>Loam</option><option>Clay</option><option>Sandy</option><option>Clay loam</option><option>Peat</option>
        </select>
        <div class="input-hint">For future rule matching (display only).</div>
      </div>
      <div>
        <label class="input-label">Weather risk (optional)</label>
        <select disabled class="input-field">
          <option>Normal</option><option>Low rain</option><option>Heavy rain</option><option>Drought</option><option>Typhoon</option>
        </select>
        <div class="input-hint">For future rule matching (display only).</div>
      </div>
    </div>

    <div style="margin-top: 20px;">
      <label for="notes" class="input-label">Notes <span style="color: #94a3b8; font-weight: 400;">(optional)</span></label>
      <textarea name="notes" id="notes" class="input-field" rows="3"
                placeholder="e.g., Flood-tolerant seed variety for wet season rice.">{{ old('notes') }}</textarea>
      <div class="input-hint">Explains the rule so farmers see why this quantity is recommended.</div>
    </div>

    <div class="preview-box" id="preview">
      Recommended quantity = <strong>—</strong> × land area. Select a product and enter a rate to preview.
    </div>

    <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
      <a href="{{ route('store.formulas.index') }}" class="btn-ghost">Cancel</a>
      <button type="submit" class="btn-primary">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
        Save Formula
  </button>
  @if(app(\App\AI\AiLlmClient::class)->available())
  <button type="button" class="btn-ghost" id="aiFillBtn" style="margin-left:10px;">
    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2l3 6 6 3-6 3-3 6-3-6-6-3 6-3z"/></svg>
    Add Rule with AI
  </button>
  @endif
    </div>
  </form>
</div>

<script>
(function () {
  const rateInput = document.getElementById('rate_per_hectare');
  const unitInput = document.getElementById('unit');
  const productSelect = document.getElementById('product_id');
  const preview = document.getElementById('preview');

  function updatePreview() {
    const rate = parseFloat(rateInput.value);
    const unit = unitInput.value.trim();
    const productText = productSelect.selectedOptions[0] ? productSelect.selectedOptions[0].text.split(' — ')[0] : 'the selected product';

    if (isNaN(rate) || rate <= 0) {
      preview.innerHTML = 'Recommended quantity = <strong>—</strong> × land area. Enter a rate to preview.';
      return;
    }
    const one = rate;
    const half = (rate * 0.5).toFixed(2);
    const two = (rate * 2).toFixed(2);
    preview.innerHTML =
      'Recommended quantity = <strong>' + rate.toFixed(2) + '</strong> ' + (unit || 'pieces') + ' × land area.<br>' +
      'Example: <strong>' + productText + '</strong> for a 1 ha farm = ' + one.toFixed(2) + ' ' + (unit || 'pieces') +
      ', for 0.5 ha = ' + half + ' ' + (unit || 'pieces') + ', for 2 ha = ' + two + ' ' + (unit || 'pieces') + '.';
  }

  rateInput.addEventListener('input', updatePreview);
  unitInput.addEventListener('input', updatePreview);
  productSelect.addEventListener('change', updatePreview);
  updatePreview();

  const aiFillBtn = document.getElementById('aiFillBtn');
  if (aiFillBtn) {
    aiFillBtn.addEventListener('click', async function () {
      const productInput = document.querySelector('input[name="product_id"]');
      const productSelect = document.getElementById('product_id');
      const productId = productInput ? productInput.value : (productSelect ? productSelect.value : '');
      if (!productId) {
        window.alert('Choose a product first.');
        return;
      }
      aiFillBtn.disabled = true;
      aiFillBtn.textContent = 'Filling...';
      try {
        const resp = await fetch('{{ route('store.formulas.ai-suggest') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ product_id: productId }),
        });
        const data = await resp.json();
        if (!resp.ok) throw new Error(data.error || 'AI failed');

        if (data.crop_type) document.getElementById('crop_type').value = data.crop_type;
        if (data.season) document.getElementById('season').value = data.season;
        if (data.stage) document.getElementById('stage').value = data.stage;
        if (data.rate_per_hectare) document.getElementById('rate_per_hectare').value = data.rate_per_hectare;
        if (data.unit) {
          document.getElementById('unit').value = data.unit;
          var hiddenUnit = document.querySelector('input[name="unit"][type="hidden"]');
          if (hiddenUnit) hiddenUnit.value = data.unit;
        }
        if (data.min_rate_per_hectare) document.getElementById('min_rate_per_hectare').value = data.min_rate_per_hectare;
        if (data.max_rate_per_hectare) document.getElementById('max_rate_per_hectare').value = data.max_rate_per_hectare;
        if (data.notes) document.getElementById('notes').value = data.notes;
        updatePreview();
      } catch (err) {
        window.alert('AI could not fill the form: ' + (err.message || 'unknown error'));
      } finally {
        aiFillBtn.disabled = false;
        aiFillBtn.textContent = 'Add Rule with AI';
      }
    });
  }
})();
</script>
@endsection
