@extends('layouts.sidebar')

@section('title', 'Product Recommendations')

@section('content')
  <div class="header-panel">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">Smart Recommendations</h1>
    <p style="color: #606d64; font-size: 14px;">Automated insights on fertilizers, seeds, and soil enhancements suited for your logs.</p>
  </div>

  <div class="data-table-container" style="padding: 24px;">
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin-bottom: 20px; color: #166534; font-size: 14px;">
      💡 <strong>AI Tip:</strong> Based on recent analytics from your regional zone, nitrogen-heavy supplement models are generating higher harvest percentages.
    </div>

    <div class="card">
      <h3>No custom matching variants found</h3>
      <p style="color: #606d64; font-size: 14px;">Please populate details inside the "My Crops" panel first so the framework can parse accurate items.</p>
    </div>
  </div>
@endsection