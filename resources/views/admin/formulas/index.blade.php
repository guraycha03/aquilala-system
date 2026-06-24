@extends('layouts.sidebar')

@section('title', 'System DSS Rules')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">System Global Decision Rules</h1>
    <p style="color: #606d64; font-size: 14px;">Audit standard baseline application formulas used across the system recommendation engine.</p>
  </div>

  <style>
    .formula-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
    .parameter-tag { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; display: inline-block; color: #334155; border: 1px solid #cbd5e1; }
  </style>

  <div class="formula-card">
    <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin-bottom: 4px;">System Baseline Crop Matrix</h2>
    <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Global rule values provided for recommendation calculators.</p>
    
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <div class="parameter-tag"><strong>Nitrogen Bound:</strong> 90 kg / Hectare</div>
      <div class="parameter-tag"><strong>Phosphorus Bound:</strong> 30 kg / Hectare</div>
      <div class="parameter-tag"><strong>Potassium Bound:</strong> 30 kg / Hectare</div>
    </div>
  </div>
@endsection