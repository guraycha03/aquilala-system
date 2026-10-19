@props([
    'product' => null,
    'href' => null,
    'actions' => false,
    'imageHeight' => '190px',
])

@once
<style>
  /* ── Shared product card (catalog style) ─────────────────────────── */
  /* One source of truth for every catalog grid: the store storefront,
     the farmer Browse Supplies and Top Products pages, and the
     store-owner dashboard carousel. No page redefines these rules any
     more, so the card reads identically for every role.

     Layout, top to bottom:
       photo, whole and uncropped, on a warm neutral field
       brand  — small, light, uppercase
       product name
       warm divider
       "Price per <size>" caption
       price, in sage

     Every colour is a --pc-* variable on .product-card, so retheming
     the card is one override rather than ten rules. */
  .product-card {
    --pc-warm: #f6f3ec;
    --pc-line: #e8e3d8;
    --pc-hover: #d6cfbf;
    --pc-ink: #2b2a26;
    --pc-soft: #78736a;
    --pc-caption: #6f6a5d;
    --pc-sage: #344b38;
    --pc-chip-bg: #e7eee5;
    --pc-chip-ink: #4a6b4e;

    position: relative;
    background: #ffffff;
    border: 1px solid var(--pc-line);
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-width: 0;
    transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
  }
  .product-card:hover {
    border-color: var(--pc-hover);
    box-shadow: 0 10px 25px -5px rgb(74 63 40 / 0.10), 0 8px 10px -6px rgb(74 63 40 / 0.05);
    transform: translateY(-3px);
  }
  .product-card-link {
    position: absolute; inset: 0; z-index: 1;
    border-radius: inherit;
  }
  .product-image-container {
    box-sizing: border-box;
    background: var(--pc-warm);
    height: var(--img-h, 190px);
    width: 100%;
    /* Breathing room. The photo sits inside the field instead of bleeding
       edge to edge, so it reads as a picture placed on a card rather than
       as a texture behind the text. */
    padding: 16px;
    display: flex; align-items: center; justify-content: center;
    border-bottom: 1px solid var(--pc-line);
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
  }
  .product-card-img {
    width: 100%;
    height: 100%;
    /* contain, not cover. A catalog photo of a sack has to read as a
       whole sack; cover cropped the top and bottom off taller uploads.
       The zoom-on-hover that used to live here is gone for the same
       reason — scaling would eat back into the breathing space. */
    object-fit: contain;
    object-position: center;
  }
  /* No-photo placeholder: the Lucide "image" glyph in the grey the old
     inline SVG used. */
  .product-image-container > .lucide { width: 40px; height: 40px; stroke: #c9c2b2; }
  /* Category eyebrow over the photo. It is absolutely positioned, so it can
     grow to two lines and wrap over the image without ever pushing the photo
     down or making one card taller than its neighbours — no eliding, no
     "...". The right edge is free now that the season icon moved into the
     brand row, so it may use most of the card's width. */
  .category-chip {
    position: absolute; top: 10px; left: 10px; z-index: 2;
    font-size: 8.5px; line-height: 1.35;
    color: var(--pc-chip-ink); background: rgb(231 238 229 / 0.94);
    padding: 3px 6px; border-radius: 5px;
    text-transform: uppercase; font-weight: 700; letter-spacing: 0.03em;
    max-width: calc(100% - 26px);
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden; overflow-wrap: anywhere;
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.12);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
  }

  /* Season sits in the photo's bottom-right corner — diagonally opposite the
     category eyebrow, so the two read as a matched pair of labels rather than
     one label and one stray button. It shares the eyebrow's tinted-badge
     language, which also keeps it legible over a busy photo. Colour is the
     wet/dry/all signal; the tooltip spells it out. */
  .pc-season {
    position: absolute; bottom: 10px; right: 10px; z-index: 2;
    display: inline-flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 7px;
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.12);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
  }
  .pc-season svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  /* The placeholder <i data-lucide> is an empty tag until createIcons() swaps
     it for the official glyph, so the same rules target both it and the SVG. */
  .pc-season .lucide { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .pc-season.season-wet { background: rgba(224, 242, 254, 0.94); color: #0369a1; }
  .pc-season.season-dry { background: rgba(255, 237, 213, 0.94); color: #c2410c; }
  .pc-season.season-all { background: rgba(240, 253, 244, 0.94); color: #166534; }

  .product-details {
    box-sizing: border-box;
    padding: 12px 14px 13px;
    flex-grow: 1;
    display: flex; flex-direction: column;
    min-width: 0;
  }

  /* The brand eyebrow. Always rendered, with a min-height of one line of
     brand text, so a product with no brand still reserves the same space and
     its divider stays aligned with its neighbours in the grid. */
  .pc-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; margin-bottom: 6px; min-width: 0;
    min-height: 13px;
  }
  /* Kept deliberately small so the product name below it stays the loudest
     thing on the card. */
  .pc-brand {
    font-size: 9.5px; line-height: 1.3; font-weight: 600; color: var(--pc-soft);
    text-transform: uppercase; letter-spacing: 0.06em;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    min-width: 0;
  }

  /* Two lines maximum: a long name is the main reason a card in a
     two-column phone grid grew taller than the rest of the row. The
     min-height reserves those two lines even for short names, so the
     divider below sits at the same offset on every card in a row. */
  .product-title {
    font-size: 13px; color: var(--pc-ink); font-weight: 700; margin: 0;
    line-height: 1.35;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: break-word;
    min-height: 2.7em;
  }

  /* Extra body a page injects (the Top Products rank badge and store
     name). */
  .pc-body { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; min-width: 0; }

  /* The warm divider owns the footer. The footer flex-grows to take the
     leftover vertical space and its content (unit caption, price, then
     any actions) is centered between the line and the card bottom, so
     the price lands at the same height on every card in a row however
     much content sits above it. */
  .card-footer {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin-top: 10px;
    padding-top: 11px;
    border-top: 1px solid var(--pc-line);
    background: transparent;
    min-width: 0;
  }
  /* Caption sits above the price line. A page-injected footer slot (the Top
     Products "Generate Quotation" link) takes its own line beneath both, so
     the unit caption never has to elide. */
  /* The unit sits above the figure so the price can be read on its own,
     and so "Price per 50 kg" is still there when the figure is scanned
     alone. Elides rather than wraps, keeping the two lines even. */
  .price-caption {
    font-size: 11.5px; font-weight: 500; color: var(--pc-caption);
    line-height: 1.3; margin-bottom: 5px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  }
  /* Figure and the save bookmark share a line, pushed apart so the bookmark
     lands at the far right, opposite the price. Cards without a bookmark —
     the store owner looking at their own listing — leave the price alone on
     the left. */
  .price-line {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; min-width: 0;
  }
  .price-tag {
    font-size: 16.5px; font-weight: 700; color: var(--pc-sage);
    letter-spacing: -0.01em; white-space: nowrap; line-height: 1.2;
    min-width: 0; overflow: hidden; text-overflow: ellipsis;
  }
  .price-right { display: flex; align-items: center; gap: 4px; margin-top: 8px; min-width: 0; }

  /* Save for later: an icon-only bookmark that fills solid when saved. It
     rides the price line, opposite the figure. It sits above the stretched
     card link (z-index 2) so the tap lands on this button, not on the link
     behind it; .price-line's space-between puts it at the far right. */
  .pc-save {
    display: inline-flex; align-items: center; justify-content: center;
    background: none; border: none; cursor: pointer;
    color: #64748b;
    width: 26px; height: 26px;
    border-radius: 7px;
    flex-shrink: 0;
    transition: color .15s ease, background .15s ease;
    position: relative; z-index: 2;
  }
  .pc-save .lucide { width: 15px; height: 15px; flex-shrink: 0; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .pc-save:hover { background: #f1f5f9; color: #334155; }
  .pc-save.is-saved { color: #16a34a; }
  .pc-save.is-saved .lucide { fill: currentColor; }

  .action-group { display: flex; gap: 6px; margin-top: 8px; position: relative; z-index: 2; }
  .btn-action {
    display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    background: #f8fafc; color: #334155; border: 1px solid #cbd5e1;
    padding: 7px 10px; border-radius: 8px;
    font-size: 12.5px; font-weight: 600; cursor: pointer;
    text-decoration: none; transition: all .2s;
  }
  .btn-action svg { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .btn-action .lucide { width: 13px; height: 13px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .btn-action:hover { background: #f1f5f9; color: #0f172a; }
  .btn-delete { background: #fff5f5; color: #e11d48; border-color: #fecdd3; }
  .btn-delete:hover { background: #ffe4e6; color: #be123c; }

  .product-card.hidden, .product-card.card-hide { display: none; }

  /* ── Phone ───────────────────────────────────────────────────────── */
  @media (max-width: 640px) {
    .product-image-container { height: calc(var(--img-h, 190px) * .7); padding: 12px; }
    .product-details { padding: 10px 12px 11px; }
    .pc-head { margin-bottom: 5px; }
    .product-title { font-size: 12px; }
    .category-chip { font-size: 8px; padding: 2px 5px; top: 8px; left: 8px; max-width: calc(100% - 20px); }
    .pc-brand { font-size: 9px; }
    .pc-season { width: 21px; height: 21px; bottom: 8px; right: 8px; }
    .pc-season svg, .pc-season .lucide { width: 12px; height: 12px; }
    .pc-body { margin-top: 8px; }
    .card-footer { margin-top: 9px; padding-top: 9px; }
    .price-right { margin-top: 6px; }
    .price-caption { font-size: 10.5px; margin-bottom: 4px; }
    .price-tag { font-size: 14px; }
    .pc-save { width: 23px; height: 23px; }
    .pc-save .lucide { width: 13px; height: 13px; }
    .btn-action { padding: 6px 8px; font-size: 12px; }
    .action-group { gap: 5px; }
  }

  /* Very narrow phones: the whole card shrinks another step. */
  @media (max-width: 400px) {
    .product-image-container { height: calc(var(--img-h, 190px) * .58); padding: 10px; }
    .product-title { font-size: 11.5px; }
    .price-tag { font-size: 13.5px; }
    .pc-save .lucide { width: 12px; height: 12px; }
    .btn-action svg { width: 12px; height: 12px; }
  }

  /* ── Dark ────────────────────────────────────────────────────────── */
  /* Retinting only the variables does the card, the chrome (bookmark,
     action buttons, placeholder) needs its own lines. */
  [data-theme="dark"] .product-card {
    --pc-warm: #1b2430;
    --pc-line: #334155;
    --pc-hover: #475569;
    --pc-ink: #e6ecea;
    --pc-soft: #9fb3a6;
    --pc-caption: #9fb3a6;
    --pc-sage: #6ee7a0;
    --pc-chip-bg: #1f2d24;
    --pc-chip-ink: #6ee7a0;
    background: #111827;
  }
  [data-theme="dark"] .product-image-container > .lucide { stroke: #475569; }
  [data-theme="dark"] .category-chip { background: rgb(31 45 36 / 0.94); }
  /* The season badge keeps its tinted pill in dark, but the light-mode tints
     and ink would glare against a dark photo, so both are swapped. */
  [data-theme="dark"] .pc-season.season-wet { background: rgb(12 74 110 / 0.94); color: #7dd3fc; }
  [data-theme="dark"] .pc-season.season-dry { background: rgb(124 45 18 / 0.94); color: #fdba74; }
  [data-theme="dark"] .pc-season.season-all { background: rgb(20 83 45 / 0.94); color: #86efac; }
  [data-theme="dark"] .product-card:hover { box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.45); }
  [data-theme="dark"] .pc-save { color: #94a3b8; }
  [data-theme="dark"] .pc-save:hover { background: #1f2937; color: #e2e8f0; }
  [data-theme="dark"] .pc-save.is-saved { color: #4ade80; }
  [data-theme="dark"] .btn-action { background: #1f2937; color: #cbd5e1; border-color: #475569; }
  [data-theme="dark"] .btn-action:hover { background: #334155; color: #f1f5f9; }
  [data-theme="dark"] .btn-delete { background: #3b1220; color: #fb7185; border-color: #7f1d34; }
  [data-theme="dark"] .btn-delete:hover { background: #58152b; color: #fda4af; }
</style>
@endonce

@php
  $season = $product->recommended_season ?? '';
  $seasonClass = $season === 'wet' ? 'season-wet' : ($season === 'dry' ? 'season-dry' : 'season-all');
  $seasonLabel = $season === 'wet' ? 'Wet season' : ($season === 'dry' ? 'Dry season' : 'All seasons');

  // The card used to print display_name ("Urea — 50 kg"). The measure now
  // sits under the divider as the price caption, so the title prints the
  // bare name.
  $displayName = $product->display_name;

  // "Price per 50 kg" / "Price per 500 mL". Products with no package size
  // on record just say "Price" rather than leaving a blank line.
  $size = $product->size_label;
  $priceCaption = ($size === null || $size === '') ? 'Price' : 'Price per ' . $size;

  $title = trim((string) $product->name);
  $title = $title === '' ? $displayName : $title;

  // Merchants type the measure into the name as well as the package fields —
  // "Magnesium Sulfate Fertilizer (25 kg)", "Urea — 50 kg". The caption under
  // the divider now says it, so a trailing copy is dropped rather than printed
  // twice. Only a size sitting at the very end behind a dash or brackets is
  // touched, so a name like "Woven Rice Bags (50 kg) — Bundle of 50" keeps its
  // own wording, and a name that is nothing but the size is left intact.
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

  // Brand is optional. An unbranded product simply starts at the title.
  $brand = trim((string) ($product->brand ?? ''));

  // Save-for-later state for the signed-in user. The controllers that render
  // the card eager-tag each product with a transient `saved` flag in a single
  // query, so checking it here costs nothing per card.
  $isSaved = $href !== null && ! empty($product->saved);
  $showSave = $href !== null && auth()->check();
@endphp

{{-- The shared catalog filter bar filters and sorts purely off the data-cf-*
     attributes Product::catalogFacets() supplies, so this card gets the full
     filter set for free. The pages that use bespoke card markup call the same
     method, so a facet is never defined twice. --}}
<div {{ $attributes->merge(array_merge(['class' => 'product-card'], $product->catalogFacets())) }}>
  @if($href)
    <a href="{{ $href }}" class="product-card-link" aria-label="View {{ $displayName }}"></a>
  @endif

  <div class="product-image-container" style="--img-h: {{ $imageHeight }};">
    {{-- Both badges are absolutely positioned over the photo, so they may
         wrap or grow without shifting the image or resizing the card. The
         category eyebrow takes the top-left, the season badge the
         bottom-right: a diagonal pair, so neither can read as a button. --}}
    <span class="category-chip">{{ $product->category_display_name }}</span>

    <span class="pc-season {{ $seasonClass }}" title="{{ $seasonLabel }}" aria-label="{{ $seasonLabel }}">
      @if($season === 'wet')
        {{-- A raindrop rather than the old umbrella: it is the season's own
             symbol, and it stays legible at 14px where an umbrella filled in. --}}
        <i data-lucide="droplet"></i>
      @elseif($season === 'dry')
        <i data-lucide="sun"></i>
      @else
        <i data-lucide="refresh-cw"></i>
      @endif
    </span>

    @if($product->mainImage && !empty($product->mainImage->file_path))
      <img src="{{ $product->mainImage->thumb_url }}" alt="{{ $displayName }}" class="product-card-img" loading="lazy">
    @elseif($product->images && $product->images->first())
      <img src="{{ $product->images->first()->thumb_url }}" alt="{{ $displayName }}" class="product-card-img" loading="lazy">
    @else
      <i data-lucide="image" width="40" height="40" aria-hidden="true"></i>
    @endif
  </div>

  <div class="product-details">
    {{-- Brand eyebrow. The row always renders — with a min-height matching one
         line of brand text — so a product without a brand still reserves the
         same space and its divider stays aligned with its neighbours. --}}
    <div class="pc-head">
      @if($brand !== '')
        <span class="pc-brand" title="{{ $brand }}">{{ $brand }}</span>
      @endif
    </div>

    <h3 class="product-title" title="{{ $title }}">{{ $title }}</h3>

    @if(isset($body))
      <div class="pc-body">{{ $body }}</div>
    @endif

    <div class="card-footer">
      <div class="price-caption">{{ $priceCaption }}</div>

      {{-- Figure left, save bookmark right on the same line. A page-injected
           footer slot stays on its own line beneath so the caption never has
           to elide to make room. --}}
      <div class="price-line">
        <div class="price-tag">₱{{ number_format($product->price, 2) }}</div>

        @if($showSave)
          <button type="button"
                  class="pc-save{{ $isSaved ? ' is-saved' : '' }}"
                  data-save-url="{{ route('products.save', $product->id) }}"
                  aria-pressed="{{ $isSaved ? 'true' : 'false' }}"
                  title="{{ $isSaved ? 'Remove from saved' : 'Save for later' }}">
            <i data-lucide="bookmark" aria-hidden="true"></i>
          </button>
        @endif
      </div>

      @if(isset($footer))
        <div class="price-right">{{ $footer }}</div>
      @endif
      @if($actions)
        <div class="action-group">
          <a href="{{ route('store.products.edit', $product->id) }}" class="btn-action" style="flex:1;">
            <i data-lucide="pencil" aria-hidden="true"></i>
            Edit
          </a>
          <form method="POST" action="{{ route('store.products.destroy', $product->id) }}" data-aqui-confirm="Archive “{{ $product->name }}”? It will be hidden from the catalog and marketplace but you can restore it anytime from the Archive." data-aqui-danger data-aqui-confirm-label="Archive" style="margin:0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-action btn-delete" title="Archive Product (restorable)">
              <i data-lucide="trash-2" aria-hidden="true"></i>
            </button>
          </form>
        </div>
      @endif
    </div>
  </div>
</div>

@once
<script>
  (function () {
    'use strict';

    function csrfToken() {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.content : '';
    }

    // Save/unsave is delegated so it works for every card on a page, with no
    // per-card listeners. The gear the user taps is the <i data-lucide> that
    // Lucide already replaced with the official bookmark SVG.
    document.addEventListener('click', function (e) {
      var btn = e.target.closest ? e.target.closest('.pc-save') : null;
      // btn.disabled doubles as a re-entry guard: after a Turbo visit the
      // previous page's copy of this handler is still bound, so a duplicate
      // fire must not trigger a second save request.
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
          btn.title = saved ? 'Remove from saved' : 'Save for later';
        })
        .catch(function () {
          // Offline or expired session: leave the bookmark as it was; the user
          // can simply tap again.
        })
        .finally(function () {
          btn.disabled = false;
        });
    });
  })();
</script>
@endonce
