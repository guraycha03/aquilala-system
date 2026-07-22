@extends('layouts.sidebar')

@section('title', 'New Product Profile')

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
</style>

<div class="form-container">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">Insert New Product Profile</h1>
        <p class="page-subtitle">Add a new agricultural input product to your marketplace catalog for farmers to browse and quote.</p>
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

    <!-- Form Card -->
    <div class="form-card">
        <form method="POST" action="{{ route('store.products.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-grid">
                <!-- SECTION 1: BASIC INFORMATION -->
                <div class="section-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Basic Information
                </div>

                <div class="form-group full-width">
                    <label for="name">Product Name <span class="required-star">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Premium Urea Fertilizer (46-0-0)" required>
                </div>

                <div class="form-group">
                    <label for="brand">Brand / Manufacturer</label>
                    <input type="text" class="form-control" id="brand" name="brand" value="{{ old('brand') }}" placeholder="e.g. Bayer, Syngenta">
                </div>

                <div class="form-group">
                    <label for="sku">SKU / Class Code <span class="required-star">*</span></label>
                    <input type="text" class="form-control" id="sku" name="sku" value="{{ old('sku') }}" placeholder="e.g. FERT-UREA-50KG" required>
                </div>

                <div class="form-group">
                    <label for="category_id">Category <span class="required-star">*</span></label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <option value="" disabled selected>Select category</option>
                        @foreach($categories as $parent)
                            <optgroup label="{{ $parent->name }}">
                                @foreach($parent->children as $child)
                                    <option value="{{ $child->id }}" {{ old('category_id') == $child->id ? 'selected' : '' }}>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                                @if($parent->children->count() === 0)
                                    <option value="{{ $parent->id }}" {{ old('category_id') == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->name }}
                                    </option>
                                @endif
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="recommended_season">Recommended Season <span class="required-star">*</span></label>
                    <select class="form-control" id="recommended_season" name="recommended_season" required>
                        <option value="wet" {{ old('recommended_season') == 'wet' ? 'selected' : '' }}>Wet Season</option>
                        <option value="dry" {{ old('recommended_season') == 'dry' ? 'selected' : '' }}>Dry Season</option>
                        <option value="both" {{ old('recommended_season', 'both') == 'both' ? 'selected' : '' }}>All Seasons</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="description">Product Description</label>
                    <textarea class="form-control" id="description" name="description" placeholder="Describe the product, usage, benefits, and application notes...">{{ old('description') }}</textarea>
                </div>

                <!-- SECTION 2: PACKAGING & PRICING -->
                <div class="section-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"> 
                        <line x1="7" y1="3" x2="7" y2="21"></line>
                        <path d="M7 3h5.5a4.5 4.5 0 0 1 0 9H7"></path>
                        <line x1="5" y1="6.5" x2="18" y2="6.5"></line>
                        <line x1="5" y1="9.5" x2="18" y2="9.5"></line>
                    </svg>
                    Pricing & Inventory
                </div>

                <div class="form-group">
                    <label for="price">Unit Price <span class="required-star">*</span></label>
                    <div class="input-addon-wrapper">
                        <span class="addon-symbol">₱</span>
                        <input type="number" class="form-control" id="price" name="price" value="{{ old('price') }}" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity <span class="required-star">*</span></label>
                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" placeholder="0" min="0" required>
                </div>

                <div class="form-group">
                    <label for="reorder_threshold">Reorder Threshold <span class="required-star">*</span></label>
                    <input type="number" class="form-control" id="reorder_threshold" name="reorder_threshold" value="{{ old('reorder_threshold', 10) }}" placeholder="10" min="0" required>
                </div>

                <div class="form-group">
                    <label for="status">Catalog Status <span class="required-star">*</span></label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- SECTION 3: MEDIA ATTACHMENTS -->
                <div class="section-header">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    Product Image <span style="font-weight: 400; font-size: 13px; color: #64748b;">(Optional)</span>
                </div>

                <div class="form-group full-width">
                    <label for="main_image">Main Display Photo</label>
                    <div class="image-management-wrapper">
                        <div class="current-image-card">
                            <img id="image_preview" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='110' height='110' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='1.5'><rect x='3' y='3' width='18' height='18' rx='2' ry='2'/><circle cx='8.5' cy='8.5' r='1.5'/><polyline points='21 15 16 10 5 21'/></svg>" alt="Preview" class="img-preview">
                            <span class="hint" id="preview_label">No Image Selected</span>
                        </div>

                        <label class="file-dropzone" for="main_image">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            <div style="font-size: 14px; font-weight: 600; color: #334155;" id="filename_display">Click to upload product image</div>
                            <div class="hint" style="margin-top: 4px;">PNG, JPG, WebP or GIF up to 5MB</div>
                            <input type="file" id="main_image" name="main_image" accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;" onchange="handleImagePreview(this)">
                        </label>
                    </div>
                </div>
            </div>

            <!-- Form Buttons -->
            <div class="btn-row">
                <button type="submit" class="btn-submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Save Product Profile
                </button>
                <a href="{{ route('store.products.index') }}" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    function handleImagePreview(input) {
        const file = input.files[0];
        const preview = document.getElementById('image_preview');
        const label = document.getElementById('preview_label');
        const nameDisplay = document.getElementById('filename_display');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                label.innerText = 'New Image';
                nameDisplay.innerText = file.name;
            }
            reader.readAsDataURL(file);
        }
    }
</script>

@endsection