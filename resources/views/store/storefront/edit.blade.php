@extends('layouts.sidebar')

@section('title', 'Edit Product Profile')

@section('content')

<style>
    /* Page Layout & Container */
    .form-container { 
        max-width: 880px; 
        margin: 0 auto; 
    }

    .page-header {
        margin-bottom: 28px;
    }

    .page-title {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px 0;
        letter-spacing: -0.02em;
    }

    .page-subtitle {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }

    /* Modern Card Layout */
    .form-card { 
        background: #ffffff; 
        border: 1px solid #e2e8f0; 
        border-radius: 16px; 
        padding: 32px; 
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    }

    /* Grids & Field Layouts */
    .form-grid { 
        display: grid; 
        grid-template-columns: repeat(2, 1fr); 
        gap: 20px; 
    }

    .form-group { 
        display: flex; 
        flex-direction: column; 
        gap: 6px; 
    }

    .form-group.full-width { 
        grid-column: 1 / -1; 
    }

    /* Section Headers */
    .section-header { 
        grid-column: 1 / -1; 
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px; 
        font-weight: 700; 
        color: #14532d; 
        border-bottom: 1px solid #e2e8f0; 
        padding-bottom: 10px; 
        margin-top: 16px; 
        margin-bottom: 4px;
    }

    .section-header svg {
        color: #16a34a;
    }

    /* Field Labels & Inputs */
    .form-group label { 
        font-size: 13px; 
        font-weight: 600; 
        color: #334155; 
    }

    .required-star {
        color: #e11d48;
    }

    .form-control {
        padding: 10px 14px; 
        border: 1px solid #cbd5e1; 
        border-radius: 8px; 
        font-size: 14px; 
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
        box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.12);
    }

    textarea.form-control { 
        resize: vertical; 
        min-height: 100px; 
    }

    .hint { 
        font-size: 12px; 
        color: #64748b; 
        margin-top: 2px;
    }

    /* Custom Input Addons */
    .input-addon-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-addon-wrapper .addon-symbol {
        position: absolute;
        left: 12px;
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        pointer-events: none;
    }

    .input-addon-wrapper input {
        padding-left: 28px;
    }

    /* File Drop Area & Image Preview */
    .image-management-wrapper {
        display: flex;
        gap: 20px;
        align-items: center;
    }

    .current-image-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
    }

    .img-preview { 
        width: 110px; 
        height: 110px; 
        object-fit: cover; 
        border-radius: 10px; 
        border: 1px solid #e2e8f0; 
        background: #f8fafc; 
    }

    .file-dropzone {
        flex: 1;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .file-dropzone:hover {
        border-color: #166534;
        background: #f0fdf4;
    }

    .file-dropzone svg {
        color: #64748b;
        margin-bottom: 8px;
    }

    /* Validation Alerts */
    .alert-error {
        background: #fef2f2; 
        border: 1px solid #fecaca; 
        border-radius: 10px; 
        padding: 16px; 
        margin-bottom: 24px;
    }

    .alert-error ul {
        list-style: none; 
        padding: 0; 
        margin: 0; 
        color: #991b1b; 
        font-size: 14px;
    }

    .alert-error li {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }

    .alert-error li:last-child {
        margin-bottom: 0;
    }

    .alert-success {
        background: #f0fdf4; 
        border: 1px solid #bbf7d0; 
        border-radius: 10px; 
        padding: 16px; 
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #166534;
        font-size: 14px;
        font-weight: 600;
    }

    /* Action Buttons */
    .btn-row { 
        display: flex; 
        gap: 12px; 
        align-items: center; 
        margin-top: 32px; 
        padding-top: 20px;
        border-top: 1px solid #f1f5f9;
    }

    .btn-submit {
        background: #14532d; 
        color: #ffffff; 
        padding: 11px 24px; 
        border: none; 
        border-radius: 8px;
        font-size: 14px; 
        font-weight: 600; 
        cursor: pointer; 
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .btn-submit:hover { 
        background: #166534; 
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(20, 83, 45, 0.15);
    }

    .btn-cancel {
        background: transparent; 
        color: #475569; 
        padding: 11px 24px; 
        border: 1px solid #cbd5e1; 
        border-radius: 8px;
        font-size: 14px; 
        font-weight: 600; 
        cursor: pointer; 
        text-decoration: none; 
        transition: all 0.2s ease;
    }

    .btn-cancel:hover { 
        background: #f8fafc; 
        color: #0f172a;
    }

    /* Existing image gallery manager */
    .existing-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
        margin-bottom: 6px;
    }
    .existing-gallery-item {
        position: relative;
        aspect-ratio: 1 / 1;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #f8fafc;
    }
    .existing-gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .existing-gallery-tag {
        position: absolute; top: 6px; left: 6px;
        font-size: 10px; font-weight: 700; letter-spacing: 0.04em;
        background: #166534; color: #ffffff;
        padding: 3px 7px; border-radius: 5px;
    }
    .existing-gallery-actions {
        position: absolute; left: 0; right: 0; bottom: 0;
        display: flex; gap: 4px; justify-content: center;
        padding: 6px;
        background: linear-gradient(to top, rgba(15, 23, 42, 0.82), rgba(15, 23, 42, 0));
    }
    .gallery-btn {
        display: inline-flex; align-items: center; justify-content: center;
        border: none; border-radius: 6px; cursor: pointer;
        font-size: 11px; font-weight: 700; font-family: inherit;
        padding: 5px 8px;
        transition: all 0.2s;
    }
    .gallery-btn svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .gallery-btn-main { background: #ffffff; color: #166534; flex: 1; }
    .gallery-btn-main:hover { background: #dcfce7; }
    .gallery-btn-delete { background: #ffffff; color: #e11d48; }
    .gallery-btn-delete:hover { background: #ffe4e6; color: #be123c; }
</style>

<x-breadcrumb :items="[
  ['label' => 'My Store', 'url' => route('store.storefront.index')],
  ['label' => 'Edit Product'],
]"/>

<div class="form-container">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Edit Product Profile</h1>
        <p class="page-subtitle">Update details, pricing, and inventory status for <strong>{{ $product->name }}</strong>.</p>
    </div>

    <!-- Error Summary -->
    @if ($errors->any())
    <div class="alert-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    {{ $error }}
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Success Message -->
    @if (session('success'))
    <div class="alert-success">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    <!-- Main Form -->
    <div class="form-card">
        <form method="POST" action="{{ route('store.products.update', $product->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <!-- SECTION 1: BASIC INFORMATION -->
                <div class="section-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Basic Information
                </div>

                <div class="form-group full-width">
                    <label for="name">Product Name <span class="required-star">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" placeholder="e.g. Urea Fertilizer (46-0-0)" maxlength="255" required>
                    <span class="hint">Just the product name — leave out the weight or volume, that goes in the size field below.</span>
                </div>

                <div class="form-group">
                    <label for="brand">Brand / Manufacturer</label>
                    <input type="text" class="form-control" id="brand" name="brand" value="{{ old('brand', $product->brand) }}" placeholder="e.g. Bayer, Syngenta">
                </div>

                <div class="form-group">
                    <label for="sku">SKU / Class Code <span class="required-star">*</span></label>
                    <input type="text" class="form-control" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="e.g. FERT-UREA-50KG" required>
                    <span class="hint">Must be unique across all catalog products.</span>
                </div>

                <div class="form-group">
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

                <div class="form-group">
                    <label for="product_type">Product Type</label>
                    <input type="text" class="form-control" id="product_type" name="product_type" value="{{ old('product_type', $product->product_type) }}" placeholder="e.g. Premium NPK Compound">
                </div>

                <div class="form-group full-width">
                    <label for="recommended_season">Recommended Season <span class="required-star">*</span></label>
                    <select class="form-control" id="recommended_season" name="recommended_season" required>
                        <option value="wet" {{ old('recommended_season', $product->recommended_season) == 'wet' ? 'selected' : '' }}>Wet Season</option>
                        <option value="dry" {{ old('recommended_season', $product->recommended_season) == 'dry' ? 'selected' : '' }}>Dry Season</option>
                        <option value="both" {{ old('recommended_season', $product->recommended_season) == 'both' ? 'selected' : '' }}>All Seasons</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="description">Product Description</label>
                    <textarea class="form-control" id="description" name="description" placeholder="Describe the product, usage, benefits, and application notes...">{{ old('description', $product->description) }}</textarea>
                    <span class="hint">Provide details that help farmers make informed purchasing decisions.</span>
                </div>

                <!-- SECTION 2: PACKAGING & PRICING -->
                <div class="section-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"> 
                        <line x1="7" y1="3" x2="7" y2="21"></line>
                        <path d="M7 3h5.5a4.5 4.5 0 0 1 0 9H7"></path>
                        <line x1="5" y1="6.5" x2="18" y2="6.5"></line>
                        <line x1="5" y1="9.5" x2="18" y2="9.5"></line>
                    </svg>
                    Packaging & Pricing
                </div>

                <div class="form-group">
                    <label for="package_size">Size / Measure</label>
                    <input type="number" class="form-control" id="package_size" name="package_size" value="{{ old('package_size', $product->package_size) }}" placeholder="e.g. 50" step="0.01" min="0">
                </div>

                <div class="form-group">
                    <label for="package_unit">Unit</label>
                    <input type="text" class="form-control" id="package_unit" name="package_unit" value="{{ old('package_unit', $product->package_unit) }}" placeholder="e.g. kg, L, bag" list="package_units" maxlength="50">
                    <datalist id="package_units">
                        @foreach(\App\Models\Product::sizeUnitSuggestions() as $unit)
                            <option value="{{ $unit }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div class="form-group">
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

                <div class="form-group">
                    <label for="price">Selling Price <span class="required-star">*</span></label>
                    <div class="input-addon-wrapper">
                        <span class="addon-symbol">₱</span>
                        <input type="number" class="form-control" id="price" name="price" value="{{ old('price', $product->price) }}" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="price_unit">Price Unit Description</label>
                    <input type="text" class="form-control" id="price_unit" name="price_unit" value="{{ old('price_unit', $product->price_unit) }}" placeholder="e.g. Per Sack, Per Kg, Per Liter">
                </div>

                <x-product-name-preview
                    :name="old('name', $product->name)"
                    :size="old('package_size', $product->package_size)"
                    :unit="old('package_unit', $product->package_unit)"
                />

                <!-- SECTION 3: INVENTORY & STATUS -->
                <div class="section-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                    Inventory & Status
                </div>

                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity <span class="required-star">*</span></label>
                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" placeholder="0" min="0" required>
                </div>

                <div class="form-group">
                    <label for="stock_unit">Stock Unit Label</label>
                    <input type="text" class="form-control" id="stock_unit" name="stock_unit" value="{{ old('stock_unit', $product->stock_unit) }}" placeholder="e.g. bags, pieces, liters" list="stock_units" maxlength="50">
                    <datalist id="stock_units">
                        @foreach(['pieces', 'pcs', 'sacks', 'bags', 'packs', 'packets', 'sachets', 'bottles', 'cans', 'boxes', 'kits', 'units', 'liters', 'rolls', 'sets'] as $unit)
                            <option value="{{ $unit }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="reorder_threshold">Reorder Threshold <span class="required-star">*</span></label>
                    <input type="number" class="form-control" id="reorder_threshold" name="reorder_threshold" value="{{ old('reorder_threshold', $product->reorder_threshold) }}" placeholder="10" min="0" required>
                    <span class="hint">Triggers low-stock warnings when inventory drops below this number.</span>
                </div>

                <div class="form-group">
                    <label for="status">Catalog Status <span class="required-star">*</span></label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="out_of_stock" {{ old('status', $product->status) == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                        <option value="discontinued" {{ old('status', $product->status) == 'discontinued' ? 'selected' : '' }}>Discontinued</option>
                    </select>
                </div>

                <x-product-spec-sections
                    :fertilizer="$product->fertilizerDetail"
                    :registration="$product->registration"
                />

                <!-- SECTION 4: PRODUCT PHOTOS -->
                <div class="section-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Product Photos <span style="font-weight: 400; font-size: 13px; color: #64748b;">(Optional)</span>
                </div>

                @php
                    $existingImages = $product->gallery;
                    $slotsLeft = max(0, \App\Models\Product::MAX_IMAGES - $existingImages->count());
                @endphp

                @if($existingImages->isNotEmpty())
                    <div class="form-group full-width">
                        <label>Uploaded Images <span class="hint" style="display: inline; font-weight: 400;">({{ $existingImages->count() }})</span></label>
                        <div class="existing-gallery">
                            @foreach($existingImages as $image)
                                <div class="existing-gallery-item">
                                    <img src="{{ asset('storage/' . $image->file_path) }}" alt="{{ $product->name }}" loading="lazy">
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

            <!-- Form Buttons -->
            <div class="btn-row">
                <button type="submit" class="btn-submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Update Product Profile
                </button>
                <a href="{{ route('store.storefront.index') }}" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection