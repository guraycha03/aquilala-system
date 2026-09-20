@extends('layouts.sidebar')

@section('title', 'Seasons')

@section('content')

  <style>
    .sea-page { position: relative; }
    .sea-page::before,
    .sea-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .sea-page::before { width: 340px; height: 340px; background: rgba(2,132,199,0.12); top: -80px; right: -80px; }
    .sea-page::after { width: 300px; height: 300px; background: rgba(245,158,11,0.12); bottom: -60px; left: -60px; }
    .sea-content { position: relative; z-index: 1; }

    .sea-head { margin: 0 0 10px; }
    .sea-head h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; letter-spacing: 0.02em; }
    .sea-head p { font-size: 13px; color: #64748b; margin: 0; }

    .sea-banner {
      border-radius: 20px; padding: 22px 26px; margin-bottom: 22px;
      display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
      border: 1px solid rgba(255,255,255,0.6);
      box-shadow: 0 6px 28px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.6);
    }
    .sea-banner.wet { background: linear-gradient(120deg, #e0f2fe, #ecfeff); }
    .sea-banner.dry { background: linear-gradient(120deg, #fef3c7, #fffbeb); }
    .sea-banner .b-icon {
      width: 52px; height: 52px; border-radius: 16px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.75);
      color: #0369a1;
    }
    .sea-banner.dry .b-icon { color: #d97706; }
    .sea-banner .b-icon svg { width: 26px; height: 26px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .sea-banner .b-body { flex: 1; min-width: 240px; }
    .sea-banner .b-eyebrow { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #475569; }
    .sea-banner .b-title { font-size: 19px; font-weight: 800; color: #0f172a; margin: 2px 0 5px; }
    .sea-banner .b-sub { font-size: 13px; color: #334155; line-height: 1.5; max-width: 640px; }
    .sea-banner .b-reco {
      font-size: 12px; font-weight: 600; color: #14532d; background: rgba(255,255,255,0.7);
      padding: 8px 14px; border-radius: 10px;
    }
    .sea-banner .b-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
    .sea-banner .b-chip {
      font-size: 11px; font-weight: 700; color: #14532d;
      background: rgba(255,255,255,0.72); border: 1px solid rgba(20,61,35,0.1);
      padding: 5px 12px; border-radius: 999px; white-space: nowrap;
    }

    .sea-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
    .sea-card {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      overflow: hidden; position: relative; transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    .sea-card:hover { transform: translateY(-2px); box-shadow: 0 16px 44px rgba(0,0,0,0.09); }
    .sea-card.current::after {
      content: "Current"; position: absolute; top: 0; right: 0;
      background: #143d23; color: #fff; font-size: 10px; font-weight: 800;
      letter-spacing: 0.12em; text-transform: uppercase;
      padding: 7px 14px; border-radius: 0 0 0 14px;
    }
    .sea-card-head { display: flex; align-items: center; gap: 14px; padding: 20px 22px; }
    .sea-card-icon {
      width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
    }
    .sea-card-icon.wet { background: #e0f2fe; color: #0284c7; }
    .sea-card-icon.dry { background: #fef3c7; color: #d97706; }
    .sea-card-icon svg { width: 22px; height: 22px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .sea-card-title { font-size: 16px; font-weight: 800; color: #1e293b; }
    .sea-card-months { font-size: 12px; color: #64748b; margin-top: 2px; letter-spacing: 0.03em; }
    .sea-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: #f1f5f9; border-top: 1px solid #f1f5f9; }
    .sea-stat { background: #fff; padding: 14px 12px; text-align: center; }
    .sea-stat b { display: block; font-size: 20px; font-weight: 800; color: #143d23; line-height: 1; }
    .sea-stat span { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-top: 5px; display: block; }
    .sea-card-body { padding: 18px 22px; }
    .sea-card-body p { font-size: 12.5px; color: #475569; line-height: 1.55; margin: 0 0 10px; }
    .sea-card-body .reco {
      font-size: 12px; font-weight: 600; color: #14532d; background: #f0fdf4;
      border: 1px solid rgba(20,61,35,0.1); padding: 10px 14px; border-radius: 10px;
    }

    @media (max-width: 720px) {
      .sea-banner { padding: 18px; }
    }
  </style>

  <div class="sea-page">
    <div class="sea-content">

      <div class="sea-head">
        <h1>Planting Seasons</h1>
        <p>Seasonal cropping calendar for Bulan, Sorsogon and the current climate conditions.</p>
      </div>

      @include('partials.admin-tabs', [
        'tabs' => [
          ['key' => 'formulas', 'label' => 'Formulas',          'url' => route('admin.operations.formulas')],
          ['key' => 'catalog',  'label' => 'Catalog & Reviews', 'url' => route('admin.operations.catalog')],
          ['key' => 'seasons',  'label' => 'Seasons',           'url' => route('admin.operations.seasons')],
        ],
        'activeTab' => 'seasons',
      ])

      @php $current = $climate['season'] === 'wet' ? 'wet' : 'dry'; @endphp
      <div class="sea-banner {{ $current }}">
        <div class="b-icon">
          @if($current === 'wet')
            <svg viewBox="0 0 24 24"><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/><path d="M8 13l-3 3M16 13l-3 3M12 9l-3 3M12 9v5"/></svg>
          @else
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
          @endif
        </div>
        <div class="b-body">
          <div class="b-eyebrow">Currently in</div>
          <div class="b-title">{{ $climate['label'] ?? 'Wet Season' }}</div>
          <div class="b-sub">{{ $climate['typical_weather'] ?? '' }}</div>
          <div class="b-chips">
            @if(!empty($climate['sub_season']))
              <span class="b-chip">{{ $climate['sub_season'] }}</span>
            @endif
            @if(!empty($climate['months']))
              <span class="b-chip">{{ $climate['months'] }}</span>
            @endif
            @if(!empty($climate['temperature']))
              <span class="b-chip">{{ $climate['temperature'] }}</span>
            @endif
            @if(!empty($climate['humidity']))
              <span class="b-chip">{{ $climate['humidity'] }}</span>
            @endif
          </div>
        </div>
        @if(!empty($climate['recommendation']))
          <div class="b-reco">
            <svg width="13" height="13" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:6px;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.72c.6.56 1 1.88 1 2.28h6c0-.4.4-1.72 1-2.28A7 7 0 0 0 12 2z"/></svg>
            {{ $climate['recommendation'] }}
          </div>
        @endif
      </div>

      <div class="sea-grid">
        @foreach($seasons as $season)
          @php $key = $season['icon'] === 'sun' ? 'dry' : 'wet'; @endphp
          <div class="sea-card {{ isset($season['current']) && $season['current'] ? 'current' : '' }}">
            <div class="sea-card-head">
              <div class="sea-card-icon {{ $key }}">
                @if($key === 'wet')
                  <svg viewBox="0 0 24 24"><path d="M20 16.58A5 5 0 0 0 18 7h-1.26A8 8 0 1 0 4 15.25"/><path d="M8 13l-3 3M16 13l-3 3M12 9l-3 3M12 9v5"/></svg>
                @else
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                @endif
              </div>
              <div>
                <div class="sea-card-title">{{ $season['label'] }}</div>
                <div class="sea-card-months">{{ $season['months'] }}</div>
              </div>
            </div>
            <div class="sea-stats">
              <div class="sea-stat"><b>{{ $season['formulas'] }}</b><span>Formulas</span></div>
              <div class="sea-stat"><b>{{ $season['crops'] }}</b><span>Crops</span></div>
              <div class="sea-stat"><b>{{ $season['quotations'] }}</b><span>Estimates</span></div>
            </div>
            <div class="sea-card-body">
              <p>{{ $season['typical_weather'] ?? '' }}</p>
              <div class="reco">{{ $season['recommendation'] ?? '' }}</div>
            </div>
          </div>
        @endforeach
      </div>

    </div>
  </div>
@endsection