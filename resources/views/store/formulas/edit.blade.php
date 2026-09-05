@extends('layouts.sidebar')

@section('title', 'Edit Formula')

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
    outline: none; border-color: #143d23;
    box-shadow: 0 0 0 3px rgba(20, 61, 35, 0.1);
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
  ['label' => 'Edit Formula'],
]"/>

<div class="header-panel" style="margin-bottom: 24px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Edit Input Formula</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">
    Update the per-hectare application rate for <strong>{{ $formula->crop_type }}</strong> — {{ ucfirst($formula->season) }} season.
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

  <form action="{{ route('store.formulas.update', $formula) }}" method="POST" class="form-card-body">
    @csrf @method('PUT')

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      <div>
        <label for="crop_type" class="input-label">Crop Type</label>
        <input type="text" name="crop_type" id="crop_type" class="input-field"
               list="existing-crops" placeholder="e.g., Rice, Corn, Sweet Corn"
               value="{{ old('crop_type', $formula->crop_type) }}" required>
        <datalist id="existing-crops">
          @foreach($existingCrops as $crop)
            <option value="{{ $crop }}">{{ $crop }}</option>
          @endforeach
        </datalist>
        <div class="input-hint">Changing the crop moves this rule to a different profile.</div>
      </div>

      <div>
        <label for="season" class="input-label">Season</label>
        <select name="season" id="season" class="input-field" required>
          <option value="">— Select season —</option>
          <option value="wet" @selected(old('season', $formula->season) === 'wet')>Wet Season (June – October)</option>
          <option value="dry" @selected(old('season', $formula->season) === 'dry')>Dry Season (November – May)</option>
        </select>
        <div class="input-hint">Input requirements differ between wet and dry cropping cycles.</div>
      </div>
    </div>

    <div style="margin-top: 20px;">
      <label for="product_id" class="input-label">Product</label>
      <select name="product_id" id="product_id" class="input-field" required>
        <option value="">— Select product from your catalog —</option>
        @foreach($products->groupBy(fn($p) => $p->category->name ?? 'General') as $categoryName => $categoryProducts)
          <optgroup label="{{ $categoryName }}">
            @foreach($categoryProducts as $product)
              <option value="{{ $product->id }}" @selected(old('product_id', $formula->product_id) == $product->id)>
                {{ $product->name }} — ₱{{ number_format($product->price ?? 0, 2) }}{{ $product->price_unit ? ' / ' . $product->price_unit : '' }}
              </option>
            @endforeach
          </optgroup>
        @endforeach
      </select>
      <div class="input-hint">Only active catalog products can be referenced by the recommendation engine.</div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
      <div>
        <label for="rate_per_hectare" class="input-label">Application Rate / Hectare</label>
        <input type="number" name="rate_per_hectare" id="rate_per_hectare" class="input-field"
               placeholder="e.g., 2.00" step="0.01" min="0.01" max="999.99"
               value="{{ old('rate_per_hectare', $formula->rate_per_hectare) }}" required>
        <div class="input-hint">Recommended quantity = this rate × land area.</div>
      </div>

      <div>
        <label for="unit" class="input-label">Unit</label>
        <input type="text" name="unit" id="unit" class="input-field"
               list="unit-suggestions" placeholder="e.g., Bags" value="{{ old('unit', $formula->unit) }}" required>
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
        <div class="input-hint">The unit this rate is expressed in (e.g., Bags per hectare).</div>
      </div>
    </div>

    <div style="margin-top: 20px;">
      <label for="notes" class="input-label">Notes <span style="color: #94a3b8; font-weight: 400;">(optional)</span></label>
      <textarea name="notes" id="notes" class="input-field" rows="3"
                placeholder="e.g., Flood-tolerant seed variety for wet season rice.">{{ old('notes', $formula->notes) }}</textarea>
      <div class="input-hint">Explains the rule so farmers see why this quantity is recommended.</div>
    </div>

    <div class="preview-box" id="preview">
      Recommended quantity = <strong>{{ number_format((float) $formula->rate_per_hectare, 2) }}</strong> {{ $formula->unit }} × land area.
    </div>

    <div style="display: flex; gap: 12px; margin-top: 24px; justify-content: flex-end;">
      <a href="{{ route('store.formulas.index') }}" class="btn-ghost">Cancel</a>
      <button type="submit" class="btn-primary">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
        Save Changes
      </button>
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
})();
</script>
@endsection
