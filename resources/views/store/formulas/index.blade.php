@extends('layouts.sidebar')

@section('title', 'Formulas')

@section('content')
  <div class="header-panel" style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922;">DSS Recommendation Rule Setup</h1>
    <p style="color: #606d64; font-size: 14px;">Define standard multi-stage application coefficients per hectare to drive backend decision algorithms.</p>
  </div>

  <style>
    .formula-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .parameter-pill { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; }
    .save-config-btn { background: #143d23; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
  </style>

  <div class="formula-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <div>
        <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Rice (Oryza sativa) - Wet Season Profile Map</h2>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Standard rule engine parameters used for wet season cultivation cycles.</p>
      </div>
      <button class="save-config-btn">Edit Parameters</button>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
      <div class="parameter-pill"><strong>Basal Application:</strong> 2 Bags Complete / Ha</div>
      <div class="parameter-pill"><strong>Early Tillering:</strong> 1 Bag Urea / Ha</div>
      <div class="parameter-pill"><strong>Panicle Initiation:</strong> 0.5 Bag MOP / Ha</div>
    </div>
  </div>

  <div class="formula-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
      <div>
        <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0;">Rice (Oryza sativa) - Dry Season Profile Map</h2>
        <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Standard rule engine parameters used for high-evaporation dry seasonal cycles.</p>
      </div>
      <button class="save-config-btn">Edit Parameters</button>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
      <div class="parameter-pill"><strong>Basal Application:</strong> 3 Bags Complete / Ha</div>
      <div class="parameter-pill"><strong>Early Tillering:</strong> 1.5 Bags Urea / Ha</div>
      <div class="parameter-pill"><strong>Panicle Initiation:</strong> 1 Bag MOP / Ha</div>
    </div>
  </div>
@endsection