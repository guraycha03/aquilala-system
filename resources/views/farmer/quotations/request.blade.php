@extends('layouts.sidebar')

@section('title', 'Request Quotation')

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Create Quotation Request</h1>
    <p style="color: #606d64; font-size: 14px;">Submit targeted quantity bids to regional supply facilities for special bulk pricing.</p>
  </div>

  <div class="card" style="max-width: 650px;">
    <form action="#" method="POST" onsubmit="event.preventDefault();">
      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: #2c3530; margin-bottom: 6px;">Select Vendor Store</label>
        <select style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; background: #ffffff;">
          <option value="">-- Choose from available regional units --</option>
        </select>
      </div>

      <div style="margin-bottom: 16px;">
        <label style="display: block; font-size: 13px; font-weight: 700; color: #2c3530; margin-bottom: 6px;">Item Specifications & Requirements</label>
        <textarea rows="4" placeholder="Detail the materials, product names, or volume requirements explicitly..." style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; font-family: inherit;"></textarea>
      </div>

      <button type="submit" style="background: #143d23; color: white; border: none; padding: 12px 24px; font-weight: 600; border-radius: 6px; cursor: pointer;">Submit Pipeline Request</button>
    </form>
  </div>
@endsection