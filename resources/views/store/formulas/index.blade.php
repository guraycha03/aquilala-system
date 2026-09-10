@extends('layouts.sidebar')

@section('title', 'Input Formulas')

@section('content')
<style>
  .formula-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
  }
  .formula-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
  }
  .formula-card-body { padding: 8px 24px 24px 24px; }

  .kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }
  .kpi-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px 20px;
  }
  .kpi-card .kpi-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 6px;
  }
  .kpi-card .kpi-value {
    font-size: 26px;
    font-weight: 800;
    color: #143d23;
    letter-spacing: -0.02em;
  }

  .season-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    text-transform: capitalize;
  }
  .season-badge-wet { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
  .season-badge-dry { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }

  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th {
    background: #fafbfa;
    padding: 12px 14px;
    font-weight: 600;
    color: #4a574e;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
  }
  td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; color: #3d4a41; vertical-align: top; }

  .rate-chip {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
  }
  .rate-chip strong { color: #143d23; font-size: 15px; }
  .rate-chip span { color: #64748b; font-size: 12px; }

  .btn-primary {
    background: #143d23;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
  }
  .btn-primary:hover { background: #0d2917; }
  .btn-icon {
    background: transparent;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #475569;
    transition: all 0.2s;
  }
  .btn-icon-edit:hover { border-color: #143d23; color: #143d23; background: #f0fdf4; }
  .btn-icon-delete { color: #b91c1c; border-color: #fecaca; }
  .btn-icon-delete:hover { background: #fef2f2; border-color: #dc2626; color: #dc2626; }

  .info-banner {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 24px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
    font-size: 13px;
    color: #166534;
    line-height: 1.6;
  }
  .info-banner svg { flex-shrink: 0; margin-top: 2px; }
</style>

<div class="header-panel" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">DSS Recommendation Rule Setup</h1>
    <p style="color: #606d64; font-size: 14px; margin: 0;">
      Define standard per-hectare application rates that drive the cost quotation engine's recommended input quantities.
    </p>
  </div>
  <a href="{{ route('store.formulas.create') }}" class="btn-primary">
    <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Add Formula
  </a>
</div>

@if(session('success'))
  <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
    <svg viewBox="0 0 24 24" width="18" height="18" stroke="#16a34a" stroke-width="2" fill="none"><polyline points="20 6 9 17 4 12"/></svg>
    <span style="font-size: 14px; color: #166534; font-weight: 500;">{{ session('success') }}</span>
  </div>
@endif

@if($errors->any())
  <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;">
    <ul style="margin: 0; padding-left: 20px; color: #dc2626; font-size: 14px;">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="info-banner">
  <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" style="flex-shrink: 0; margin-top: 2px;">
    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
  </svg>
  <div>
    <strong>How formulas work:</strong> When a farmer generates a cost quotation for a crop + season, the engine computes
    <strong>recommended quantity = rate per hectare × land area</strong> for every product with a formula here.
    Soil and weather adjustments (e.g., sandy soil, typhoon season) are applied automatically on top of these base rates.
  </div>
</div>

@if($formulas->count() === 0)
  <div class="formula-card">
    <div style="text-align: center; padding: 56px 24px;">
      <svg viewBox="0 0 24 24" width="48" height="48" stroke="#94a3b8" stroke-width="1.5" fill="none" style="margin-bottom: 12px;">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
      </svg>
      <h3 style="font-size: 17px; color: #1e2922; margin: 0 0 6px 0;">No Input Formulas Yet</h3>
      <p style="font-size: 14px; color: #64748b; max-width: 420px; margin: 0 auto 20px auto;">
        The recommendation engine needs at least one formula per crop + season to generate input quantities.
        Add your first formula to start computing cost quotations.
      </p>
      <a href="{{ route('store.formulas.create') }}" class="btn-primary">
        <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Your First Formula
      </a>
    </div>
  </div>
@else
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-label">Active Formulas</div>
      <div class="kpi-value">{{ $stats['total'] }}</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Crop Profiles</div>
      <div class="kpi-value">{{ $stats['crops'] }}</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Linked Products</div>
      <div class="kpi-value">{{ $stats['products'] }}</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-label">Seasons Covered</div>
      <div class="kpi-value">{{ $stats['seasons'] }} / 2</div>
    </div>
  </div>

  @foreach($grouped as $crop => $seasons)
    <div class="formula-card">
      <div class="formula-card-header">
        <div>
          <h2 style="font-size: 16px; color: #1e2922; font-weight: 700; margin: 0; text-transform: capitalize;">{{ $crop }}</h2>
          <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
            {{ $seasons->flatten()->count() }} formula(s) across {{ $seasons->keys()->count() }} season(s)
          </p>
        </div>
      </div>

      <div class="formula-card-body">
        @foreach($seasons as $season => $seasonFormulas)
          <div style="display: flex; align-items: center; gap: 10px; margin: 18px 0 10px 0;">
            <span class="season-badge {{ $season === 'wet' ? 'season-badge-wet' : 'season-badge-dry' }}">
              @if($season === 'wet')
                <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/></svg>
              @else
                <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
              @endif
              {{ $season === 'wet' ? 'Wet Season' : 'Dry Season' }}
            </span>
            <span style="font-size: 12px; color: #94a3b8;">{{ $season === 'wet' ? 'June – January' : 'February – May' }}</span>
          </div>

          @if($seasonFormulas->count() === 0)
            <p style="font-size: 13px; color: #94a3b8; padding: 8px 0;">No formulas for this season.</p>
          @else
            <div class="app-scroll-wrap" style="--sb-bottom: 16px;">
              <div class="app-scroll">
                <table>
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Rate / Hectare</th>
                    <th>Notes</th>
                    <th style="width: 130px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($seasonFormulas as $formula)
                    <tr>
                      <td>
                        <div style="font-weight: 600; color: #1e2922;">{{ $formula->product->name ?? 'Unknown Product' }}</div>
                        @if($formula->product)
                          <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                            {{ $formula->product->category->name ?? 'General' }}
                            · ₱{{ number_format($formula->product->price ?? 0, 2) }}/{{ $formula->product->price_unit ?? 'piece' }}
                          </div>
                        @endif
                      </td>
                      <td>
                        <span class="rate-chip">
                          <strong>{{ number_format((float) $formula->rate_per_hectare, 2) }}</strong>
                          <span>{{ $formula->unit }} / ha</span>
                        </span>
                      </td>
                      <td style="color: #64748b; font-size: 13px; max-width: 320px;">
                        {{ $formula->notes ?? '—' }}
                      </td>
                      <td>
                        <div style="display: flex; gap: 6px;">
                          <a href="{{ route('store.formulas.edit', $formula) }}" class="btn-icon btn-icon-edit">
                            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                            Edit
                          </a>
                          <form action="{{ route('store.formulas.destroy', $formula) }}" method="POST"
                                onsubmit="return confirm('Remove this formula? Recommended quantities will stop being computed for this product in this crop + season.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon btn-icon-delete">
                              <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                              Delete
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
              </div>
              <div class="app-scrollbar">
                <div class="app-scrollbar-track"></div>
                <div class="app-scrollbar-thumb"></div>
              </div>
            </div>
          @endif
        @endforeach
      </div>
    </div>
  @endforeach
@endif
@endsection
