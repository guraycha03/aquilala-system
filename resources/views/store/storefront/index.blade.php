@extends('layouts.sidebar')

@section('title', 'Product Catalog')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Retail Product Catalog Setup</h1>
    <p style="color: #606d64; font-size: 14px;">Manage items displayed in the public marketplace browse view for association farmers.</p>
  </div>

  <style>
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
    .product-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; }
    .product-details { padding: 16px; flex-grow: 1; display: flex; flex-direction: column; gap: 8px; }
    .price-tag { font-size: 18px; font-weight: 700; color: #143d23; }
    .edit-btn { text-align: center; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 8px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; }
    .edit-btn:hover { background: #e2e8f0; }
  </style>

  <div style="margin-bottom: 20px; display: flex; justify-content: flex-end;">
    <button style="background: #143d23; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer;">+ Insert New Product Profile</button>
  </div>

  <div class="product-grid">
    <div class="product-card">
      <div style="background: #f8fafc; height: 160px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 14px; border-bottom: 1px solid #e2e8f0; font-weight: 500;">
        [ Input Image Asset Area ]
      </div>
      <div class="product-details">
        <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700;">Chemical Fertilizer</span>
        <h3 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Urea Premium Granular</h3>
        <p style="color: #627267; font-size: 13px; margin: 0; line-height: 1.4;">High nitrogen crop input container variant for early vegetation stages.</p>
        <div class="price-tag" style="margin-top: auto; padding-top: 8px;">₱2,450.00 <span style="font-size: 12px; color: #64748b; font-weight: 400;">/ 50kg bag</span></div>
        <a href="#" class="edit-btn">Configure Pricing Matrix</a>
      </div>
    </div>

    <div class="product-card">
      <div style="background: #f8fafc; height: 160px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 14px; border-bottom: 1px solid #e2e8f0; font-weight: 500;">
        [ Input Image Asset Area ]
      </div>
      <div class="product-details">
        <span style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700;">Crop Protection</span>
        <h3 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Inescticide Formula Solution</h3>
        <p style="color: #627267; font-size: 13px; margin: 0; line-height: 1.4;">Broad-spectrum pest protection targeted for local rice plant variants.</p>
        <div class="price-tag" style="margin-top: auto; padding-top: 8px;">₱850.00 <span style="font-size: 12px; color: #64748b; font-weight: 400;">/ 1-Liter bottle</span></div>
        <a href="#" class="edit-btn">Configure Pricing Matrix</a>
      </div>
    </div>
  </div>
@endsection