@extends('layouts.sidebar')

@section('title', 'Catalog & Reviews')

@section('content')

  <style>
    .cat-page { position: relative; }
    .cat-page::before,
    .cat-page::after {
      content: ""; position: fixed; border-radius: 50%; filter: blur(60px); pointer-events: none; z-index: 0;
    }
    .cat-page::before { width: 340px; height: 340px; background: rgba(147,51,234,0.12); top: -80px; right: -80px; }
    .cat-page::after { width: 300px; height: 300px; background: rgba(16,185,129,0.14); bottom: -60px; left: -60px; }
    .cat-content { position: relative; z-index: 1; }

    .cat-head { margin: 0 0 10px; }
    .cat-head h1 { font-size: 24px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; letter-spacing: 0.02em; }
    .cat-head p { font-size: 13px; color: #64748b; margin: 0; }

    .cat-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .cat-kpi {
      padding: 16px 18px; border-radius: 14px; background: rgba(255,255,255,0.84);
      backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); box-shadow: 0 4px 18px rgba(0,0,0,0.05);
    }
    .cat-kpi .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
    .cat-kpi .kpi-value { font-size: 24px; font-weight: 800; color: #143d23; margin-top: 6px; line-height: 1; }
    .cat-kpi .kpi-sub { font-size: 11.5px; color: #64748b; margin-top: 6px; }

    .cat-grid { display: grid; grid-template-columns: 340px 1fr; gap: 20px; align-items: start; margin-bottom: 22px; }
    .cat-panel {
      background: rgba(255,255,255,0.86); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);
      border: 1px solid rgba(255,255,255,0.55); border-radius: 18px;
      box-shadow: 0 6px 28px rgba(0,0,0,0.05), 0 1px 4px rgba(0,0,0,0.03);
      overflow: hidden;
    }
    .cat-panel .panel-head { padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,0.05); }
    .cat-panel .panel-head h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; letter-spacing: 0.03em; }
    .cat-panel .panel-head p { font-size: 12px; color: #64748b; margin: 2px 0 0 0; }
    .cat-panel .panel-body { padding: 16px 20px; }

    .cat-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 0; }
    .cat-row + .cat-row { border-top: 1px outset rgba(0,0,0,0.04); }
    .cat-row .lbl { font-size: 13px; color: #475569; font-weight: 600; }
    .cat-track { flex: 1; max-width: 130px; height: 8px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
    .cat-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #86efac, #16a34a); }

    .rev-item { display: flex; gap: 14px; padding: 16px 20px; }
    .rev-item + .rev-item { border-top: 1px solid #f1f5f9; }
    .rev-avatar {
      width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 700; font-size: 13px;
    }
    .rev-body { flex: 1; min-width: 0; }
    .rev-top { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .rev-top b { font-size: 13.5px; color: #1e293b; }
    .rev-product { font-size: 12px; color: #16a34a; font-weight: 600; }
    .rev-stars { color: #f59e0b; font-size: 12px; letter-spacing: 1px; }
    .rev-comment { font-size: 13px; color: #475569; line-height: 1.5; margin-top: 6px; }
    .rev-meta { font-size: 11.5px; color: #94a3b8; margin-top: 6px; }
    .rev-actions { display: flex; gap: 8px; flex-shrink: 0; align-items: center; }
    .btn-mod {
      display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
      border: 1px solid rgba(20,61,35,0.12); background: #fff; color: #14532d;
      padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 700;
      transition: all 0.18s ease;
    }
    .btn-mod:hover { transform: translateY(-1px); }
    .btn-mod.approve { border-color: rgba(22,163,74,0.3); }
    .btn-mod.approve:hover { background: #f0fdf4; }
    .btn-mod.reject { color: #a86464; border-color: rgba(220,38,38,0.22); }
    .btn-mod.reject:hover { background: #fef2f2; }
    .rev-status {
      display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px;
      border-radius: 999px; font-size: 11px; font-weight: 600;
    }
    .rev-status.pending { background: #fff7ed; color: #b45309; }
    .rev-status.approved { background: #eef3ef; color: #63856c; }
    .rev-status.rejected { background: #f6ecec; color: #a86464; }

    .cat-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 40px 20px; color: #94a3b8; font-size: 13px; text-align: center; }
    .cat-empty svg { width: 34px; height: 34px; stroke: #c3cec7; fill: none; stroke-width: 1.5; stroke-linecap: round; stroke-linejoin: round; }

    @media (max-width: 920px) {
      .cat-grid { grid-template-columns: 1fr; }
      .rev-actions { flex-direction: column; align-items: stretch; }
    }
  </style>

  <div class="cat-page">
    <div class="cat-content">

      <div class="cat-head">
        <h1>Farm Supply Catalog &amp; Reviews</h1>
        <p>Overview of the supply catalog and moderation of farmer product reviews.</p>
      </div>

      @include('partials.admin-tabs', [
        'tabs' => [
          ['key' => 'formulas', 'label' => 'Formulas',          'url' => route('admin.operations.formulas')],
          ['key' => 'catalog',  'label' => 'Catalog & Reviews', 'url' => route('admin.operations.catalog')],
          ['key' => 'seasons',  'label' => 'Seasons',           'url' => route('admin.operations.seasons')],
        ],
        'activeTab' => 'catalog',
      ])

      <div class="cat-kpis">
        <div class="cat-kpi">
          <div class="kpi-label">Total Products</div>
          <div class="kpi-value">{{ $productStats['total'] }}</div>
          <div class="kpi-sub">Registered supply items</div>
        </div>
        <div class="cat-kpi">
          <div class="kpi-label">Active Listings</div>
          <div class="kpi-value">{{ $productStats['active'] }}</div>
          <div class="kpi-sub">Visible to farmers</div>
        </div>
        <div class="cat-kpi">
          <div class="kpi-label">Out of Stock</div>
          <div class="kpi-value">{{ $productStats['out'] }}</div>
          <div class="kpi-sub">Low / zero inventory</div>
        </div>
        <div class="cat-kpi">
          <div class="kpi-label">Pending Reviews</div>
          <div class="kpi-value">{{ $reviewCounts['pending'] }}</div>
          <div class="kpi-sub">Awaiting moderation</div>
        </div>
      </div>

      <div class="cat-grid">
        <div class="cat-panel">
          <div class="panel-head">
            <h3>Products by Category</h3>
            <p>Distribution of listed supply items</p>
          </div>
          <div class="panel-body">
            @if($productsByCategory->isEmpty())
              <div class="cat-empty">No catalog data yet.</div>
            @else
              @php $catMax = max($productsByCategory->max('count'), 1) ?: 1; @endphp
              @foreach($productsByCategory as $cat)
                @php $pct = $catMax > 0 ? round(($cat->count / $catMax) * 100) : 0; @endphp
                <div class="cat-row">
                  <span class="lbl">{{ $cat->category }}</span>
                  <div class="cat-track"><div class="cat-fill" style="width: {{ $pct }}%;"></div></div>
                  <span style="font-size:13px;font-weight:800;color:#1e2922;width:34px;text-align:right;">{{ $cat->count }}</span>
                </div>
              @endforeach
            @endif
          </div>
        </div>

        <div class="cat-panel">
          <div class="panel-head">
            <h3>Review Queue</h3>
            <p>Latest farmer reviews across stores — approve or reject</p>
          </div>
          @if($reviews->isEmpty())
            <div class="cat-empty">No reviews recorded yet.</div>
          @else
            @foreach($reviews as $review)
              @php
                $statusChip = match ($review->status) {
                    'approved' => 'approved',
                    'rejected' => 'rejected',
                    default   => 'pending',
                };
                $statusLabel = ucfirst($review->status);
                $initials = strtoupper(substr($review->farmer?->name ?? 'U', 0, 1));
                $bg = ['#16a34a', '#2563eb', '#7c3aed', '#d97706'][((int) $review->id) % 4];
              @endphp
              <div class="rev-item">
                <div class="rev-avatar" style="background:{{ $bg }};">{{ $initials }}</div>
                <div class="rev-body">
                  <div class="rev-top">
                    <b>{{ $review->farmer?->name ?? 'Unknown Farmer' }}</b>
                    <span class="rev-product">{{ $review->product?->display_name ?? 'Unknown Product' }}</span>
                    <span class="rev-stars">{{ str_repeat('★', $review->rating) }}<span style="color:#e2e8f0;">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                  </div>
                  @if($review->comment)
                    <div class="rev-comment">{{ $review->comment }}</div>
                  @endif
                  <div class="rev-meta">
                    {{ $review->product?->storeOwner?->name ?? 'Location store' }} · {{ $review->created_at?->format('M d, Y h:i A') }}
                  </div>
                </div>
                <div class="rev-actions">
                  <span class="rev-status {{ $statusChip }}">{{ $statusLabel }}</span>
                  @if($review->status === 'pending')
                    <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                      @csrf
                      <button type="submit" class="btn-mod approve">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.reviews.disapprove', $review) }}">
                      @csrf
                      <button type="submit" class="btn-mod reject">Reject</button>
                    </form>
                  @endif
                </div>
              </div>
            @endforeach
          @endif
        </div>
      </div>

    </div>
  </div>
@endsection