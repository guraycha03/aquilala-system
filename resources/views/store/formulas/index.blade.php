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

  .stage-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 999px;
    font-size: 12px; font-weight: 700;
    background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe;
    white-space: nowrap;
  }
  .stage-badge-standard { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
  .bounds-text {
    font-size: 12px; color: #94a3b8; margin-top: 4px; white-space: nowrap;
  }
  .bounds-text.out-of-bounds { color: #b45309; font-weight: 600; }

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
  .btn-icon-delete { color: #b0403a; border-color: #e4c6c3; }
  .btn-icon-delete:hover { background: #faf2f1; border-color: #b0403a; color: #b0403a; }

  /* ── Help (?) button + how-it-works modal ──────────────────────── */
  .help-btn {
    width: 40px; height: 40px; border-radius: 10px;
    background: #ffffff; border: 1px solid #e2e8f0; color: #475569;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all 0.2s; padding: 0;
  }
  .help-btn:hover { border-color: #143d23; color: #143d23; background: #f0fdf4; }
  .help-btn:focus-visible { outline: 2px solid #16a34a; outline-offset: 2px; }

  .btn-ghost {
    background: transparent; color: #475569; border: 1px solid #e2e8f0;
    padding: 10px 18px; border-radius: 8px; font-size: 14px;
    font-weight: 600; cursor: pointer; font-family: inherit;
    transition: all 0.2s;
  }
  .btn-ghost:hover { background: #f8fafc; border-color: #cbd5e1; }

  .modal-overlay {
    position: fixed; inset: 0; z-index: 60; display: none;
    align-items: center; justify-content: center;
    background: rgba(15, 23, 42, 0.45); padding: 32px 20px 20px;
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: #ffffff; border-radius: 14px; width: 100%; max-width: 520px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .25);
    display: flex; flex-direction: column; overflow: hidden;
    animation: modalIn .18s ease-out;
  }
  @keyframes modalIn { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }
  .modal-head { padding: 18px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-shrink: 0; }
  .modal-head h3 { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 3px 0; }
  .modal-head p { margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.5; }
  .modal-close {
    background: transparent; border: none; font-size: 24px; line-height: 1;
    color: #94a3b8; cursor: pointer; padding: 2px 6px; border-radius: 8px;
    transition: all 0.15s; flex-shrink: 0;
  }
  .modal-close:hover { background: #e2e8f0; color: #0f172a; }
  .modal-body { padding: 16px 20px; overflow-y: auto; flex: 1; min-height: 0; }
  .modal-foot { padding: 14px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; }

  .help-list { margin: 0; padding-left: 18px; font-size: 13.5px; color: #475569; line-height: 1.65; }
  .help-list li { margin-bottom: 12px; }
  .help-list li:last-child { margin-bottom: 0; }
  .help-list strong { color: #1e2922; }

  @media (max-width: 640px) {
    .modal-overlay { padding-top: 56px; }
    .modal { max-height: calc(100vh - 72px); }
  }

  /* ── Sortable Table ──────────────────────────────────────────────── */
  .formula-table-card {
    background: #ffffff; border: 1px solid #e5e7eb; border-radius: 14px;
    overflow: hidden;
  }
  .table-scroll { overflow-x: auto; }
  table.formula-table { width: 100%; border-collapse: collapse; font-size: 14px; min-width: 960px; }
  .formula-table thead th {
    background: #fafcfa; padding: 13px 14px; font-weight: 700;
    color: #4a574e; border-bottom: 1px solid #e2e8f0; text-align: left;
    font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em;
    white-space: nowrap; user-select: none;
  }
  .formula-table td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; color: #3d4a41; vertical-align: middle; }
  .formula-table tbody tr { transition: background 0.15s; }
  .formula-table tbody tr:hover { background: #f8fdf9; }
  .formula-table tbody tr:last-child td { border-bottom: none; }
  .formula-table tbody tr.card-hide { display: none; }
  .prod-cell { font-weight: 600; color: #1e2922; }
  .prod-meta { font-size: 12px; color: #94a3b8; margin-top: 2px; }

  .rate-chip {
    background: #f1f5f9; border: 1px solid #e2e8f0;
    padding: 5px 11px; border-radius: 8px; font-size: 13px;
    display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
  }
  .rate-chip strong { color: #143d23; font-size: 15px; }
  .rate-chip span { color: #64748b; font-size: 12px; }

  .btn-icon {
    background: transparent; border: 1px solid #e2e8f0; border-radius: 8px;
    padding: 6px 10px; font-size: 13px; font-weight: 600; cursor: pointer;
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
    color: #475569; transition: all 0.2s;
  }
  .btn-icon-edit:hover { border-color: #143d23; color: #143d23; background: #f0fdf4; }
  .btn-icon-delete { color: #b0403a; border-color: #e4c6c3; }
  .btn-icon-delete:hover { background: #faf2f1; border-color: #b0403a; color: #b0403a; }

  .table-empty {
    display: none; text-align: center; padding: 48px 20px; color: #64748b; font-size: 14px;
  }
  .table-empty.show { display: block; }

  /* ── Pagination (client-side) ────────────────────────────────────── */
  .pagination-bar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 14px 18px; border-top: 1px solid #eef2ee; flex-wrap: wrap;
  }
  .pagination-info { font-size: 13px; color: #64748b; font-weight: 500; }
  .pagination-controls { display: flex; align-items: center; gap: 6px; }
  .page-btn {
    min-width: 34px; height: 34px; padding: 0 10px; border: 1.5px solid #e2e8f0;
    border-radius: 8px; background: #ffffff; color: #475569; font-size: 13px;
    font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s;
    display: inline-flex; align-items: center; justify-content: center;
  }
  .page-btn:hover:not(:disabled) { border-color: #16a34a; color: #16a34a; }
  .page-btn:disabled { opacity: 0.45; cursor: not-allowed; }
  .page-btn.active { background: #16a34a; border-color: #16a34a; color: #ffffff; font-weight: 700; }
  .page-ellipsis { color: #94a3b8; font-size: 13px; padding: 0 2px; }
</style>

<div class="header-panel" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
  <div>
    <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">Recommendation Rule Setup</h1>
    <p style="color: #606d64; font-size: 14px; margin: 0;">
      Define standard per-hectare application rates that drive the cost quotation engine's recommended input quantities.
    </p>
  </div>
  <div style="display: flex; align-items: center; gap: 10px;">
    <button type="button" class="help-btn" id="openHelpModal"
            aria-label="How formulas work" title="How formulas work">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        <circle cx="12" cy="12" r="10"/>
      </svg>
    </button>
    <a href="{{ route('store.formulas.create') }}" class="btn-primary">
      <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Add Formula
    </a>
  </div>
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

<!-- How-formulas-work modal (opened by the ? button in the header) -->
<div class="modal-overlay" id="helpModal" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="helpModalTitle">
    <div class="modal-head">
      <div>
        <h3 id="helpModalTitle">How formulas work</h3>
        <p>What happens on this page behind the scenes.</p>
      </div>
      <button type="button" class="modal-close" id="closeHelpModal" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
      <ul class="help-list">
        <li>
          <strong>Base recommendation.</strong> When a farmer generates a cost quotation for a crop + season,
          the engine computes <strong>recommended quantity = rate per hectare × land area</strong> for every
          product with a formula here.
        </li>
        <li>
          <strong>Application stages.</strong> When a product has several stage rows (e.g., basal + topdress),
          their rates are <strong>summed</strong> into one season total and the split is shown on the quote.
        </li>
        <li>
          <strong>Safety bounds.</strong> Min/max bounds clamp any rate that ever drifts outside its
          window, so a typo can never push a quotation outside the agronomic safety range.
        </li>
        <li>
          <strong>Adjustments on top.</strong> Soil and weather conditions (e.g., sandy soil,
          typhoon season) are applied automatically on top of these base rates.
        </li>
      </ul>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-ghost" id="gotItHelpModal">Got it</button>
    </div>
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

  <!-- Search + Styled Filters -->
  <x-table-toolbar
      uid="formulas"
      work-table="formulaTable"
      per-page="10"
      noun="formulas"
      empty="formulaTableEmpty"
      search-placeholder="Search formulas — product, crop, season, unit, or notes..."
      :filters="[
          ['key' => 'crop', 'label' => 'All Crop Types', 'options' => $crops->map(fn($c) => [strtolower($c), ucfirst($c)])->values()->all()],
          ['key' => 'season', 'label' => 'All Seasons', 'options' => [['wet', 'Wet Season'], ['dry', 'Dry Season']]],
          ['key' => 'stage', 'label' => 'All Stages', 'options' => collect(\App\Models\DecisionFormula::STAGES)->map(fn($label, $key) => [$key, $label])->values()->all()],
      ]"
      :sorts="[
          ['key' => 'name', 'dir' => 1, 'type' => 'str', 'label' => 'Product (A–Z)'],
          ['key' => 'name', 'dir' => -1, 'type' => 'str', 'label' => 'Product (Z–A)'],
          ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop type (A–Z)'],
          ['key' => 'rate', 'dir' => -1, 'type' => 'num', 'label' => 'Rate / ha (high → low)'],
          ['key' => 'rate', 'dir' => 1, 'type' => 'num', 'label' => 'Rate / ha (low → high)'],
          ['key' => 'unit', 'dir' => 1, 'type' => 'str', 'label' => 'Unit (A–Z)'],
      ]"
  />

  <p style="font-size: 12px; color: #94a3b8; margin: 0 0 10px 0;">
    <strong style="color: #64748b;">Crop Type</strong> = what the farmer is growing &nbsp;·&nbsp;
    <strong style="color: #64748b;">Product</strong> = the input applied to that crop
  </p>

  <!-- Formula Table -->
  <div class="formula-table-card">
    <div class="table-scroll">
      <table class="formula-table" id="formulaTable">
        <thead>
          <tr>
            <th>Product</th>
            <th>Crop Type</th>
            <th>Season</th>
            <th>Stage</th>
            <th>Rate / Ha</th>
            <th>Unit</th>
            <th>Notes</th>
            <th style="width: 140px;">Actions</th>
          </tr>
        </thead>
        <tbody id="formulaTableBody">
          @foreach($formulas as $formula)
          <tr data-search="{{ strtolower(trim(($formula->product->name ?? '') . ' ' . ($formula->product->size_label ?? '') . ' ' . ($formula->product->category_display_name ?? '') . ' ' . ($formula->crop_type ?? '') . ' ' . ($formula->season ?? '') . ' ' . $formula->stageLabel() . ' ' . ($formula->unit ?? '') . ' ' . ($formula->notes ?? ''))) }}"
              data-crop="{{ strtolower($formula->crop_type) }}"
              data-season="{{ $formula->season }}"
              data-stage="{{ $formula->stage }}"
              data-rate="{{ (float) $formula->rate_per_hectare }}"
              data-unit="{{ strtolower(trim($formula->unit ?? '')) }}"
              data-name="{{ strtolower($formula->product->name ?? '') }}">
            <td>
              <div class="prod-cell">{{ $formula->product->display_name ?? 'Unknown Product' }}</div>
              @if($formula->product)
                <div class="prod-meta">
                  {{ $formula->product->category_display_name }}
                  · ₱{{ number_format($formula->product->price ?? 0, 2) }}/{{ $formula->product->price_unit ?? 'piece' }}
                </div>
              @endif
            </td>
            <td style="font-weight: 600; color: #1e2922; text-transform: capitalize;">{{ $formula->crop_type }}</td>
            <td>
              <span class="season-badge {{ $formula->season === 'wet' ? 'season-badge-wet' : 'season-badge-dry' }}">
                @if($formula->season === 'wet')
                  <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/></svg>
                @else
                  <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                @endif
                {{ $formula->season === 'wet' ? 'Wet Season' : 'Dry Season' }}
              </span>
            </td>
            <td>
              <span class="stage-badge {{ $formula->stage === 'standard' ? 'stage-badge-standard' : '' }}" title="{{ $formula->stage }}">
                {{ $formula->stageLabel() }}
              </span>
            </td>
            <td>
              <span class="rate-chip"><strong>{{ number_format((float) $formula->rate_per_hectare, 2) }}</strong><span>/ ha</span></span>
              @if($formula->min_rate_per_hectare !== null || $formula->max_rate_per_hectare !== null)
                <div class="bounds-text {{ $formula->rateNeedsClamp() ? 'out-of-bounds' : '' }}">
                  @if($formula->min_rate_per_hectare !== null && $formula->max_rate_per_hectare !== null)
                    {{ number_format((float) $formula->min_rate_per_hectare, 2) }} – {{ number_format((float) $formula->max_rate_per_hectare, 2) }}
                  @elseif($formula->min_rate_per_hectare !== null)
                    ≥ {{ number_format((float) $formula->min_rate_per_hectare, 2) }}
                  @else
                    ≤ {{ number_format((float) $formula->max_rate_per_hectare, 2) }}
                  @endif
                  <span style="font-weight: 400;">bounds</span>
                </div>
              @endif
            </td>
            <td style="color: #475569;">{{ $formula->unit ?? '—' }}</td>
            <td style="color: #64748b; font-size: 13px; max-width: 280px;">{{ $formula->notes ?? '—' }}</td>
            <td>
              <div style="display: flex; gap: 6px;">
                <a href="{{ route('store.formulas.edit', $formula) }}" class="btn-icon btn-icon-edit">
                  <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                  Edit
                </a>
                <form action="{{ route('store.formulas.destroy', $formula) }}" method="POST"
                      data-aqui-confirm="Remove this formula? Recommended quantities will stop being computed for this product in this crop + season."
                      data-aqui-danger data-aqui-confirm-label="Delete">
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

    <div class="table-empty" id="formulaTableEmpty">
      <svg viewBox="0 0 24 24" width="40" height="40" stroke="#94a3b8" stroke-width="1.5" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <p style="margin: 10px 0 0 0;">No formulas match your search or filters. Try a different keyword or clear the filters.</p>
    </div>
  </div>
  @endif

<script>
(function () {
  const overlay = document.getElementById('helpModal');
  const openBtn = document.getElementById('openHelpModal');
  const closeBtn = document.getElementById('closeHelpModal');
  const gotItBtn = document.getElementById('gotItHelpModal');

  function open() {
    overlay.classList.add('open');
    overlay.setAttribute('aria-hidden', 'false');
    closeBtn.focus();
  }
  function close() {
    overlay.classList.remove('open');
    overlay.setAttribute('aria-hidden', 'true');
    openBtn.focus();
  }

  openBtn.addEventListener('click', open);
  closeBtn.addEventListener('click', close);
  gotItBtn.addEventListener('click', close);
  overlay.addEventListener('click', e => { if (e.target === overlay) close(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.classList.contains('open')) close(); });
})();
</script>
@endsection
