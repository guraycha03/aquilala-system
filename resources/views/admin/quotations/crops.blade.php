@extends('layouts.sidebar')

@section('title', 'All Crop Trends')

@section('content')
<style>
  .badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
  }
  .variety-chip { background: #f1f5f9; color: #475569; }
  [data-theme="dark"] .variety-chip { background: #1f2937; color: #a7f3d0; }
  .num { text-align: right; font-variant-numeric: tabular-nums; }
  .cell-strong { font-weight: 600; color: #1e2922; }
  .cell-muted { color: #64748b; }
  .cap { text-transform: capitalize; }
</style>

<div class="header-panel" style="margin-bottom: 10px;">
  <h1 style="font-size: 24px; font-weight: 700; color: #1e2922; margin: 0 0 4px 0;">All Crops</h1>
  <p style="color: #606d64; font-size: 14px; margin: 0;">
    Every crop tracked in View Crop Trends &mdash; {{ $cropTotal }} in total.
    <a href="{{ route('admin.quotations.index') }}" style="color: #3f6b4a; font-weight: 600; text-decoration: none;">&larr; Back to View Crop Trends</a>
  </p>
</div>

<x-table-toolbar
    uid="admin-crops-all"
    work-table="adminCropTable"
    per-page="10"
    noun="crops"
    search-placeholder="Search crops…"
    :sorts="[
        ['key' => 'harvest_yield', 'dir' => -1, 'type' => 'num', 'label' => 'Harvest yield (highest)'],
        ['key' => 'harvest_reports', 'dir' => -1, 'type' => 'num', 'label' => 'Harvest reports (most)'],
        ['key' => 'planted_area', 'dir' => -1, 'type' => 'num', 'label' => 'Planted area (largest)'],
        ['key' => 'estimates', 'dir' => -1, 'type' => 'num', 'label' => 'Estimates (highest)'],
        ['key' => 'crop', 'dir' => 1, 'type' => 'str', 'label' => 'Crop (A–Z)'],
    ]"
  />

<div class="data-table-container">
  <div class="chart-scroll table-scroll">
    <table id="adminCropTable" class="data-table">
      <thead>
        <tr>
          <th>Crop</th>
          <th>Varieties</th>
          <th class="num">Estimates</th>
          <th class="num">Quoted Area</th>
          <th class="num">Quoted Value</th>
          <th class="num">Batches</th>
          <th class="num">Active</th>
          <th class="num">Farmers</th>
          <th class="num">Planted Area</th>
          <th class="num">Harvest Reports</th>
          <th class="num">Harvest Yield</th>
        </tr>
      </thead>
      <tbody>
        @foreach($cropRows as $crop)
          <tr data-crop="{{ strtolower($crop['crop']) }}"
              data-estimates="{{ $crop['estimates'] }}"
              data-planted_area="{{ $crop['planted_area'] }}"
              data-harvest_reports="{{ $crop['harvest_reports'] }}"
              data-harvest_yield="{{ $crop['harvest_yield'] }}">
            <td class="cell-strong cap">{{ $crop['crop'] }}</td>
            <td>
              @forelse($crop['varieties'] as $variety)
                <span class="badge variety-chip">{{ $variety }}</span>
              @empty
                <span class="cell-muted">&mdash;</span>
              @endforelse
            </td>
            <td class="num">{{ $crop['estimates'] }}</td>
            <td class="num">{{ number_format($crop['quoted_area'], 1) }} ha</td>
            <td class="num">₱{{ number_format($crop['quoted_value'], 0) }}</td>
            <td class="num">{{ $crop['batches'] }}</td>
            <td class="num">{{ $crop['active'] }}</td>
            <td class="num">{{ $crop['farmers'] }}</td>
            <td class="num">{{ number_format($crop['planted_area'], 1) }} ha</td>
            <td class="num">
              <div class="cell-strong">{{ $crop['harvest_reports'] }}</div>
              <div class="cell-muted" style="font-size: 11px;">{{ $crop['farmer_reports'] }} farmer &middot; {{ $crop['admin_reports'] }} admin</div>
            </td>
            <td class="num">
              <div class="cell-strong">{{ number_format($crop['harvest_yield'], 1) }} MT</div>
              <div class="cell-muted" style="font-size: 11px;">{{ number_format($crop['harvest_area'], 1) }} ha reported</div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
