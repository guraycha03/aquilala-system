@extends('layouts.sidebar')

@section('title', 'Cost Quotation ' . $quotation->quotation_number)

@section('content')
@php
  $total      = (float) $quotation->estimated_total_cost;
  $landArea   = (float) $quotation->land_area;
  $perHectare = $landArea > 0 ? round($total / $landArea, 2) : $total;
  $daysLeft   = $quotation->valid_until ? (int) max(0, now()->diffInDays($quotation->valid_until, false)) : null;
  $effStatus  = $quotation->effective_status;
  $storeOwners = $quotation->items->pluck('product.storeOwner')->filter()->unique('id');

  // Field-condition chips for the header and plan rows.
  $soilLabels = [
      'clay_loam' => 'Clay loam soil',
      'sandy'     => 'Sandy soil',
      'peat'      => 'Peat soil',
  ];
  $riskLabels = [
      'normal'   => 'Normal conditions',
      'low_rain' => 'Low rainfall',
      'typhoon'  => 'Typhoon risk',
      'el_nino'  => 'El Niño drought',
  ];
  $soilLabel = $quotation->soil_type
      ? ($soilLabels[$quotation->soil_type] ?? ucfirst(str_replace('_', ' ', $quotation->soil_type)))
      : null;
  $riskLabel = $quotation->weather_context
      ? ($riskLabels[$quotation->weather_context] ?? ucfirst(str_replace('_', ' ', $quotation->weather_context)))
      : null;

  // Budget outcome, derived from the stored quotation (the engine's
  // summary is not persisted wholesale): cap + fitted total + deferrals.
  $deferredItems = $quotation->items->where('is_deferred', true);
  $deferredCount = $deferredItems->count();
  $fittedCount   = $quotation->items->count() - $deferredCount;
  $budgetCap     = $quotation->budget_cap !== null ? (float) $quotation->budget_cap : null;
  // Fit mode's hard ceiling: twice the stated budget — the same rule the
  // engine fits against (mirrored here because only the persisted cap,
  // total, and strict flag reach the view).
  $budgetStrict  = (bool) $quotation->budget_strict;
  $budgetCeiling = $budgetCap !== null
    ? round($budgetCap * ($budgetStrict ? 2 : 1), 2)
    : null;
  // Over the stated budget but inside the allowance: fit mode's flexible
  // band — fitted, not short.
  $flexBand      = $budgetCap !== null && $budgetStrict
    && $total > $budgetCap && $total <= $budgetCeiling;
  $shortfall     = $budgetCap !== null && $total > $budgetCeiling
    ? round($total - $budgetCeiling, 2) : null;
  $budgetLeft    = $budgetCap !== null && $total <= $budgetCap
    ? round($budgetCap - $total, 2) : null;
  $deferredTotal = (float) $deferredItems->sum('subtotal');

  // "Nothing fits" state (strict budget mode priced nothing inside the
  // ceiling): every line on the quotation is deferred. The floor the plan
  // would need is the essential minimum, or the deferred sum when the
  // plan is all optional lines.
  $essentialMin = (float) $quotation->items->where('priority', '!=', 'optional')->sum('subtotal');
  $fitFloor     = $essentialMin > 0 ? $essentialMin : $deferredTotal;
  $unreachable  = $budgetCap !== null && $quotation->items->isNotEmpty() && $fittedCount === 0;
  $unreachableShortfall = $unreachable ? round($fitFloor - (float) $budgetCeiling, 2) : null;
  $isRed       = $unreachable || $shortfall;

  // The plan's own lines, in the order they were priced. Each one is
  // shown as a readable row: what it is, where it comes from, how much
  // of it, and what it costs.
  $planLines = $quotation->items->map(function ($item) {
      $unit = strtolower(trim($item->product?->stock_unit ?? 'unit'));
      if ($unit === 'piece') { $unit = 'unit'; }

      $quantity = (float) $item->calculated_quantity;

      // "2 sacks", not "2.00 sacks": a farmer counts whole bags, and one
      // bag is not "1 bags".
      $shown = rtrim(rtrim(number_format($quantity, 2), '0'), '.');
      if ($quantity == 1 && substr($unit, -1) === 's') { $unit = substr($unit, 0, -1); }

      return [
          'name'      => $item->product_name,
          'store'     => $item->product?->storeOwner?->name ?? null,
          'quantity'  => $shown,
          'unit'      => $unit,
          'price'     => (float) $item->price_at_generation,
          'subtotal'  => (float) $item->subtotal,
          'deferred'  => (bool) $item->is_deferred,
      ];
  })->values();
@endphp
<style>
  /* ── Page shell ────────────────────────────────────────────────
     One soft surface, one border, one accent. Everything on this
     page is a card on the same muted ground, so the page reads as
     a set of things rather than a wall of text. Colours live in
     classes (never inline) so the dark theme can repaint them. */
  .fq-card {
    background: #ffffff;
    border: 1px solid #E9EDE8;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03), 0 6px 20px rgba(0,0,0,0.025);
    margin-bottom: 22px;
    overflow: hidden;
  }
  .fq-card__head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 14px; flex-wrap: wrap;
    padding: 16px 22px;
    border-bottom: 1px solid #F0F2F1;
    background: #FAFDFB;
  }
  .fq-card__title { font-size: 15px; font-weight: 700; color: #1a2b1e; margin: 0; }
  .fq-card__sub { font-size: 12.5px; color: #879a8c; margin: 3px 0 0 0; }
  .fq-card__body { padding: 22px; }

  /* A card head that opens and closes its own body. */
  .fq-toggle {
    width: 100%; border: none; border-bottom: 1px solid #F0F2F1;
    background: #FAFDFB; cursor: pointer; font-family: inherit; text-align: left;
  }
  .fq-toggle:hover { background: #F4F8F4; }
  .fq-toggle span { display: block; }
  .fq-toggle__chev {
    width: 18px; height: 18px; flex-shrink: 0; color: #879a8c;
    transition: transform 0.2s ease;
  }
  .fq-toggle.is-open .fq-toggle__chev { transform: rotate(180deg); }
  .fq-toggle.is-open { border-bottom-color: #E9EDE8; }

  /* Header block */
  .fq-head {
    background: #F3F5F0;
    border: 1px solid #E0E5DB;
    border-radius: 16px;
    padding: 24px 26px;
    margin-bottom: 20px;
  }
  .fq-head__row {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 18px; flex-wrap: wrap;
  }
  .fq-title {
    font-size: 24px; font-weight: 800; color: #1a2b1e;
    margin: 0 0 6px 0; letter-spacing: -0.02em;
    font-family: 'SF Mono', ui-monospace, monospace;
  }
  .fq-subtitle { color: #5c6d61; font-size: 14px; margin: 0; line-height: 1.55; }
  .fq-subtitle strong { color: #143d23; text-transform: capitalize; }

  /* The one-line note under the title: what the prices are, and
     nothing else. */
  .fq-note {
    display: inline-flex; align-items: center; gap: 7px;
    margin: 12px 0 0 0;
    font-size: 12.5px; color: #6b7c6e; line-height: 1.5;
  }
  .fq-note svg { width: 14px; height: 14px; color: #8fae97; flex-shrink: 0; }

  .fq-chips { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; margin-bottom: 10px; }
  .fq-status {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 14px; border-radius: 22px;
    font-size: 12.5px; font-weight: 700;
  }
  .fq-status .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
  .fq-status.computed { background: #EDF1E9; color: #4b5c50; }
  .fq-status.viewed   { background: #EDF1E9; color: #4b5c50; }
  .fq-status.expired  { background: #F1F3F1; color: #8a948d; }
  .fq-validity { font-size: 12.5px; color: #879a8c; }
  .fq-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 11px; border-radius: 20px;
    background: #ffffff; border: 1px solid #E0E5DB;
    font-size: 11.5px; font-weight: 700; color: #4a6353;
  }
  .fq-chip .dot { width: 7px; height: 7px; border-radius: 50%; background: #8fae97; flex-shrink: 0; }

  /* ── The summary: one number, three facts ──────────────────────
     A soft matte card, not a spotlight. The price leads; the facts
     sit beside it as quiet tiles. */
  .fq-sum {
    display: flex; align-items: center; justify-content: space-between;
    gap: 24px; flex-wrap: wrap;
    background: #ffffff;
    border: 1px solid #E9EDE8;
    border-radius: 16px;
    padding: 24px 26px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03), 0 6px 20px rgba(0,0,0,0.025);
  }
  .fq-sum__label {
    font-size: 11.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: #879a8c; margin-bottom: 8px;
  }
  .fq-sum__value {
    font-size: 38px; font-weight: 800; color: #1a2b1e;
    letter-spacing: -0.025em; line-height: 1.05;
  }
  .fq-sum__sub { font-size: 13px; color: #6b7c6e; margin-top: 7px; }
  .fq-sum__stats { display: flex; gap: 12px; flex-wrap: wrap; }
  .fq-stat {
    display: flex; align-items: center; gap: 11px;
    background: #F3F5F0; border: 1px solid #E4E9E0;
    border-radius: 13px; padding: 12px 16px; min-width: 148px;
  }
  .fq-stat__ic {
    width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0;
    background: #E7EDE5; color: #648268;
    display: flex; align-items: center; justify-content: center;
  }
  .fq-stat__ic svg { width: 16px; height: 16px; }
  .fq-stat__label {
    display: block; font-size: 10.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em; color: #879a8c;
  }
  .fq-stat__value { display: block; font-size: 15.5px; font-weight: 800; color: #1a2b1e; margin-top: 1px; }
  .fq-stat__note { display: block; font-size: 11.5px; color: #85620f; font-weight: 700; margin-top: 1px; }

  /* ── Buttons ────────────────────────────────────────────────── */
  .fq-btn {
    display: inline-flex; align-items: center; gap: 7px;
    border-radius: 11px; font-size: 13px; font-weight: 700; font-family: inherit;
    padding: 10px 16px; cursor: pointer; text-decoration: none;
    transition: background 0.15s, border-color 0.15s, transform 0.15s;
  }
  .fq-btn svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2.2; stroke-linecap: round; stroke-linejoin: round; }
  .fq-btn--primary { background: #F3F5F0; color: #143d23; border: 1px solid #D5DDD1; }
  .fq-btn--primary:hover { background: #E9EEE5; border-color: #C4D0C0; }
  .fq-btn--ghost { background: #ffffff; color: #4b5c50; border: 1px solid #E0E5DB; }
  .fq-btn--ghost:hover { background: #F3F5F0; }
  .fq-btn--danger { background: #FAF3F2; color: #a34a4a; border: 1px solid #E8D2CF; }
  .fq-btn--danger:hover { background: #F6E9E7; }

  /* ── Budget outcome ─────────────────────────────────────────── */
  .fq-budget {
    display: flex; gap: 13px; align-items: flex-start;
    border: 1.5px solid; border-radius: 14px;
    padding: 16px 18px; margin-bottom: 22px;
  }
  .fq-budget__ic {
    width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0; margin-top: 1px;
    display: flex; align-items: center; justify-content: center;
  }
  .fq-budget__ic svg { width: 16px; height: 16px; }
  .fq-budget__title { display: block; font-size: 14px; font-weight: 800; margin-bottom: 3px; }
  .fq-budget__text { margin: 0; font-size: 13px; line-height: 1.6; }
  .fq-budget__list {
    list-style: none; margin: 10px 0 0; padding: 0;
    display: flex; flex-wrap: wrap; gap: 7px;
  }
  .fq-budget__list li {
    font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 8px;
    background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(30, 41, 34, 0.10);
    color: inherit;
  }
  .fq-budget.fitted  { background: #F1F6F1; border-color: #D3E3D5; }
  .fq-budget.fitted .fq-budget__title { color: #3f6b4d; }
  .fq-budget.fitted .fq-budget__text { color: #4a6353; }
  .fq-budget.fitted .fq-budget__ic { background: #E7EDE5; color: #648268; }
  .fq-budget.trimmed { background: #FAF8F0; border-color: #E8E2CE; }
  .fq-budget.trimmed .fq-budget__title { color: #7A6A3A; }
  .fq-budget.trimmed .fq-budget__text { color: #7a6e3f; }
  .fq-budget.trimmed .fq-budget__ic { background: #F2EEDF; color: #8A7C3E; }
  .fq-budget.over { background: #FAF3F2; border-color: #E8D2CF; }
  .fq-budget.over .fq-budget__title { color: #a34a4a; }
  .fq-budget.over .fq-budget__text { color: #8c524b; }
  .fq-budget.over .fq-budget__ic { background: #F4E3E1; color: #a34a4a; }

  /* ── AI adviser: cards, not a document ───────────────────────── */
  .fq-ai__meta {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    margin-bottom: 16px;
  }
  .fq-ai__note { font-size: 12px; color: #879a8c; }
  .fq-ai__cards { display: flex; flex-direction: column; gap: 12px; }

  .fq-aicard {
    border: 1px solid #E9EDE8; border-radius: 13px;
    background: #FCFDFC; overflow: hidden;
  }
  .fq-aicard__head {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; border-bottom: 1px solid #F0F2F1;
  }
  .fq-aicard__ic {
    width: 26px; height: 26px; border-radius: 9px; flex-shrink: 0;
    background: #EDF1E9; color: #648268;
    display: flex; align-items: center; justify-content: center;
  }
  .fq-aicard__ic svg { width: 14px; height: 14px; }
  .fq-aicard__head h3 {
    margin: 0; font-size: 13.5px; font-weight: 700; color: #1a2b1e;
  }
  .fq-aicard__body { padding: 14px 16px; }
  .fq-aicard--takeaway { background: #F4F8F4; border-color: #D6E6D8; }
  .fq-aicard--takeaway .fq-aicard__head { background: #EDF4EE; border-bottom-color: #D9E7DA; }
  .fq-aicard--takeaway .fq-aicard__ic { background: #E1EDE3; color: #4f7a5c; }
  .fq-aicard--warn { background: #FAF8F0; border-color: #E8E2CE; }
  .fq-aicard--warn .fq-aicard__head { background: #F6F2E6; border-bottom-color: #E8E2CE; }
  .fq-aicard--warn .fq-aicard__ic { background: #F2EEDF; color: #8A7C3E; }

  .fq-ai-text { font-size: 13.5px; color: #46554c; line-height: 1.65; }
  .fq-ai-text p { margin: 0 0 9px 0; }
  .fq-ai-text p:last-child { margin-bottom: 0; }
  .fq-ai-text strong { color: #143d23; font-weight: 700; }
  .fq-ai-text code {
    background: #EFF3EE; border-radius: 5px; padding: 1px 5px;
    font-family: 'SF Mono', ui-monospace, monospace; font-size: 12.5px;
  }

  .fq-ai-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
  .fq-ai-list li { display: flex; gap: 10px; align-items: flex-start; }
  .fq-ai-list__ic {
    width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0; margin-top: 1px;
    background: #EDF1E9; color: #648268;
    display: flex; align-items: center; justify-content: center;
  }
  .fq-ai-list__ic svg { width: 12px; height: 12px; }

  .fq-ai-steps { display: flex; flex-direction: column; gap: 10px; }
  .fq-ai-step {
    display: flex; gap: 11px; align-items: flex-start;
    border: 1px solid #EDEFEE; border-radius: 11px;
    background: #ffffff; padding: 11px 13px;
  }
  .fq-ai-step__n {
    width: 23px; height: 23px; border-radius: 8px; flex-shrink: 0;
    background: #EDF1E9; color: #4b5c50;
    font-size: 11.5px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
  }

  /* ── The plan's lines ───────────────────────────────────────── */
  .fq-plan { display: flex; flex-direction: column; gap: 10px; }
  .fq-plan__row {
    display: grid; grid-template-columns: 1fr auto auto;
    align-items: center; gap: 16px;
    border: 1px solid #E9EDE8; border-radius: 12px;
    background: #ffffff; padding: 13px 16px;
  }
  .fq-plan__row.is-deferred { background: #FAF9F4; border-color: #EAE5D6; }
  .fq-plan__name { font-size: 13.5px; font-weight: 700; color: #1a2b1e; }
  .fq-plan__meta { font-size: 12.5px; color: #879a8c; margin-top: 2px; }
  .fq-plan__qty {
    font-size: 13px; color: #4b5c50; font-weight: 600; text-align: right;
    white-space: nowrap;
  }
  .fq-plan__sum {
    font-size: 14.5px; font-weight: 800; color: #143d23;
    text-align: right; min-width: 92px; white-space: nowrap;
  }
  .fq-plan__row.is-deferred .fq-plan__sum { color: #8A7C3E; }
  .fq-plan__tag {
    display: inline-block; margin-left: 7px; padding: 2px 8px; border-radius: 7px;
    background: #F2EEDF; color: #7A6A3A; font-size: 10.5px; font-weight: 700;
    vertical-align: middle;
  }
  .fq-plan__total {
    display: flex; justify-content: space-between; align-items: baseline;
    gap: 14px; flex-wrap: wrap;
    margin-top: 14px; padding-top: 14px; border-top: 1px dashed #E9EDE8;
  }
  .fq-plan__total-label { font-size: 12px; font-weight: 700; color: #6b7c6e; text-transform: uppercase; letter-spacing: 0.05em; }
  .fq-plan__total-value { font-size: 20px; font-weight: 800; color: #1a2b1e; }
  .fq-plan__note { margin: 8px 0 0 0; font-size: 12.5px; color: #879a8c; line-height: 1.5; }
  .fq-plan__more { display: flex; justify-content: center; width: 100%; margin-top: 16px; }

  /* ── Facts and stores ───────────────────────────────────────── */
  .fq-cols { display: grid; grid-template-columns: 1.5fr 1fr; gap: 22px; align-items: start; }
  @media (max-width: 980px) { .fq-cols { grid-template-columns: 1fr; } }
  .fq-ai-card { margin-bottom: 22px; }

  .fq-facts {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 18px 22px; padding: 22px;
  }
  .fq-fact__label {
    display: block; font-size: 10.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.07em; color: #879a8c;
    margin-bottom: 5px;
  }
  .fq-fact__value { font-size: 14.5px; font-weight: 700; color: #1a2b1e; }
  .fq-fact__value--muted { color: #4b5c50; }
  .fq-fact__value--warn { color: #a34a4a; }
  .fq-fact__value--soft { color: #7A6A3A; }
  .fq-season-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; margin-right: 7px; }
  .fq-season-dot.wet { background: #7f9dae; }
  .fq-season-dot.dry { background: #bfa262; }

  .fq-notes {
    margin: 0 22px 22px; padding: 14px 16px;
    background: #F7F9F7; border: 1px solid #E9EDE8; border-radius: 12px;
  }
  .fq-notes__label {
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.07em; color: #879a8c; margin-bottom: 4px;
  }
  .fq-notes__text { margin: 0; font-size: 13.5px; color: #46554c; line-height: 1.6; }

  .fq-stores { display: flex; flex-direction: column; gap: 10px; padding: 20px 22px 22px; }
  .fq-store {
    display: flex; align-items: center; gap: 12px;
    border: 1px solid #E9EDE8; border-radius: 12px; padding: 11px 13px;
  }
  .fq-store__name { font-weight: 700; color: #1a2b1e; font-size: 13.5px; }
  .fq-store__meta { font-size: 12px; color: #879a8c; margin-top: 2px; }
  .fq-empty { padding: 26px 22px; color: #879a8c; font-size: 13.5px; margin: 0; }

  /* ── Flash ──────────────────────────────────────────────────── */
  .fq-flash {
    display: flex; align-items: center; gap: 10px;
    background: #F1F6F1; border: 1px solid #D3E3D5; border-radius: 12px;
    padding: 13px 17px; margin-bottom: 20px;
  }
  .fq-flash__ic {
    width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0;
    background: #E1EDE3; color: #4f7a5c;
    display: flex; align-items: center; justify-content: center;
  }
  .fq-flash__ic svg { width: 15px; height: 15px; }
  .fq-flash__text { font-size: 14px; color: #3f6b4d; font-weight: 600; }

  /* ── Phone ──────────────────────────────────────────────────── */
  @media (max-width: 640px) {
    .fq-head { padding: 18px 16px; }
    .fq-title { font-size: 20px; }

    /* The total card stacks on a phone: the price on top, the three
       facts as an even row underneath. Nothing is squeezed into a
       column of its own. */
    .fq-sum {
      flex-direction: column; align-items: stretch;
      gap: 18px; padding: 20px 16px;
    }
    .fq-sum__value { font-size: 31px; }
    .fq-sum__stats {
      display: grid; grid-template-columns: repeat(3, 1fr);
      gap: 8px; width: 100%;
    }
    .fq-stat {
      flex-direction: column; align-items: flex-start; gap: 7px;
      min-width: 0; padding: 11px 12px;
    }
    .fq-stat__ic { width: 28px; height: 28px; border-radius: 9px; }
    .fq-stat__ic svg { width: 15px; height: 15px; }
    .fq-stat__label { font-size: 9.5px; letter-spacing: 0.05em; }
    .fq-stat__value { font-size: 14.5px; }
    .fq-stat__note { font-size: 11px; }

    .fq-card__body { padding: 16px; }
    .fq-facts { grid-template-columns: repeat(2, 1fr); gap: 16px; padding: 18px 16px; }
    .fq-notes { margin: 0 16px 18px; }
    .fq-stores { padding: 16px; }
    /* The plan line stacks on a phone: what it is on the left, how much
       and what it costs on the right. */
    .fq-plan__row { grid-template-columns: 1fr auto; gap: 4px 12px; }
    .fq-plan__row > *:first-child { grid-column: 1; grid-row: 1 / span 2; }
    .fq-plan__qty { grid-column: 2; grid-row: 1; text-align: right; }
    .fq-plan__sum { grid-column: 2; grid-row: 2; text-align: right; min-width: 0; }
  }
  @media (max-width: 380px) {
    .fq-sum__stats { grid-template-columns: repeat(2, 1fr); }
  }
</style>

<x-breadcrumb :items="[
  ['label' => 'My Cost Quotations', 'url' => route('farmer.quotations.index')],
  ['label' => $quotation->quotation_number],
]"/>

{{-- The AI card and the window it opens: one shared set of rules, so the
     farmer's quotation page and the store's demand page read the same. --}}
@include('partials.ai-explanation-ui')

@if(session('success'))
  <div class="fq-flash">
    <span class="fq-flash__ic">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </span>
    <span class="fq-flash__text">{{ session('success') }}</span>
  </div>
@endif

<!-- Header: what this quotation is -->
<div class="fq-head">
  <div class="fq-head__row">
    <div>
      <div class="fq-chips">
        <span class="fq-status {{ $effStatus }}">
          <span class="dot"></span>
          {{ $quotation->status_label }}
        </span>
        <span class="fq-validity">
          @if($quotation->valid_until)
            @if($effStatus === 'expired')
              Expired {{ $quotation->valid_until->format('M d, Y') }}
            @elseif(is_numeric($daysLeft))
              Valid until {{ $quotation->valid_until->format('M d, Y') }} &middot; {{ $daysLeft }} day(s) left
            @else
              Valid until {{ $quotation->valid_until->format('M d, Y') }}
            @endif
          @else
            No expiration set
          @endif
        </span>
        @if($soilLabel)
          <span class="fq-chip"><span class="dot"></span>{{ $soilLabel }}</span>
        @endif
        @if($riskLabel)
          <span class="fq-chip"><span class="dot"></span>{{ $riskLabel }}</span>
        @endif
      </div>
      <h1 class="fq-title">{{ $quotation->quotation_number }}</h1>
      <p class="fq-subtitle">
        <strong>{{ $quotation->crop_type }}</strong>
        &middot; {{ ucfirst($quotation->season) }} Season &middot; {{ number_format($landArea, 2) }} ha
        &middot; generated {{ $quotation->created_at->format('M d, Y \a\t g:i A') }}
      </p>
      {{-- One line, and only what the farmer needs to know here: the
           prices are a snapshot taken when the estimate was made. --}}
      <p class="fq-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Prices are based on store prices at the time this quotation was made.
      </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <a href="{{ route('farmer.quotations.breakdown', $quotation) }}" class="fq-btn fq-btn--primary">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Full Itemized Breakdown
      </a>
      <form action="{{ route('farmer.quotations.destroy', $quotation) }}" method="POST"
            data-aqui-confirm="Are you sure you want to delete this cost quotation? This cannot be undone."
            data-aqui-danger data-aqui-confirm-label="Delete" style="margin: 0;">
        @csrf
        @method('DELETE')
        <button type="submit" class="fq-btn fq-btn--danger">
          <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          Delete
        </button>
      </form>
    </div>
  </div>
</div>

<!-- The one number that matters, and the three facts around it -->
<div class="fq-sum">
  <div>
    <div class="fq-sum__label">Estimated total cost</div>
    <div class="fq-sum__value">₱{{ number_format($total, 2) }}</div>
    <div class="fq-sum__sub">≈ ₱{{ number_format($perHectare, 2) }} per hectare</div>
  </div>
  <div class="fq-sum__stats">
    <div class="fq-stat">
      <span class="fq-stat__ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"/><path d="M15 5.764v15"/><path d="M9 3.236v15"/></svg>
      </span>
      <span>
        <span class="fq-stat__label">Land area</span>
        <span class="fq-stat__value">{{ number_format($landArea, 2) }} ha</span>
      </span>
    </div>
    <div class="fq-stat">
      <span class="fq-stat__ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
      </span>
      <span>
        <span class="fq-stat__label">Line items</span>
        <span class="fq-stat__value">{{ $fittedCount }} @if($deferredCount) <span class="fq-stat__note">{{ $deferredCount }} deferred</span> @endif</span>
      </span>
    </div>
    <div class="fq-stat">
      <span class="fq-stat__ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7h20"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="m2 7 3-3h14l3 3"/></svg>
      </span>
      <span>
        <span class="fq-stat__label">Stores</span>
        <span class="fq-stat__value">{{ max(1, $storeOwners->count()) }}</span>
      </span>
    </div>
  </div>
</div>

<!-- Budget outcome (only when the farmer set a budget cap) -->
@if($budgetCap !== null)
  @if($unreachable)
    <div class="fq-budget over">
      <span class="fq-budget__ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </span>
      <div>
        <b class="fq-budget__title">Nothing fits your ₱{{ number_format($budgetCap, 2) }} budget yet</b>
        <p class="fq-budget__text">The inputs this plan needs start at about ₱{{ number_format($fitFloor, 2) }} for {{ number_format($landArea, 2) }} ha — above your budget, so no items were included. Raise the budget toward ₱{{ number_format($fitFloor, 2) }}, or reduce the farm area, then generate again:</p>
        @if($deferredCount > 0)
          <ul class="fq-budget__list">
            @foreach($deferredItems as $di)
              <li>{{ $di->product_name }} — ₱{{ number_format((float) $di->subtotal, 2) }}</li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  @elseif($shortfall)
    <div class="fq-budget over">
      <span class="fq-budget__ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </span>
      <div>
        <b class="fq-budget__title">Short by ₱{{ number_format($shortfall, 2) }}@if($budgetStrict) — past your ₱{{ number_format($budgetCeiling, 2) }} flexible limit @else — essentials alone exceed your budget @endif</b>
        <p class="fq-budget__text">Your ₱{{ number_format($budgetCap, 2) }} budget @if($budgetStrict) with its ₱{{ number_format($budgetCeiling, 2) }} allowance @endif is below what this plan comes to (₱{{ number_format($total, 2) }}). The plan below keeps what fits — anything extra stays deferred. Adjust quantities, wait for better prices, or revisit before committing.</p>
      </div>
    </div>
  @elseif($flexBand)
    <div class="fq-budget trimmed">
      <span class="fq-budget__ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
      </span>
      <div>
        <b class="fq-budget__title">Within your flexible limit — ₱{{ number_format($total, 2) }} of your ₱{{ number_format($budgetCap, 2) }} budget</b>
        <p class="fq-budget__text">Fit mode allows up to ₱{{ number_format($budgetCeiling, 2) }} — twice your budget — so a must-have that crosses the line can still be part of the plan; the total never goes past that limit.@if($deferredCount > 0) {{ $deferredCount }} input(s) worth ₱{{ number_format($deferredTotal, 2) }} were deferred to stay within it:@endif</p>
        @if($deferredCount > 0)
          <ul class="fq-budget__list">
            @foreach($deferredItems as $di)
              <li>{{ $di->product_name }} — ₱{{ number_format((float) $di->subtotal, 2) }}</li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  @elseif($deferredCount > 0)
    <div class="fq-budget trimmed">
      <span class="fq-budget__ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>
      </span>
      <div>
        <b class="fq-budget__title">Fitted to your ₱{{ number_format($budgetCap, 2) }} budget — ₱{{ number_format((float) $budgetLeft, 2) }} left</b>
        <p class="fq-budget__text">{{ $deferredCount }} input(s) worth ₱{{ number_format($deferredTotal, 2) }} were deferred to fit your budget. They stay on this quotation so you can add them when your budget allows:</p>
        <ul class="fq-budget__list">
          @foreach($deferredItems as $di)
            <li>{{ $di->product_name }} — ₱{{ number_format((float) $di->subtotal, 2) }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  @else
    <div class="fq-budget fitted">
      <span class="fq-budget__ic">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </span>
      <div>
        <b class="fq-budget__title">Fits your ₱{{ number_format($budgetCap, 2) }} budget — ₱{{ number_format((float) $budgetLeft, 2) }} to spare</b>
        <p class="fq-budget__text">Every recommended input fits within the budget you set.</p>
      </div>
    </div>
  @endif
@endif

{{-- The explanation opens in a window: the same card and window the
     store's demand page uses, so the two read the same. --}}
<div class="ai-modal" id="aiModal" role="dialog" aria-modal="true" aria-labelledby="aiModalTitle" aria-hidden="true">
  <div class="ai-modal-head">
    <div>
      <h3 id="aiModalTitle">AI Explanation</h3>
      <p>What this quotation means for your farm</p>
    </div>
    <button type="button" class="ai-modal-close" id="aiModalClose" aria-label="Close explanation" title="Close">
      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <div class="ai-modal-body" id="aiModalBody"></div>
</div>

<style>
  .fq-ai-loading { display: flex; gap: 14px; padding: 4px 2px; }
  .fq-ai-loading__lines { flex: 1; }
  .fq-shimmer {
    height: 12px; border-radius: 6px; margin-bottom: 10px;
    background: linear-gradient(90deg, #f1f3f1 25%, #e7eae7 50%, #f1f3f1 75%);
    background-size: 200% 100%;
    animation: fqShimmer 1.4s infinite;
  }
  @keyframes fqShimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
</style>

<!-- The plan on the left, the things that explain it on the right -->
<div class="fq-cols">
  <div>
<!-- What the plan actually covers -->
<div class="fq-card">
  <div class="fq-card__head">
    <div>
      <h2 class="fq-card__title">What this plan covers</h2>
      <p class="fq-card__sub">Every input in the estimate — what it is, how much, and what it costs</p>
    </div>
  </div>
  <div class="fq-card__body">
    @if($planLines->isNotEmpty())
      <div class="fq-plan">
        @foreach($planLines as $line)
          <div class="fq-plan__row {{ $line['deferred'] ? 'is-deferred' : '' }}">
            <div>
              <div class="fq-plan__name">
                {{ $line['name'] }}
                @if($line['deferred'])
                  <span class="fq-plan__tag">Deferred</span>
                @endif
              </div>
              <div class="fq-plan__meta">
                {{ $line['store'] ?? 'General catalog' }} &middot; ₱{{ number_format($line['price'], 2) }} each
              </div>
            </div>
            <div class="fq-plan__qty">{{ $line['quantity'] }} {{ $line['unit'] }}</div>
            <div class="fq-plan__sum">₱{{ number_format($line['subtotal'], 2) }}</div>
          </div>
        @endforeach
      </div>
      <div class="fq-plan__total">
        <span class="fq-plan__total-label">Estimated total</span>
        <span class="fq-plan__total-value">₱{{ number_format($total, 2) }}</span>
      </div>
      @if($deferredCount > 0)
        <p class="fq-plan__note">
          {{ $deferredCount }} deferred line(s) worth ₱{{ number_format($deferredTotal, 2) }} are not included in this total.
        </p>
      @endif
      <a href="{{ route('farmer.quotations.breakdown', $quotation) }}" class="fq-btn fq-btn--ghost fq-plan__more">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        See the line-by-line breakdown
      </a>
    @else
      <p class="fq-empty" style="padding: 0;">No products matched the formulas for this crop and season.</p>
    @endif
  </div>
</div>
  </div>

  <div>
<!-- AI explanation: a card that opens a window, so the page stays short -->
<div class="ai-kpi-card fq-ai-card">
  <div class="kpi-top">
    <div class="kpi-icon-chip">
      {{-- Lucide "square-sparkles" (1.48.0), inlined verbatim. The bundled
           lucide.min.js is 1.47.0 — one release short — so <i data-lucide>
           would leave an empty tag; the glyph is copied here instead. --}}
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 15H7"/><path d="M15.41 2.49a.6.6 0 011.18 0l.63 3.334a1.2 1.2 0 00.956.955l3.334.631a.6.6 0 010 1.18l-3.334.63a1.2 1.2 0 00-.955.956l-.631 3.334a.6.6 0 01-1.18 0l-.63-3.334a1.2 1.2 0 00-.955-.955L10.49 8.59a.6.6 0 010-1.18l3.334-.63a1.2 1.2 0 00.955-.956z"/><path d="M21 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h6"/><path d="M9 13v4"/></svg>
    </div>
  </div>
  <h3 class="kpi-title">AI Explanation</h3>
  <p class="kpi-desc">What this quotation means for your farm, in plain words.</p>
  <button type="button" class="ai-btn" id="aiOverviewBtn" onclick="generateAiOverview(this)">
    <svg class="ai-btn-icon" viewBox="0 0 24 24" width="13" height="13" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/></svg>
    <span>Generate explanation</span>
  </button>
</div>

<div class="fq-card">
    {{-- Closed by default: the header says what it is, and the details
         open when the farmer asks for them. --}}
    <button type="button" class="fq-card__head fq-toggle" id="planToggle" aria-expanded="false" aria-controls="planFacts">
      <span>
        <span class="fq-card__title">Your plan at a glance</span>
        <span class="fq-card__sub">The details behind the total</span>
      </span>
      <svg class="fq-toggle__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
    </button>
    <div id="planFacts" hidden>
    <div class="fq-facts">
      <div>
        <span class="fq-fact__label">Crop</span>
        <span class="fq-fact__value" style="text-transform: capitalize;">{{ $quotation->crop_type }}</span>
      </div>
      <div>
        <span class="fq-fact__label">Season</span>
        <span class="fq-fact__value">
          <span class="fq-season-dot {{ $quotation->season === 'wet' ? 'wet' : 'dry' }}"></span>{{ ucfirst($quotation->season) }} Season
        </span>
      </div>
      @if($soilLabel)
        <div>
          <span class="fq-fact__label">Soil type</span>
          <span class="fq-fact__value fq-fact__value--muted">{{ $soilLabel }}</span>
        </div>
      @endif
      @if($riskLabel)
        <div>
          <span class="fq-fact__label">Season risk</span>
          <span class="fq-fact__value fq-fact__value--muted">{{ $riskLabel }}</span>
        </div>
      @endif
      @if($budgetCap !== null)
        <div>
          <span class="fq-fact__label">Budget</span>
          <span class="fq-fact__value {{ $isRed ? 'fq-fact__value--warn' : 'fq-fact__value--muted' }}">₱{{ number_format($budgetCap, 2) }}</span>
        </div>
      @endif
      <div>
        <span class="fq-fact__label">Selected inputs</span>
        <span class="fq-fact__value">{{ $fittedCount }} item(s) @if($deferredCount) <span class="fq-fact__value--soft">&middot; {{ $deferredCount }} deferred</span> @endif</span>
      </div>
      <div>
        <span class="fq-fact__label">Generated on</span>
        <span class="fq-fact__value fq-fact__value--muted">{{ $quotation->created_at->format('M d, Y') }}</span>
      </div>
      <div>
        <span class="fq-fact__label">Valid until</span>
        <span class="fq-fact__value {{ $effStatus === 'expired' ? 'fq-fact__value--warn' : 'fq-fact__value--muted' }}">
          @if($quotation->valid_until)
            {{ $quotation->valid_until->format('M d, Y') }}
            @if($effStatus === 'expired')
              (expired)
            @elseif(is_numeric($daysLeft))
              ({{ $daysLeft }} day(s) left)
            @endif
          @else
            No expiration
          @endif
        </span>
      </div>
    </div>
    @if($quotation->notes)
      <div class="fq-notes">
        <div class="fq-notes__label">Your notes</div>
        <p class="fq-notes__text">{{ $quotation->notes }}</p>
      </div>
    @endif
    </div>
  </div>

  <div class="fq-card" style="margin-bottom: 0;">
    <div class="fq-card__head">
      <div>
        <h2 class="fq-card__title">Supply stores</h2>
        <p class="fq-card__sub">Where your inputs come from</p>
      </div>
    </div>
    @if($storeOwners->count() > 0)
      <div class="fq-stores">
        @foreach($storeOwners as $store)
          @php
            $sItems    = $quotation->items->where('product.user_id', $store->id);
            $sFitted   = $sItems->where('is_deferred', false);
            $sDeferred = $sItems->count() - $sFitted->count();
          @endphp
          <div class="fq-store">
            <x-user-avatar :user="$store" :size="38" />
            <div style="flex: 1; min-width: 0;">
              <div class="fq-store__name">{{ $store->name }}</div>
              <div class="fq-store__meta">
                {{ $sFitted->count() }} product(s) &middot; ₱{{ number_format((float) $sFitted->sum('subtotal'), 2) }}@if($sDeferred) &middot; {{ $sDeferred }} deferred @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <p class="fq-empty">Products from the general catalog were used.</p>
    @endif
  </div>
  </div>
</div>

<script>
  function escapeAiHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function inlineAi(t) {
    return escapeAiHtml(t)
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/\*([^*\n]+)\*/g, '<em>$1</em>')
      .replace(/\u2014/g, '&mdash;');
  }

  // One icon per kind of advice. The set is fixed and muted — the shape
  // tells you what you are reading, never a colour.
  var AI_ICONS = {
    takeaway: '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/>',
    meaning: '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
    advice: '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
    expired: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    check: '<polyline points="20 6 9 17 4 12"/>',
    plain: '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>'
  };

  function aiIcon(kind) {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
      + (AI_ICONS[kind] || AI_ICONS.plain) + '</svg>';
  }

  // Which kind of card a section is. The headings the advisor is asked
  // to use are known, so the shape of the card can match the advice.
  // "Next Move" has no card: the closing action belongs to the advice
  // itself, not in a card of its own.
  function aiSectionKind(title) {
    var t = (title || '').toLowerCase();
    if (t.indexOf('takeaway') !== -1) return 'takeaway';
    if (t.indexOf('expired') !== -1) return 'expired';
    if (t.indexOf('next') !== -1) return 'skip';
    if (t.indexOf('advice') !== -1) return 'advice';
    if (t.indexOf('means') !== -1 || t.indexOf('farm') !== -1) return 'meaning';
    return 'plain';
  }

  // Split the advisor's markdown into sections, then into the paragraphs
  // and the points inside them.
  function parseAiSections(markdown) {
    var text = String(markdown || '').replace(/\[[ \t\u00A0]*SUGGEST:[^\]]*\]/gi, '');
    var lines = text.replace(/\r/g, '').split('\n');
    var sections = [], current = null;

    for (var i = 0; i < lines.length; i++) {
      var line = lines[i];
      var heading = line.match(/^\s{0,3}#{1,6}\s+(.*?)\s*#*\s*$/);
      if (heading) {
        current = { title: heading[1].trim(), lines: [] };
        sections.push(current);
        continue;
      }
      if (!current) {
        if (!line.trim()) continue;
        current = { title: '', lines: [] };
        sections.push(current);
      }
      current.lines.push(line);
    }

    return sections.map(function (section) {
      var items = [], paras = [], buffer = [];

      function flush() {
        if (buffer.length) { paras.push(buffer.join(' ')); buffer = []; }
      }

      section.lines.forEach(function (raw) {
        var t = raw.trim();
        if (!t) { flush(); return; }
        var bullet = t.match(/^[-*\u2022]\s+(.*)$/);
        if (bullet) { flush(); items.push(bullet[1]); return; }
        var numbered = t.match(/^\d+[.)]\s+(.*)$/);
        if (numbered) { flush(); items.push(numbered[1]); return; }
        buffer.push(t);
      });
      flush();

      return { title: section.title, items: items, paras: paras };
    });
  }

  function renderAiSection(section) {
    var kind = aiSectionKind(section.title);
    if (kind === 'skip') return '';

    var html = '<div class="fq-aicard fq-aicard--' + kind + '">';

    html += '<div class="fq-aicard__head"><span class="fq-aicard__ic">' + aiIcon(kind) + '</span>';
    if (section.title) { html += '<h3>' + escapeAiHtml(section.title) + '</h3>'; }
    html += '</div>';

    var body = '';
    section.paras.forEach(function (p) { body += '<p>' + inlineAi(p) + '</p>'; });

    if (section.items.length) {
      if (kind === 'advice') {
        body += '<div class="fq-ai-steps">';
        section.items.forEach(function (item, index) {
          body += '<div class="fq-ai-step"><span class="fq-ai-step__n">' + (index + 1) + '</span>'
            + '<span class="fq-ai-text">' + inlineAi(item) + '</span></div>';
        });
        body += '</div>';
      } else {
        body += '<ul class="fq-ai-list">';
        section.items.forEach(function (item) {
          body += '<li><span class="fq-ai-list__ic">' + aiIcon(kind === 'expired' ? 'expired' : 'check') + '</span>'
            + '<span class="fq-ai-text">' + inlineAi(item) + '</span></li>';
        });
        body += '</ul>';
      }
    }

    html += '<div class="fq-card__body fq-ai-text">' + body + '</div>';
    html += '</div>';

    return html;
  }

  var aiLoading = false;

  // A window that sits on top of the page must not let the page scroll
  // underneath it — the wheel would move content the reader cannot see.
  function lockPageScroll(lock) {
    var body = document.body;
    if (lock) {
      body.dataset.scrollLocked = '1';
      body.style.overflow = 'hidden';
    } else if (body.dataset.scrollLocked) {
      delete body.dataset.scrollLocked;
      body.style.overflow = '';
    }
  }

  function openAiModal(html) {
    document.getElementById('aiModalBody').innerHTML = html;
    var modal = document.getElementById('aiModal');
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    lockPageScroll(true);
  }

  function closeAiModal() {
    var modal = document.getElementById('aiModal');
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    lockPageScroll(false);
  }

  function renderAiOverview(data) {
    if (data && typeof data === 'object' && data.error) {
      openAiModal(
        '<div class="ai-modal-error">'
        + '<div class="ai-modal-error-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>'
        + '<div class="ai-modal-error-title">We couldn\'t build your explanation</div>'
        + '<div class="ai-modal-error-text">' + escapeAiHtml(data.error) + '</div>'
        + '</div>'
      );
      return;
    }

    var source = (data && data.source === 'ai')
      ? '<span class="fq-btn fq-btn--ghost" style="font-size:11px;padding:4px 10px;"><svg viewBox="0 0 24 24" stroke-width="2.2"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15.5l.85 2.15L22 18.5l-2.15.85L19 21.5l-.85-2.15L16 18.5l2.15-.85L19 15.5z"/></svg>AI assisted</span>'
      : '<span class="fq-btn fq-btn--ghost" style="font-size:11px;padding:4px 10px;"><svg viewBox="0 0 24 24" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg>Smart summary (AI offline)</span>';

    var sections = parseAiSections(data.overview || '');
    var cards = '';
    sections.forEach(function (section) { cards += renderAiSection(section); });

    if (!cards) {
      cards = '<div class="fq-aicard fq-aicard--plain"><div class="fq-card__body fq-ai-text">'
        + '<p>' + inlineAi(data.overview || 'The assistant could not read this quotation just now. Please try again.') + '</p></div></div>';
    }

    openAiModal(
      '<div class="fq-ai__meta">' + source
      + '<span class="fq-ai__note">Built from your quotation data &mdash; it never invents numbers.</span></div>'
      + '<div class="fq-ai__cards">' + cards + '</div>'
    );
  }

  function setAiBusy(busy) {
    aiLoading = busy;
    var btn = document.getElementById('aiOverviewBtn');
    if (btn) {
      btn.disabled = busy;
      // The sparkle turns while the explanation is being written, so the
      // button reads as working even though it can't be pressed again.
      btn.classList.toggle('is-loading', busy);
      var label = btn.querySelector('span');
      if (label) label.textContent = busy ? 'Generating\u2026' : 'Generate explanation';
    }
  }

  function showAiLoading() {
    openAiModal(
      '<div class="fq-ai-loading">'
      + '<div class="fq-ai-loading__lines">'
      + '<div class="fq-shimmer" style="width: 42%;"></div>'
      + '<div class="fq-shimmer" style="width: 96%;"></div>'
      + '<div class="fq-shimmer" style="width: 88%;"></div>'
      + '<div class="fq-shimmer" style="width: 74%;"></div>'
      + '</div></div>'
    );
  }

  function generateAiOverview(btn) {
    if (aiLoading) return;
    setAiBusy(true);
    showAiLoading();

    fetch('{{ route('farmer.quotations.ai-overview', $quotation) }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: '{}'
    })
    .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
    .then(function (res) {
      setAiBusy(false);
      if (res.ok && res.data && res.data.overview) {
        renderAiOverview(res.data);
      } else if (res.data && res.data.error) {
        renderAiOverview({ error: res.data.error });
      } else {
        renderAiOverview({ error: 'The explanation could not be generated right now. Please try again.' });
      }
    })
    .catch(function () {
      setAiBusy(false);
      renderAiOverview({ error: 'Connection failed. Please try again.' });
    });
  }

  document.getElementById('aiModalClose').addEventListener('click', closeAiModal);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeAiModal();
  });

  // "Your plan at a glance" stays closed until the farmer asks for it:
  // the header is enough to know the card is there.
  (function () {
    var toggle = document.getElementById('planToggle');
    var facts = document.getElementById('planFacts');
    if (!toggle || !facts) return;

    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
      toggle.classList.toggle('is-open', !open);
      facts.hidden = open;
    });
  })();
</script>
@endsection
