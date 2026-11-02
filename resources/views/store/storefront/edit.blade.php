@extends('layouts.sidebar')

@section('title', 'Edit Product')

@section('content')

<style>
    /* Page Layout & Container */
    .form-container { 
        width: 100%;
        max-width: 980px; 
        margin: 0 auto; 
        padding: 0 20px 48px;
        box-sizing: border-box;
    }

    .page-header {
        margin-bottom: 32px;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px 0;
        letter-spacing: -0.02em;
        line-height: 1.25;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        margin: 0;
        line-height: 1.5;
    }

    /* Modern Card Layout */
    .form-card { 
        background: #ffffff; 
        border: 1px solid #e2e8f0; 
        border-radius: 16px; 
        padding: 36px; 
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.04);
        margin-bottom: 32px;
        box-sizing: border-box;
    }

    /* Spacious 12-Column Grid System */
    .form-grid { 
        display: grid; 
        grid-template-columns: repeat(12, 1fr); 
        gap: 28px 24px; /* Increased row gap to 28px */
        width: 100%;
    }

    .col-12 { grid-column: span 12 / span 12; }
    .col-8  { grid-column: span 8 / span 8; }
    .col-6  { grid-column: span 6 / span 6; }
    .col-4  { grid-column: span 4 / span 4; }
    .col-3  { grid-column: span 3 / span 3; }

    .form-group { 
        display: flex; 
        flex-direction: column; 
        gap: 8px; /* Increased label to input gap */
        width: 100%;
        margin-bottom: 4px;
    }

    /* Section Details & Summary Styling */
    details.form-section { 
        display: block; 
        grid-column: span 12 / span 12; 
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }
    
    details.form-section > summary.section-header { 
        cursor: pointer; 
        padding: 18px 24px; 
        font-size: 1.05rem; 
        font-weight: 700; 
        color: #14532d; 
        background: #fafafa; 
        display: flex; 
        align-items: center; 
        gap: 12px; 
        list-style: none;
        user-select: none;
        transition: all 0.2s ease;
        margin: 0;
    }

    details.form-section > summary.section-header:hover {
        background: #f0fdf4;
        color: #166534;
    }

    details.form-section > summary::-webkit-details-marker { display: none; }
    details.form-section > summary::marker { content: ""; }

    details.form-section[open] > summary.section-header {
        border-bottom: 1px solid #e2e8f0;
        background: #f0fdf4;
    }

    details.form-section[open] > summary .rotate-icon {
        transform: rotate(180deg);
    }

    .rotate-icon {
        transition: transform 0.25 ease;
        margin-left: auto;
    }

    .section-content {
        padding: 32px 28px; /* Roomy content area inside cards */
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 28px 24px;
    }

    /* Field Labels & Inputs */
    .form-group label { 
        font-size: 0.875rem; 
        font-weight: 600; 
        color: #1e293b; 
        letter-spacing: -0.01em;
    }

    .required-star {
        color: #e11d48;
    }

    .form-control {
        min-height: 48px; /* Taller, more clickable controls */
        padding: 12px 16px; 
        border: 1px solid #cbd5e1; 
        border-radius: 10px; 
        font-size: 0.9375rem; 
        color: #0f172a;
        background-color: #ffffff; 
        transition: all 0.2s ease; 
        font-family: inherit;
        width: 100%;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none; 
        border-color: #166534; 
        box-shadow: 0 0 0 4px rgba(22, 101, 52, 0.1);
    }

    textarea.form-control { 
        resize: vertical; 
        min-height: 120px; 
        line-height: 1.5;
    }

    .hint { 
        font-size: 0.8125rem; 
        color: #64748b; 
        margin-top: 4px;
        line-height: 1.45;
    }

    /* Custom Input Addons */
    .input-addon-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .input-addon-wrapper .addon-symbol {
        position: absolute;
        left: 16px;
        color: #64748b;
        font-size: 0.95rem;
        font-weight: 600;
        pointer-events: none;
    }

    .input-addon-wrapper input {
        padding-left: 36px;
    }

    /* Validation Alerts */
    .alert-error {
        background: #fef2f2; 
        border: 1px solid #fecaca; 
        border-radius: 12px; 
        padding: 18px 24px; 
        margin-bottom: 28px;
    }

    .alert-error ul {
        list-style: none; 
        padding: 0; 
        margin: 0; 
        color: #991b1b; 
        font-size: 0.875rem;
    }

    .alert-error li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 8px;
    }

    .alert-error li:last-child {
        margin-bottom: 0;
    }

    .alert-success {
        background: #f0fdf4; 
        border: 1px solid #bbf7d0; 
        border-radius: 12px; 
        padding: 18px 24px; 
        margin-bottom: 28px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #166534;
        font-size: 0.875rem;
        font-weight: 600;
    }

    /* Action Buttons */
    .btn-row { 
        display: flex; 
        gap: 14px; 
        align-items: center; 
        justify-content: flex-end;
        margin-top: 36px; 
        padding-top: 24px;
        border-top: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }

    .btn-submit {
        min-height: 48px;
        background: #14532d; 
        color: #ffffff; 
        padding: 12px 28px; 
        border: none; 
        border-radius: 10px;
        font-size: 0.9375rem; 
        font-weight: 600; 
        cursor: pointer; 
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 2px 4px rgba(20, 83, 45, 0.12);
    }

    .btn-submit:hover { 
        background: #166534; 
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(20, 83, 45, 0.2);
    }

    .btn-cancel {
        min-height: 48px;
        background: transparent; 
        color: #475569; 
        padding: 12px 28px; 
        border: 1px solid #cbd5e1; 
        border-radius: 10px;
        font-size: 0.9375rem; 
        font-weight: 600; 
        cursor: pointer; 
        text-decoration: none; 
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-cancel:hover { 
        background: #f8fafc; 
        color: #0f172a;
        border-color: #94a3b8;
    }

    /* Existing Image Gallery Manager */
    .existing-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 16px;
        margin-top: 12px;
        margin-bottom: 14px;
    }

    .existing-gallery-item {
        position: relative;
        aspect-ratio: 1 / 1;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        background: #f8fafc;
    }

    .existing-gallery-item img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        display: block; 
    }

    .existing-gallery-tag {
        position: absolute; 
        top: 8px; 
        left: 8px;
        font-size: 0.65rem; 
        font-weight: 700; 
        letter-spacing: 0.04em;
        background: #166534; 
        color: #ffffff;
        padding: 4px 8px; 
        border-radius: 6px;
        z-index: 2;
    }

    .existing-gallery-actions {
        position: absolute; 
        left: 0; 
        right: 0; 
        bottom: 0;
        display: flex; 
        gap: 6px; 
        justify-content: center;
        padding: 10px 8px;
        background: linear-gradient(to top, rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0));
    }

    .gallery-btn {
        display: inline-flex; 
        align-items: center; 
        justify-content: center;
        border: none; 
        border-radius: 6px; 
        cursor: pointer;
        font-size: 0.75rem; 
        font-weight: 700; 
        font-family: inherit;
        padding: 6px 10px;
        transition: all 0.2s;
    }

    .gallery-btn svg { 
        width: 14px; 
        height: 14px; 
        stroke: currentColor; 
        fill: none; 
        stroke-width: 2; 
        stroke-linecap: round; 
        stroke-linejoin: round; 
    }

    .gallery-btn-main { background: #ffffff; color: #166534; flex: 1; }
    .gallery-btn-main:hover { background: #dcfce7; }
    .gallery-btn-delete { background: #ffffff; color: #e11d48; }
    .gallery-btn-delete:hover { background: #ffe4e6; color: #be123c; }

    /* Breakpoints for Responsive Behavior */
    @media (max-width: 1024px) {
        .col-4 { grid-column: span 6 / span 6; }
        .col-3 { grid-column: span 6 / span 6; }
    }

    @media (max-width: 768px) {
        .form-container { padding: 0 14px 28px; }
        .form-card { padding: 24px 18px; border-radius: 14px; }
        .section-content { padding: 20px 16px; gap: 20px; }

        .col-8, .col-6, .col-4, .col-3 { grid-column: span 12 / span 12; }

        .btn-row { 
            flex-direction: column-reverse; 
            gap: 12px; 
            margin-top: 24px; 
            padding-top: 18px; 
        }

        .btn-submit, .btn-cancel { 
            width: 100%; 
            justify-content: center; 
        }

        .existing-gallery { 
            grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); 
            gap: 12px;
        }
    }

    @media (max-width: 480px) {
        .page-title { font-size: 1.4rem; }
        .page-subtitle { font-size: 0.875rem; }
        .existing-gallery { grid-template-columns: repeat(2, 1fr); }
    }
    /* Restored image upload styling */
    .image-management-wrapper { display: flex; gap: 20px; align-items: center; }
    .current-image-card { display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .img-preview { width: 110px; height: 110px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc; }
    .file-dropzone { flex: 1; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 24px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s ease; }
    .file-dropzone:hover { border-color: #166534; background: #f0fdf4; }
    .file-dropzone svg { color: #64748b; margin-bottom: 8px; }
    .img-picker-previews { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 10px; margin-bottom: 14px; }
    .img-picker-previews:empty { display: none; }
    .img-picker-item { position: relative; aspect-ratio: 1 / 1; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; background: #f8fafc; }
    .img-picker-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .img-picker-remove { position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.72); color: #fff; border: none; border-radius: 50%; font-size: 15px; line-height: 1; cursor: pointer; transition: background 0.2s; }
    .img-picker-remove:hover { background: #e11d48; }
    .img-picker-remove[hidden] { display: none; }
    .img-picker-tag { position: absolute; left: 0; bottom: 0; font-size: 10px; font-weight: 700; letter-spacing: 0.02em; background: rgba(15, 23, 42, 0.72); color: #fff; padding: 3px 7px; border-top-right-radius: 8px; }
    .img-picker-summary { font-size: 12px; font-weight: 600; color: #166534; margin-bottom: 10px; }
    .img-picker-summary:empty { display: none; }
    .product-tabs { display: flex; gap: 8px; border-bottom: 1px solid #e2e8f0; margin-bottom: 24px; }
    .product-tab { padding: 12px 18px; color: #64748b; text-decoration: none; font-weight: 700; border-bottom: 3px solid transparent; }
    .product-tab.active { color: #14532d; border-bottom-color: #14532d; }
    .rule-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 18px; }
    .rule-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .rule-field { display: flex; flex-direction: column; gap: 6px; }
    .rule-field label { font-size: .82rem; font-weight: 700; color: #334155; }
    .rule-field input, .rule-field select, .rule-field textarea { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit; }
    @media (max-width: 720px) { .rule-grid { grid-template-columns: 1fr; } }
</style>

<x-breadcrumb :items="[
  ['label' => 'My Store', 'url' => route('store.storefront.index')],
  ['label' => 'Edit Product'],
]"/>

<div class="form-container">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Edit Product</h1>
        <p class="page-subtitle">Update details, pricing, and inventory status for <strong>{{ $product->name }}</strong>.</p>
    </div>

    <!-- Error Summary -->
    @if ($errors->any())
    <div class="alert-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:3px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span>{{ $error }}</span>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Success Message -->
    @if (session('success'))
    <div class="alert-success">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <nav class="product-tabs" aria-label="Product sections">
        <a class="product-tab {{ $tab === 'details' ? 'active' : '' }}" href="{{ route('store.products.edit', $product) }}">Edit Product</a>
        <a class="product-tab {{ $tab === 'rules' ? 'active' : '' }}" href="{{ route('store.products.edit', ['product' => $product, 'tab' => 'rules']) }}">Application Rules</a>
    </nav>

    @php
        $ruleCrops = collect(app(\App\Services\CropLibraryService::class)->offeredNames())
            ->merge($formulas->pluck('crop_type'))
            ->unique()
            ->sort()
            ->values();
    @endphp

    @if($tab === 'details')

    @php
        $existingImages = $product->gallery;
        $slotsLeft = max(0, \App\Models\Product::MAX_IMAGES - $existingImages->count());
    @endphp

    @if($existingImages->isNotEmpty())
        <div class="form-card">
            <div class="section-header" style="margin-bottom: 8px; font-weight: 700; font-size: 1.05rem; color: #14532d; display: flex; align-items: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Product Photos <span style="font-weight: 400; font-size: 0.8125rem; color: #64748b;">({{ $existingImages->count() }} uploaded)</span>
            </div>
            <div class="existing-gallery">
                @foreach($existingImages as $image)
                    <div class="existing-gallery-item">
                        <img src="{{ $image->thumb_url }}" alt="{{ $product->name }}" loading="lazy">
                        @if($image->image_type === 'main')
                            <span class="existing-gallery-tag">MAIN</span>
                        @endif
                        <div class="existing-gallery-actions">
                            @if($image->image_type !== 'main')
                                <form method="POST" action="{{ route('store.products.images.main', [$product->id, $image->id]) }}" style="margin:0;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="gallery-btn gallery-btn-main" title="Use as cover photo">Set main</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('store.products.images.destroy', [$product->id, $image->id]) }}" style="margin:0;"
                                  data-aqui-confirm="Remove this image from the product?" data-aqui-danger data-aqui-confirm-label="Remove">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="gallery-btn gallery-btn-delete" title="Remove image">
                                    <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            <span class="hint">The cover photo is what catalog cards show. Removing the cover promotes the next image automatically.</span>
        </div>
    @endif

    <!-- Main Form -->
    <div class="form-card">
        <form method="POST" action="{{ route('store.products.update', $product->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-grid">
                
                <!-- SECTION 1: BASIC INFORMATION -->
                <details class="form-section" open>
                    <summary class="section-header">
                        <i data-lucide="info" style="width:18px;height:18px;"></i>
                        <span>Basic Information</span>
                        <i data-lucide="chevron-down" class="rotate-icon" style="width:18px;height:18px;"></i>
                    </summary>
                    <div class="section-content">
                        <div class="form-group col-12">
                            <label for="name">Product Name <span class="required-star">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" placeholder="e.g. Urea Fertilizer (46-0-0)" maxlength="255" required>
                            <span class="hint">Just the product name — leave out the weight or volume, that goes in the size field below.</span>
                        </div>

                        <div class="form-group col-6">
                            <label for="brand">Brand / Manufacturer</label>
                            <input type="text" class="form-control" id="brand" name="brand" value="{{ old('brand', $product->brand) }}" placeholder="e.g. Bayer, Syngenta">
                        </div>

                        <div class="form-group col-6">
                            <label for="sku">SKU / Class Code <span class="required-star">*</span></label>
                            <input type="text" class="form-control" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="e.g. FERT-UREA-50KG" required>
                            <span class="hint">Must be unique across all catalog products.</span>
                        </div>

                        <div class="form-group col-6">
                            <label for="category_id">Category <span class="required-star">*</span></label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="" disabled>Select category</option>
                                @foreach($categories as $parent)
                                    <optgroup label="{{ $parent->name }}">
                                        @foreach($parent->children as $child)
                                            <option value="{{ $child->id }}" {{ old('category_id', $product->category_id) == $child->id ? 'selected' : '' }}>
                                                {{ $child->name }}
                                            </option>
                                        @endforeach
                                        @if($parent->children->count() === 0)
                                            <option value="{{ $parent->id }}" {{ old('category_id', $product->category_id) == $parent->id ? 'selected' : '' }}>
                                                {{ $parent->name }}
                                            </option>
                                        @endif
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-6">
                            <label for="product_type">Product Type</label>
                            <input type="text" class="form-control" id="product_type" name="product_type" value="{{ old('product_type', $product->product_type) }}" placeholder="e.g. Premium NPK Compound">
                        </div>

                        <div class="form-group col-12">
                            <label for="recommended_season">Recommended Season <span class="required-star">*</span></label>
                            <select class="form-control" id="recommended_season" name="recommended_season" required>
                                <option value="wet" {{ old('recommended_season', $product->recommended_season) == 'wet' ? 'selected' : '' }}>Wet Season</option>
                                <option value="dry" {{ old('recommended_season', $product->recommended_season) == 'dry' ? 'selected' : '' }}>Dry Season</option>
                                <option value="both" {{ old('recommended_season', $product->recommended_season) == 'both' ? 'selected' : '' }}>All Seasons</option>
                            </select>
                        </div>

                        <div class="form-group col-12">
                            <label for="crop_types">Recommended Crop Types</label>
                            <select class="form-control" id="crop_types" name="crop_types[]" multiple size="6">
                                @php($selectedCropTypes = old('crop_types', $product->cropTypes->pluck('crop_type')->all()))
                                @foreach($cropTypes as $cropType)
                                    <option value="{{ $cropType }}" {{ in_array($cropType, $selectedCropTypes, true) ? 'selected' : '' }}>
                                        {{ $cropType }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="hint">Hold Ctrl (Windows) or Command (Mac) to select multiple crops. Products selected here can be recommended in farmer cost quotations for these crops.</span>
                        </div>

                        <div class="form-group col-12">
                            <label for="description">Product Description</label>
                            <textarea class="form-control" id="description" name="description" placeholder="Describe the product, usage, benefits, and application notes...">{{ old('description', $product->description) }}</textarea>
                            <span class="hint">Provide details that help farmers make informed purchasing decisions.</span>
                        </div>
                    </div>
                </details>

                <!-- SECTION 2: PACKAGING & PRICING -->
                <details class="form-section">
                    <summary class="section-header">
                        <i data-lucide="philippine-peso" style="width:18px;height:18px;"></i>
                        <span>Packaging & Pricing</span>
                        <i data-lucide="chevron-down" class="rotate-icon" style="width:18px;height:18px;"></i>
                    </summary>
                    <div class="section-content">
                        <div class="form-group col-4">
                            <label for="package_size">Size / Measure</label>
                            <input type="number" class="form-control" id="package_size" name="package_size" value="{{ old('package_size', $product->package_size) }}" placeholder="e.g. 50" step="0.01" min="0">
                        </div>

                        <div class="form-group col-4">
                            <label for="package_unit">Unit</label>
                            <input type="text" class="form-control" id="package_unit" name="package_unit" value="{{ old('package_unit', $product->package_unit) }}" placeholder="e.g. kg, L, bag" list="package_units" maxlength="50">
                            <datalist id="package_units">
                                @foreach(\App\Models\Product::sizeUnitSuggestions() as $unit)
                                    <option value="{{ $unit }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="form-group col-4">
                            <label for="packaging_type">Packaging Type</label>
                            <select class="form-control" id="packaging_type" name="packaging_type">
                                <option value="">Select packaging type</option>
                                <option value="sack" {{ old('packaging_type', $product->packaging_type) == 'sack' ? 'selected' : '' }}>Sack</option>
                                <option value="bottle" {{ old('packaging_type', $product->packaging_type) == 'bottle' ? 'selected' : '' }}>Bottle</option>
                                <option value="box" {{ old('packaging_type', $product->packaging_type) == 'box' ? 'selected' : '' }}>Box</option>
                                <option value="drum" {{ old('packaging_type', $product->packaging_type) == 'drum' ? 'selected' : '' }}>Drum</option>
                                <option value="can" {{ old('packaging_type', $product->packaging_type) == 'can' ? 'selected' : '' }}>Can</option>
                                <option value="bag" {{ old('packaging_type', $product->packaging_type) == 'bag' ? 'selected' : '' }}>Bag</option>
                                <option value="other" {{ old('packaging_type', $product->packaging_type) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="form-group col-6">
                            <label for="price">Selling Price <span class="required-star">*</span></label>
                            <div class="input-addon-wrapper">
                                <span class="addon-symbol">₱</span>
                                <input type="number" class="form-control" id="price" name="price" value="{{ old('price', $product->price) }}" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="form-group col-6">
                            <label for="price_unit">Price Unit Description</label>
                            <input type="text" class="form-control" id="price_unit" name="price_unit" value="{{ old('price_unit', $product->price_unit) }}" placeholder="e.g. Per Sack, Per Kg, Per Liter">
                        </div>

                        <div class="col-12" style="margin-top: 8px;">
                            <x-product-name-preview
                                :name="old('name', $product->name)"
                                :size="old('package_size', $product->package_size)"
                                :unit="old('package_unit', $product->package_unit)"
                            />
                        </div>
                    </div>
                </details>

                <!-- SECTION 3: INVENTORY & STATUS -->
                <details class="form-section">
                    <summary class="section-header">
                        <i data-lucide="trending-up" style="width:18px;height:18px;"></i>
                        <span>Inventory & Status</span>
                        <i data-lucide="chevron-down" class="rotate-icon" style="width:18px;height:18px;"></i>
                    </summary>
                    <div class="section-content">
                        <div class="form-group col-6">
                            <label for="stock_quantity">Stock Quantity <span class="required-star">*</span></label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" placeholder="0" min="0" required>
                        </div>

                        <div class="form-group col-6">
                            <label for="stock_unit">Stock Unit Label</label>
                            <input type="text" class="form-control" id="stock_unit" name="stock_unit" value="{{ old('stock_unit', $product->stock_unit) }}" placeholder="e.g. bags, pieces, liters" list="stock_units" maxlength="50">
                            <datalist id="stock_units">
                                @foreach(['pieces', 'pcs', 'sacks', 'bags', 'packs', 'packets', 'sachets', 'bottles', 'cans', 'boxes', 'kits', 'units', 'liters', 'rolls', 'sets'] as $unit)
                                    <option value="{{ $unit }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        <div class="form-group col-6">
                            <label for="reorder_threshold">Reorder Threshold <span class="required-star">*</span></label>
                            <input type="number" class="form-control" id="reorder_threshold" name="reorder_threshold" value="{{ old('reorder_threshold', $product->reorder_threshold) }}" placeholder="10" min="0" required>
                            <span class="hint">Triggers low-stock warnings when inventory drops below this number.</span>
                        </div>

                        <div class="form-group col-6">
                            <label for="status">Catalog Status <span class="required-star">*</span></label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="out_of_stock" {{ old('status', $product->status) == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                <option value="discontinued" {{ old('status', $product->status) == 'discontinued' ? 'selected' : '' }}>Discontinued</option>
                            </select>
                        </div>

                        <div class="col-12" style="margin-top: 8px;">
                            <x-product-spec-sections
                                :fertilizer="$product->fertilizerDetail"
                                :registration="$product->registration"
                            />
                        </div>
                    </div>
                </details>

                <!-- SECTION 4: ADD MORE PHOTOS -->
                <details class="form-section">
                    <summary class="section-header">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>Add More Photos <span style="font-weight: 400; font-size: 0.8125rem; color: #64748b;">(Optional)</span></span>
                        <i data-lucide="chevron-down" class="rotate-icon" style="width:18px;height:18px;"></i>
                    </summary>
                    <div class="section-content">
                        <div class="col-12">
                            @if($slotsLeft > 0)
                                <x-product-image-picker
                                    id="product_images"
                                    :max="$slotsLeft"
                                    label="Add More Images"
                                    :hint="'PNG, JPG, WebP or GIF up to 5MB each · ' . $slotsLeft . ' slot' . ($slotsLeft === 1 ? '' : 's') . ' left'"
                                />
                            @else
                                <p class="hint">
                                    This product has reached the {{ \App\Models\Product::MAX_IMAGES }}-image limit.
                                    Remove an image above to add another.
                                </p>
                            @endif
                        </div>
                    </div>
                </details>

            </div>

            <!-- Form Buttons -->
            <div class="btn-row">
                <a href="{{ route('store.storefront.index') }}" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Update Product Profile
                </button>
            </div>
        </form>
    </div>
    @else
        <div class="rule-card">
            <h2 style="margin:0 0 6px;color:#14532d;font-size:1.2rem;">Application Rules</h2>
            <p class="hint" style="margin:0 0 20px;">These rules tell the quotation engine which quantity of <strong>{{ $product->name }}</strong> to recommend per hectare for each crop, season, and application stage.</p>
            <form method="POST" action="{{ route('store.formulas.store') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="return_to_product" value="1">
                <div class="rule-grid">
                    <div class="rule-field">
                        <label for="rule_crop_type">Crop Type</label>
                        <input id="rule_crop_type" name="crop_type" list="rule-crops" value="{{ old('crop_type') }}" placeholder="e.g. Rice" required>
                        <datalist id="rule-crops">@foreach($ruleCrops as $crop)<option value="{{ $crop }}">@endforeach</datalist>
                    </div>
                    <div class="rule-field">
                        <label for="rule_season">Season</label>
                        <select id="rule_season" name="season" required>
                            <option value="wet" @selected(old('season') === 'wet')>Wet Season</option>
                            <option value="dry" @selected(old('season') === 'dry')>Dry Season</option>
                        </select>
                    </div>
                    <div class="rule-field">
                        <label for="rule_stage">Application Stage</label>
                        <select id="rule_stage" name="stage" required>
                            @foreach(\App\Models\DecisionFormula::STAGES as $key => $label)
                                <option value="{{ $key }}" @selected(old('stage', 'standard') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rule-field">
                        <label for="rule_rate">Rate per Hectare</label>
                        <input id="rule_rate" type="number" name="rate_per_hectare" step="0.01" min="0.01" max="999.99" value="{{ old('rate_per_hectare') }}" required>
                    </div>
                    <div class="rule-field">
                        <label for="rule_unit">Unit</label>
                        <input id="rule_unit" name="unit" value="{{ old('unit', $product->package_unit ?? $product->stock_unit) }}" placeholder="Bags, kg, liters" required>
                    </div>
                    <div class="rule-field">
                        <label for="rule_notes">Notes</label>
                        <input id="rule_notes" name="notes" value="{{ old('notes') }}" placeholder="Optional guidance">
                    </div>
                    <div class="rule-field">
                        <label for="rule_min">Minimum Rate</label>
                        <input id="rule_min" type="number" name="min_rate_per_hectare" step="0.01" min="0.01" max="999.99" value="{{ old('min_rate_per_hectare') }}">
                    </div>
                    <div class="rule-field">
                        <label for="rule_max">Maximum Rate</label>
                        <input id="rule_max" type="number" name="max_rate_per_hectare" step="0.01" min="0.01" max="999.99" value="{{ old('max_rate_per_hectare') }}">
                    </div>
                </div>
                <button type="submit" class="btn-submit" style="margin-top:20px;">Add Application Rule</button>
            </form>
        </div>

        <div class="rule-card">
            <h2 style="margin:0 0 16px;color:#14532d;font-size:1.1rem;">Saved Rules</h2>
            @forelse($formulas as $formula)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;padding:14px 0;border-top:1px solid #f1f5f9;flex-wrap:wrap;">
                    <div>
                        <strong>{{ $formula->crop_type }}</strong>
                        <span style="color:#64748b;"> · {{ ucfirst($formula->season) }} · {{ $formula->stageLabel() }}</span>
                        <div class="hint">{{ $formula->rate_per_hectare }} {{ $formula->unit }} / hectare{{ $formula->notes ? ' — '.$formula->notes : '' }}</div>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <a class="btn-cancel" href="{{ route('store.formulas.edit', ['formula' => $formula, 'return_to_product' => 1]) }}">Edit</a>
                        <form method="POST" action="{{ route('store.formulas.destroy', $formula) }}" data-aqui-confirm="Remove this application rule?" data-aqui-danger>
                            @csrf @method('DELETE')
                            <input type="hidden" name="return_to_product" value="1">
                            <button type="submit" class="btn-cancel">Remove</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="hint" style="margin:0;">No application rules yet. Add the first rule above.</p>
            @endforelse
        </div>
    @endif
</div>

@endsection
