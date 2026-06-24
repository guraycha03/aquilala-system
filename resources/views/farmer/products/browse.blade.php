@extends('layouts.sidebar')

@section('title', 'Browse Products')

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">B2B Supply Directory</h1>
    <p style="color: #606d64; font-size: 14px;">Explore available industrial stock listings from verified network stores.</p>
  </div>

  <div style="margin-bottom: 24px; display: flex; gap: 12px; align-items: center;">
    <input type="text" placeholder="Search parameters (e.g. Fertilizer, Seeds...)" style="flex: 1; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
    <select style="padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; font-size: 14px; color: #4a574e;">
      <option value="">All Seasons</option>
      <option value="wet">Wet Season</option>
      <option value="dry">Dry Season</option>
    </select>
    <button style="background: #143d23; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;">Search</button>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px;">
    
    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border: 1px solid #e2e8f0; background: white; border-radius: 12px; overflow: hidden; padding: 0;">
      <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
          <span style="font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">Seeds</span>
          <span style="font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px;">🌧️ Wet Season</span>
        </div>
        <h3 style="font-size: 16px; font-weight: 700; color: #1e2922; margin-bottom: 6px; line-height: 1.4;">NSIC Rc222 (Submarino Rice Seeds)</h3>
        <p style="font-size: 13px; color: #606d64; line-height: 1.5; margin-bottom: 12px;">Flood resistant certified rice seed variants ideal for Bulan rainy seasons.</p>
        <span style="font-size: 11px; font-family: monospace; color: #94a3b8; background: #f8fafc; padding: 2px 6px; border-radius: 4px;">SKU: SEED-RICE-WET-01</span>
      </div>
      <div style="padding: 16px 20px; background: #fafbfa; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
        <span style="font-size: 18px; font-weight: 800; color: #143d23;">₱1,200.00</span>
        <span style="font-size: 12px; font-weight: 600; color: #b91c1c;">Only 5 left!</span>
      </div>
    </div>

    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border: 1px solid #e2e8f0; background: white; border-radius: 12px; overflow: hidden; padding: 0;">
      <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
          <span style="font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">Seeds</span>
          <span style="font-size: 11px; font-weight: 700; background: #ffedd5; color: #c2410c; padding: 4px 8px; border-radius: 4px;">☀️ Dry Season</span>
        </div>
        <h3 style="font-size: 16px; font-weight: 700; color: #1e2922; margin-bottom: 6px; line-height: 1.4;">NSIC Rc216 (High-Yield Rice Seeds)</h3>
        <p style="font-size: 13px; color: #606d64; line-height: 1.5; margin-bottom: 12px;">Maximum output yield profiling variant suited for irrigated dry seasons.</p>
        <span style="font-size: 11px; font-family: monospace; color: #94a3b8; background: #f8fafc; padding: 2px 6px; border-radius: 4px;">SKU: SEED-RICE-DRY-02</span>
      </div>
      <div style="padding: 16px 20px; background: #fafbfa; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
        <span style="font-size: 18px; font-weight: 800; color: #143d23;">₱1,400.00</span>
        <span style="font-size: 12px; font-weight: 600; color: #16a34a;">40 In Stock</span>
      </div>
    </div>

    <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border: 1px solid #e2e8f0; background: white; border-radius: 12px; overflow: hidden; padding: 0;">
      <div style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
          <span style="font-size: 11px; font-weight: 700; background: #f3e8ff; color: #6b21a8; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">Fertilizer</span>
          <span style="font-size: 11px; font-weight: 700; background: #f0fdf4; color: #166534; padding: 4px 8px; border-radius: 4px;">🔄 All Seasons</span>
        </div>
        <h3 style="font-size: 16px; font-weight: 700; color: #1e2922; margin-bottom: 6px; line-height: 1.4;">Premium Urea Fertilizer (46-0-0)</h3>
        <p style="font-size: 13px; color: #606d64; line-height: 1.5; margin-bottom: 12px;">High nitrogen compound for rapid crop vegetation growth stages.</p>
        <span style="font-size: 11px; font-family: monospace; color: #94a3b8; background: #f8fafc; padding: 2px 6px; border-radius: 4px;">SKU: FERT-UREA-46</span>
      </div>
      <div style="padding: 16px 20px; background: #fafbfa; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
        <span style="font-size: 18px; font-weight: 800; color: #143d23;">₱2,100.00</span>
        <span style="font-size: 12px; font-weight: 600; color: #16a34a;">50 In Stock</span>
      </div>
    </div>

  </div>
@endsection