@props([
    'product' => null,
])

@once
<style>
  /* ── Recommended-supply card ─────────────────────────────────────
     The card is a normal product tile. It no longer carries the rule
     explanation: clicking its body opens the temporary "why" drawer,
     and the Product info button opens the temporary preview drawer. */
  .rec-card {
    --rc-line: #e4e9e0;
    --rc-ink: #1e2922;
    --rc-soft: #647263;
    --rc-green: #143d23;
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: #ffffff;
    border: 1px solid var(--rc-line);
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
  }
  .rec-card:hover {
    border-color: #c8d5c6;
    box-shadow: 0 12px 28px -8px rgb(20 61 35 / 0.16);
    transform: translateY(-2px);
  }
  .rec-card.is-selected {
    border-color: #a9c4ab;
    box-shadow: 0 0 0 2px rgb(169 196 171 / 0.35);
  }
  .rec-media {
    background: #f6f4ee;
    border-bottom: 1px solid var(--rc-line);
    padding: 14px;
    height: 170px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
    pointer-events: none;
  }
  .rec-media img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
  }
  .rec-media > .lucide { width: 38px; height: 38px; stroke: #c9c2b2; }
  .rec-top-actions {
    position: absolute; top: 9px; left: 9px; right: 9px; z-index: 2;
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    pointer-events: none;
  }
  .rec-top-actions button { pointer-events: auto; }
  .rec-why-pick {
    display: inline-flex; align-items: center; gap: 4px;
    max-width: calc(100% - 38px);
    padding: 4px 8px; border-radius: 999px;
    border: 1px solid #cbd9c8; background: rgb(238 244 237 / 0.96);
    color: #274a30; font-size: 9px; font-weight: 800;
    letter-spacing: .02em; text-transform: uppercase;
    font-family: inherit; cursor: pointer;
  }
  .rec-why-pick:hover { background: #e4eee0; }
  .rec-why-pick svg { width: 11px; height: 11px; flex-shrink: 0; }

  /* The category and the season icon share one line at the bottom of the
     image, leaving the top-left free for the recommendation trigger. */
  .rec-image-foot {
    position: absolute; left: 9px; right: 9px; bottom: 9px; z-index: 2;
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    pointer-events: none;
  }
  .rec-chip {
    min-width: 0;
    max-width: calc(100% - 32px);
    padding: 3px 7px;
    border-radius: 6px;
    background: rgb(231 238 229 / 0.96);
    color: #4a6b4e;
    font-size: 8.5px;
    font-weight: 700;
    letter-spacing: .04em;
    line-height: 1.35;
    text-transform: uppercase;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: anywhere;
  }
  .rec-season {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  .rec-season svg, .rec-season .lucide {
    width: 14px;
    height: 14px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }
  .rec-season.season-wet { background: rgb(224 242 254 / 0.95); color: #0369a1; }
  .rec-season.season-dry { background: rgb(255 237 213 / 0.95); color: #c2410c; }
  .rec-season.season-all { background: rgb(240 253 244 / 0.95); color: #166534; }

  .rec-body {
    padding: 15px 15px 13px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    min-width: 0;
    pointer-events: none;
  }
  .rec-body button, .rec-save { pointer-events: auto; }
  .rec-title {
    margin: 0;
    font-size: 13.5px;
    font-weight: 700;
    line-height: 1.35;
    color: var(--rc-ink);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: anywhere;
    min-height: 2.7em;
  }
  .rec-meta {
    margin-top: 8px;
    display: flex;
    flex-wrap: wrap;
    gap: 4px 12px;
    color: var(--rc-soft);
    font-size: 11px;
    pointer-events: none;
  }
  .rec-meta span { display: inline-flex; align-items: center; gap: 4px; min-width: 0; }
  .rec-meta svg, .rec-meta .lucide {
    width: 12px; height: 12px; flex-shrink: 0;
    fill: none; stroke: currentColor; stroke-width: 2;
    stroke-linecap: round; stroke-linejoin: round;
  }
  .rec-stock.is-low { color: #8a7c3e; }
  .rec-stock.is-out { color: #a34a4a; }

  .rec-footer {
    margin-top: auto;
    padding-top: 13px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 10px;
    pointer-events: none;
  }
  .rec-price-caption {
    display: block;
    color: var(--rc-soft);
    font-size: 10.5px;
    margin-bottom: 2px;
  }
  .rec-price {
    display: block;
    color: var(--rc-green);
    font-size: 16px;
    font-weight: 800;
    letter-spacing: -.01em;
    white-space: nowrap;
  }
  .rec-save {
    position: absolute; top: 9px; right: 9px; z-index: 3;
    display: inline-flex; align-items: center; justify-content: center;
    width: 27px; height: 27px;
    border: 1px solid #d5ddd1;
    border-radius: 8px;
    background: rgb(255 255 255 / 0.96);
    color: #64748b;
    cursor: pointer;
  }
  .rec-save .lucide {
    width: 15px; height: 15px;
    fill: none; stroke: currentColor; stroke-width: 2;
    stroke-linecap: round; stroke-linejoin: round;
  }
  .rec-save:hover { background: #f1f5f9; color: #334155; }
  .rec-save.is-saved { color: #4a7a54; border-color: #a9c4ab; background: #f2f8f1; }
  .rec-save.is-saved .lucide { fill: currentColor; }

  .rec-info-btn {
    margin-top: 11px;
    padding: 7px 9px;
    width: 100%;
    border-radius: 8px;
    border: 1px solid #d5ddd1;
    background: #f8faf7;
    color: #2c4b34;
    font-size: 11px;
    font-weight: 700;
    font-family: inherit;
    text-decoration: none;
    cursor: pointer;
    pointer-events: auto;
  }
  .rec-info-btn:hover { background: #e9eee5; }

  [data-theme="dark"] .rec-card {
    --rc-line: #334155; --rc-ink: #e6ecea; --rc-soft: #9fb3a6; --rc-green: #86efac;
    background: #111827;
  }
  [data-theme="dark"] .rec-media { background: #1b2430; }
  [data-theme="dark"] .rec-chip { background: rgb(31 45 36 / 0.95); color: #86efac; }
  [data-theme="dark"] .rec-season.season-wet { background: rgb(12 74 110 / 0.95); color: #7dd3fc; }
  [data-theme="dark"] .rec-season.season-dry { background: rgb(124 45 18 / 0.95); color: #fdba74; }
  [data-theme="dark"] .rec-season.season-all { background: rgb(20 83 45 / 0.95); color: #86efac; }
  [data-theme="dark"] .rec-save { background: #1f2937; border-color: #475569; color: #94a3b8; }
  [data-theme="dark"] .rec-save.is-saved { color: #9cc4a3; border-color: #4a7a54; background: #1b2a20; }
  [data-theme="dark"] .rec-why-pick { background: rgb(27 42 32 / 0.96); border-color: #4a7a54; color: #bfe6c6; }
  [data-theme="dark"] .rec-info-btn { background: #1f2937; border-color: #475569; color: #d6f5e3; }
  [data-theme="dark"] .rec-info-btn:hover { background: #273449; }

  @media (max-width: 640px) {
    .rec-media { height: 135px; padding: 11px; }
    .rec-body { padding: 13px; }
    .rec-title { font-size: 12.5px; }
    .rec-footer { align-items: center; padding-top: 11px; }
    .rec-price { font-size: 14.5px; }
    .rec-why-pick { font-size: 8.5px; padding: 3px 6px; }
    .rec-info-btn { font-size: 10.5px; }
  }
</style>
@endonce

@php
  $season = $product->recommended_season ?? '';
  $seasonClass = $season === 'wet' ? 'season-wet' : ($season === 'dry' ? 'season-dry' : 'season-all');
  $seasonIcon = $season === 'wet' ? 'droplet' : ($season === 'dry' ? 'sun' : 'refresh-cw');
  $seasonLabel = $season === 'wet' ? 'Wet season' : ($season === 'dry' ? 'Dry season' : 'All seasons');

  $displayName = $product->display_name;
  $size = $product->size_label;
  $priceCaption = ($size === null || $size === '') ? 'Price' : 'Price per ' . $size;
  $title = trim((string) $product->name);
  if ($title === '') {
      $title = $displayName;
  }
  if ($size !== null && $size !== '') {
      $trimmed = preg_replace(
          '/(?:\s*[\(\[]\s*|\s*[\x{2013}\x{2014}\-]\s*)' . preg_quote($size, '/') . '\s*[\)\]]?\s*$/u',
          '',
          $title
      );
      if (is_string($trimmed) && trim($trimmed) !== '') {
          $title = trim($trimmed);
      }
  }

  $quantity = (int) ($product->stock_quantity ?? 0);
  $stockClass = $quantity <= 0 ? 'is-out' : ($quantity <= (int) ($product->reorder_threshold ?? 5) ? 'is-low' : '');
  $stockLabel = $quantity <= 0 ? 'Out of stock' : $quantity.' '.$product->stock_unit.' in stock';

  $isSaved = ! empty($product->saved);
  $showSave = auth()->check();
@endphp

<div {{ $attributes->merge(array_merge([
        'class' => 'rec-card',
        'data-rsr-card' => '',
        'title' => 'Tap to see why this was recommended',
    ], $product->catalogFacets())) }}
    data-rsr-product-id="{{ $product->id }}">
  <div class="rec-media">
    <div class="rec-top-actions">
      <button type="button" class="rec-why-pick" data-rsr-action="reason">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/></svg>
        Why this pick
      </button>
      @if($showSave)
        <button type="button"
                class="rec-save{{ $isSaved ? ' is-saved' : '' }}"
                data-save-url="{{ route('products.save', $product->id) }}"
                aria-pressed="{{ $isSaved ? 'true' : 'false' }}"
                aria-label="{{ $isSaved ? 'Remove from saved' : 'Save for later' }}"
                title="{{ $isSaved ? 'Remove from saved' : 'Save for later' }}">
          <i data-lucide="bookmark" aria-hidden="true"></i>
        </button>
      @endif
    </div>

    <div class="rec-image-foot">
      <span class="rec-chip">{{ $product->category_display_name }}</span>
      <span class="rec-season {{ $seasonClass }}" title="{{ $seasonLabel }}" aria-label="{{ $seasonLabel }}">
        <i data-lucide="{{ $seasonIcon }}"></i>
      </span>
    </div>

    @if($product->mainImage && ! empty($product->mainImage->file_path))
      <img src="{{ $product->mainImage->card_url }}" alt="{{ $displayName }}" loading="lazy">
    @elseif($product->images && $product->images->first())
      <img src="{{ $product->images->first()->card_url }}" alt="{{ $displayName }}" loading="lazy">
    @else
      <i data-lucide="image" width="38" height="38" aria-hidden="true"></i>
    @endif
  </div>

  <div class="rec-body">
    <h3 class="rec-title" title="{{ $title }}">{{ $title }}</h3>

    <div class="rec-meta">
      @if($product->storeOwner)
        <span>
          <i data-lucide="store" aria-hidden="true"></i>
          {{ $product->storeOwner->store_name ?? $product->storeOwner->name }}
        </span>
      @endif
      <span class="rec-stock {{ $stockClass }}">
        <i data-lucide="package" aria-hidden="true"></i>
        {{ $stockLabel }}
      </span>
    </div>

    <div class="rec-footer">
      <div>
        <span class="rec-price-caption">{{ $priceCaption }}</span>
        <span class="rec-price">₱{{ number_format($product->price, 2) }}</span>
      </div>
    </div>

    <button type="button" class="rec-info-btn" data-rsr-action="info">
      Product info
    </button>
  </div>
</div>

@once
<script>
  (function () {
    'use strict';

    if (window.__aqRecSaveDelegate) return;
    window.__aqRecSaveDelegate = true;

    function csrfToken() {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.content : '';
    }

    document.addEventListener('click', function (e) {
      var btn = e.target.closest ? e.target.closest('.rec-save') : null;
      if (!btn || e.defaultPrevented || e.button !== 0 || btn.disabled) return;

      var url = btn.getAttribute('data-save-url');
      if (!url) return;

      e.preventDefault();
      btn.disabled = true;

      fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken(),
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      })
        .then(function (res) {
          if (!res.ok) throw new Error('save request failed');
          return res.json();
        })
        .then(function (data) {
          var saved = !!data.saved;
          btn.classList.toggle('is-saved', saved);
          btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
          var label = saved ? 'Remove from saved' : 'Save for later';
          btn.title = label;
          btn.setAttribute('aria-label', label);
        })
        .catch(function () {
          // Leave the bookmark as it was; the farmer can tap again.
        })
        .finally(function () {
          btn.disabled = false;
        });
    });
  })();
</script>
@endonce
