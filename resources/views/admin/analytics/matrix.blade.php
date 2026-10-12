@extends('layouts.sidebar')

@section('title', 'Decision Rule Matrix')

@section('content')
  @php
    $noteFor = function ($f) {
        $season = ucfirst($f->season);
        $crop   = ucfirst($f->crop_type);
        $n      = strtolower($f->product->name ?? '');

        if (preg_match('/(seed|plantlet|seedling)/', $n))            return "Plant/sow within the {$season}-season window — apply the full {$f->unit} per hectare.";
        if (preg_match('/(fertilizer|urea|muriate|npk|complete|superphosphate|micronutrient|humus|vermicast|bio)/', $n)) return "Apply in split doses across the {$season}-season {$crop} growth cycle.";
        if (preg_match('/(pesticide|insecticide|imidacloprid|confidor|karate|decis|selecron)/', $n)) return "Spray at the first sign of infestation; reapply if damage persists.";
        if (preg_match('/(herbicide|glyphosate|roundup|stomp|pendimethalin)/', $n))           return "Apply before weeds emerge; keep it off crop foliage.";
        if (preg_match('/(molluscicide|niclosamide)/', $n))          return "Scatter evenly over wet fields near planting time.";
        if (preg_match('/(fungicide|saaf|carbendazim|mancozeb)/', $n)) return "Treat at the first sign of disease; follow the labeled interval.";
        if (preg_match('/(knapsack|sprayer|bolo|knife|irrigation|pipe|hose|drip|kit)/', $n)) return "One unit per hectare — check condition before the busy season.";
        return "Baseline recommendation for {$season}-season {$crop}.";
    };
  @endphp

  <style>
    .mx-page { position: relative; }
    .mx-page::before,
    .mx-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .mx-page::before { width: 360px; height: 360px; background: rgba(16,185,129,0.16); top: -90px; right: -90px; }
    .mx-page::after { width: 300px; height: 300px; background: rgba(59,130,246,0.13); bottom: -70px; left: -70px; }
    .mx-content { position: relative; z-index: 1; }

    /* Breadcrumb */
    .mx-crumb { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #94a3b8; margin-bottom: 14px; flex-wrap: wrap; }
    .mx-crumb a { color: #14532d; font-weight: 600; text-decoration: none; }
    .mx-crumb a:hover { text-decoration: underline; }
    .mx-crumb svg { width: 13px; height: 13px; stroke: #cbd5e1; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }
    .mx-crumb span { font-weight: 600; color: #334155; }

    .matrix-chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .matrix-chips span {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 11px; font-weight: 700; color: #14532d;
      background: #f0fdf4; border: 1px solid rgba(20,61,35,0.12);
      padding: 6px 12px; border-radius: 999px; white-space: nowrap;
    }
    .matrix-chips svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    .mx-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      padding: 22px 24px;
    }

    .mx-group + .mx-group { margin-top: 30px; }
    .mx-group-head {
      display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;
      padding-bottom: 12px; margin-bottom: 6px; border-bottom: 1px solid #eef1ef;
    }
    .mx-group-head .mx-name { display: flex; align-items: center; gap: 10px; }
    .mx-group-head .mx-name b { font-size: 15px; font-weight: 700; color: #1e293b; text-transform: capitalize; }
    .mx-group-head .mx-ico {
      width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      background: #f0fdf4; color: #16a34a;
    }
    .mx-group-head .mx-ico svg { width: 16px; height: 16px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .mx-group-head .mx-badge {
      font-size: 10px; font-weight: 700; color: #4d7c0f; background: #f4fbe9;
      padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }

    .mx-season-title {
      display: inline-flex; align-items: center; gap: 6px; margin: 16px 0 8px;
      padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: capitalize;
    }
    .mx-season-title.wet { background: #eef2f6; color: #62829b; }
    .mx-season-title.dry { background: #f6f0e6; color: #a57f43; }

    .mx-table { width: 100%; border-collapse: collapse; text-align: left; }
    .mx-table th {
      padding: 9px 12px; color: #94a3b8; font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.08em; background: #fafbfa;
      border-bottom: 1px solid #eef1ef;
    }
    .mx-table td { padding: 12px; color: #334155; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .mx-table tr:last-child td { border-bottom: none; }
    .mx-table td.product { font-weight: 600; }
    .mx-table td.rate { white-space: nowrap; }
    .mx-table .rate-pill {
      background: #f0fdf4; border: 1px solid rgba(20,61,35,0.1); border-radius: 8px;
      padding: 5px 10px; font-size: 12px; display: inline-flex; gap: 4px; align-items: baseline; white-space: nowrap;
    }
    .mx-table .rate-pill b { color: #14532d; font-variant-numeric: tabular-nums; }
    .mx-table .rate-pill span { color: #94a3b8; }
    .mx-table td.notes { color: #64748b; font-size: 12px; line-height: 1.5; max-width: 360px; }

    .mx-empty {
      display: flex; flex-direction: column; align-items: center; gap: 8px;
      padding: 48px 20px; color: #94a3b8; font-size: 13px; text-align: center;
    }
    .mx-empty svg { width: 34px; height: 34px; stroke: #c3cec7; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 720px) {
      .mx-panel { padding: 16px; }
      .mx-table th, .mx-table td { padding: 10px 8px; }
      .mx-table td.notes { max-width: 180px; }
    }
  </style>

  <div class="mx-page">
    <div class="mx-content">

      <!-- BREADCRUMB -->
      <nav class="mx-crumb">
        <a href="{{ route('admin.operations.index') }}">Crops & Inputs</a>
        <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        <span>Decision Rule Matrix</span>
      </nav>

      <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:10px;">
        <div>
          <h1 style="font-size:22px;font-weight:700;color:#1e293b;margin:0 0 4px 0;">Decision Rule Matrix</h1>
          <p style="font-size:13px;color:#64748b;margin:0;">Baseline application rates per hectare — managed by store owners</p>
        </div>
        <div class="matrix-chips">
          <span>
            <svg viewBox="0 0 24 24"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 1 7 7c0 2.38-1.19 4.47-3 5.74V17a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1v-2.26C6.19 13.47 5 11.38 5 9a7 7 0 0 1 7-7z"/></svg>
            {{ number_format($ruleCount) }} rules
          </span>
          <span>
            <svg viewBox="0 0 24 24"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>
            {{ number_format($cropCount) }} crops
          </span>
          <span>
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            {{ $seasonLabels }}
          </span>
        </div>
      </div>

      @include('partials.admin-tabs', [
        'tabs' => [
          ['key' => 'formulas', 'label' => 'Formulas',          'url' => route('admin.operations.formulas')],
          ['key' => 'catalog',  'label' => 'Catalog', 'url' => route('admin.operations.catalog')],
          ['key' => 'plantings','label' => 'Planting Requests', 'url' => route('admin.plantings.index')],
          ['key' => 'seasons',  'label' => 'Seasons',           'url' => route('admin.operations.seasons')],
        ],
        'activeTab' => 'formulas',
      ])

      @if($ruleCount === 0)
        <div class="mx-panel">
          <div class="mx-empty">
            <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            <span>No formulas configured yet. Store owners define crop-specific formulas in their <strong>Input Formulas</strong> section.</span>
          </div>
        </div>
      @else
        <div class="mx-panel">
          @foreach($grouped as $crop => $seasons)
            @php $flattened = $seasons->flatten(); @endphp
            <div class="mx-group">
              <div class="mx-group-head">
                <div class="mx-name">
                  <span class="mx-ico">
                    <svg viewBox="0 0 24 24"><path d="M7 20h10"/><path d="M12 20v-8"/><path d="M12 12c-3 0-6-1-6-5 3 0 6 2 6 5"/><path d="M12 12c3 0 6-1 6-5-3 0-6 2-6 5"/></svg>
                  </span>
                  <b>{{ $crop }}</b>
                </div>
                <span class="mx-badge">{{ $flattened->count() }} rule{{ $flattened->count() === 1 ? '' : 's' }}</span>
              </div>

              @foreach($seasons as $season => $seasonFormulas)
                <div class="mx-season-title {{ $season }}">{{ $season }} season</div>
                <table class="mx-table">
                  <thead>
                    <tr>
                      <th style="width:32%;">Product</th>
                      <th style="width:18%;">Rate / Hectare</th>
                      <th>Notes</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($seasonFormulas as $formula)
                      <tr>
                        <td class="product">
                          {{ $formula->product->display_name ?? 'Unknown Product' }}
                          @if($formula->stage && $formula->stage !== 'standard')
                            <span style="display:inline-block; margin-left:6px; padding:2px 8px; border-radius:999px; font-size:11px; font-weight:700; background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; vertical-align:middle;">{{ $formula->stageLabel() }}</span>
                          @endif
                        </td>
                        <td class="rate">
                          <span class="rate-pill">
                            <b>{{ number_format((float) $formula->rate_per_hectare, 2) }}</b>
                            <span>{{ $formula->unit }} / ha</span>
                          </span>
                          @if($formula->min_rate_per_hectare !== null || $formula->max_rate_per_hectare !== null)
                            <div style="font-size:11px; color:#94a3b8; margin-top:2px;">
                              Bounds: {{ $formula->min_rate_per_hectare !== null ? number_format((float) $formula->min_rate_per_hectare, 2) : '—' }} – {{ $formula->max_rate_per_hectare !== null ? number_format((float) $formula->max_rate_per_hectare, 2) : '—' }}
                            </div>
                          @endif
                        </td>
                        <td class="notes">{{ $formula->notes ?: $noteFor($formula) }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              @endforeach
            </div>
          @endforeach
        </div>
      @endif

    </div>
  </div>
@endsection