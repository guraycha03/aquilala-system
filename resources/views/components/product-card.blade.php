@props([
    'product' => null,
    'href' => null,
    'actions' => false,
    'imageHeight' => '190px',
])

@once
<style>
  /* ── Shared product card (catalog style) ─────────────────────────── */
  /* Used by the store storefront, farmer browse, farmer reviews and the
     store-owner dashboard, so every role gets the same card.
     Deliberately minimal: name, price, rating, season. Stock level, quantity
     and brand all live on the product detail page — a shopper scanning a
     catalog does not decide on those, and each extra line made the card
     taller than its neighbours in the two-column phone grid. */
  .product-card {
    position: relative;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-width: 0;
    transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
  }
  .product-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
    transform: translateY(-3px);
  }
  .product-card-link {
    position: absolute; inset: 0; z-index: 1;
    border-radius: inherit;
  }
  .product-image-container {
    background: #f8fafc;
    height: var(--img-h, 190px);
    width: 100%;
    display: flex; align-items: center; justify-content: center;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
    overflow: hidden;
    flex-shrink: 0;
  }
  .product-card-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .3s ease;
  }
  /* No-photo placeholder: the Lucide "image" glyph in the light grey the old
     inline SVG used. */
  .product-image-container > .lucide { width: 40px; height: 40px; stroke: #cbd5e1; }
  .product-card:hover .product-card-img { transform: scale(1.03); }
  .product-details {
    padding: 11px 13px 12px;
    flex-grow: 1;
    display: flex; flex-direction: column;
    min-width: 0;
  }
  .category-chip {
    position: absolute; top: 10px; left: 10px; z-index: 2;
    font-size: 10px; color: #0f766e; background: rgba(204, 251, 241, 0.92);
    padding: 3px 8px; border-radius: 6px;
    text-transform: uppercase; font-weight: 700; letter-spacing: 0.03em;
    max-width: calc(100% - 62px); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
  }

  /* Season is the only badge left, and it is just the icon in the corner. */
  .pc-season {
    position: absolute; top: 10px; right: 10px; z-index: 2;
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 7px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
  }
  .pc-season svg { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  /* The placeholder <i data-lucide> is an empty tag until createIcons() swaps
     it for the official glyph, so the same rules target both it and the SVG. */
  .pc-season .lucide { width: 15px; height: 15px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  .pc-season.season-wet { background: rgba(224, 242, 254, 0.94); color: #0369a1; }
  .pc-season.season-dry { background: rgba(255, 237, 213, 0.94); color: #c2410c; }
  .pc-season.season-all { background: rgba(240, 253, 244, 0.94); color: #166534; }

  /* Two lines maximum: a long product name is the main reason a card in a
     two-column phone grid grew taller than the rest of the row. The min-height
     reserves those two lines even for short names, so the rating row below
     sits at the same offset on every card in a row. */
  .product-title {
    font-size: 14.5px; color: #0f172a; font-weight: 700; margin: 0 0 6px;
    line-height: 1.35;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
    overflow-wrap: break-word;
    min-height: 2.7em;
  }

  /* A catalog only needs a quick sense of quality, so the rating is the
     official Lucide star plus the number. The colour comes from `color`
     inherited into the glyph (filled via currentColor), so the same icon
     serves rated (amber) and unrated (grey) cards. */
  .pc-rating { display: flex; align-items: center; gap: 4px; min-width: 0; color: #f59e0b; }

  /* The top row holds the rating on the left and the save bookmark on the
     right. Its bottom margin is what the old .pc-rating margin supplied, so
     the vertical rhythm below it is unchanged. */
  .pc-top-row {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px; margin-bottom: 6px; min-width: 0;
  }
  .pc-rating .lucide { width: 14px; height: 14px; flex-shrink: 0; fill: currentColor; stroke: none; }
  .pc-rating-text { font-size: 11.5px; color: #64748b; white-space: nowrap; }
  .pc-rating-text b { color: #334155; font-weight: 700; }
  .pc-rating.is-unrated { color: #cbd5e1; }
  .pc-rating.is-unrated .pc-rating-text b { color: #94a3b8; }

  .pc-body { display: flex; flex-direction: column; gap: 6px; margin-bottom: 6px; min-width: 0; }

  /* The separating line owns the whole footer: the footer flex-grows to take
     the leftover vertical space and its content (price row, actions) is
     centered between the line and the card bottom. The price therefore sits
     at the same height on every card, no matter how much content sits above. */
  .card-footer {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 11px 0 12px;
    border-top: 1px dashed #e2e8f0;
    background: transparent;
    min-width: 0;
  }
  .price-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; }
  .price-tag {
    font-size: 16px; font-weight: 700; color: #0f172a;
    letter-spacing: -0.01em; white-space: nowrap;
    /* Anchor the price to the right edge; where a footer slot follows (e.g.
       the reviews card) it sits inline right after the price. */
    margin-left: auto;
  }
  .price-right { display: flex; align-items: center; gap: 4px; min-width: 0; }

  /* Save for later: an icon-only bookmark on the rating's right that fills
     solid when saved. It sits above the stretched card link (z-index 2) so
     the tap lands on this button, not on the link behind it. */
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

  .action-group { display: flex; gap: 6px; position: relative; z-index: 2; }
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
    .product-image-container { height: calc(var(--img-h, 190px) * .7); }
    .product-details { padding: 9px 11px 10px; }
    .product-title { font-size: 13.5px; margin-bottom: 5px; }
    .category-chip { font-size: 9px; padding: 2px 7px; top: 8px; left: 8px; max-width: calc(100% - 54px); }
    .pc-season { top: 8px; right: 8px; width: 23px; height: 23px; }
    .pc-season svg { width: 13px; height: 13px; }
    .pc-top-row { gap: 6px; margin-bottom: 5px; }
    .pc-rating { gap: 3px; }
    .pc-rating .lucide { width: 12px; height: 12px; }
    .pc-rating-text { font-size: 11px; }
    .pc-body { gap: 5px; margin-bottom: 5px; }
    .card-footer { padding: 8px 0 9px; }
    .price-row { gap: 6px; margin-bottom: 6px; }
    .price-tag { font-size: 15px; }
    .pc-save { width: 23px; height: 23px; }
    .pc-save .lucide { width: 13px; height: 13px; }
    .btn-action { padding: 6px 8px; font-size: 12px; }
    .action-group { gap: 5px; }
  }

  /* Very narrow phones: the whole card shrinks another step. */
  @media (max-width: 400px) {
    .product-image-container { height: calc(var(--img-h, 190px) * .58); }
    .product-title { font-size: 12.5px; }
    .price-tag { font-size: 14px; }
    .pc-rating .lucide { width: 11px; height: 11px; }
    .pc-save .lucide { width: 12px; height: 12px; }
    .btn-action svg { width: 12px; height: 12px; }
  }
</style>
@endonce

@php
  $season = $product->recommended_season ?? '';
  $seasonClass = $season === 'wet' ? 'season-wet' : ($season === 'dry' ? 'season-dry' : 'season-all');
  $seasonLabel = $season === 'wet' ? 'Wet season' : ($season === 'dry' ? 'Dry season' : 'All seasons');
  $displayName = $product->display_name;

  // Accessors over the approvedReviews relation, so any surface that renders
  // the card can show a rating without knowing where the number came from.
  // A product with no approved reviews is treated as 0 rather than left blank,
  // so the card always shows a star and a number.
  $rating = $product->average_rating;
  $hasRating = $rating !== null;
  $ratingValue = $hasRating ? (float) $rating : 0.0;

  // Save-for-later state for the signed-in user. The controllers that render
  // the card eager-tag each product with a transient `saved` flag in a single
  // query, so checking it here costs nothing per card.
  $isSaved = $href !== null && ! empty($product->saved);
@endphp

{{-- The shared catalog filter bar filters and sorts purely off the data-cf-*
     attributes Product::catalogFacets() supplies, so this card gets the full
     filter set for free. The pages that use bespoke card markup call the same
     method, so a facet is never defined twice.

     Stock level, remaining quantity and brand are intentionally absent from
     the visible card: they are stock-control data and belong on the product
     detail page. The facets are for filtering only. --}}
<div {{ $attributes->merge(array_merge(['class' => 'product-card'], $product->catalogFacets())) }}>
  @if($href)
    <a href="{{ $href }}" class="product-card-link" aria-label="View {{ $displayName }}"></a>
  @endif

  <div class="product-image-container" style="--img-h: {{ $imageHeight }};">
    <span class="category-chip">{{ $product->category_display_name }}</span>

    <span class="pc-season {{ $seasonClass }}" title="{{ $seasonLabel }}" aria-label="{{ $seasonLabel }}">
      @if($season === 'wet')
        <i data-lucide="umbrella"></i>
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
    <h3 class="product-title" title="{{ $displayName }}">{{ $displayName }}</h3>

    <div class="pc-top-row">
      <div class="pc-rating{{ $hasRating ? '' : ' is-unrated' }}"
           role="img"
           aria-label="{{ $hasRating ? 'Rated ' . number_format($ratingValue, 1) . ' out of 5' : 'Not yet rated' }}"
           title="{{ $hasRating ? 'Rated ' . number_format($ratingValue, 1) . ' out of 5' : 'Not yet rated' }}">
        <i data-lucide="star" aria-hidden="true"></i>
        <span class="pc-rating-text"><b>{{ number_format($ratingValue, 1) }}</b></span>
      </div>
      @auth
        @if($href)
          <button type="button"
                  class="pc-save{{ $isSaved ? ' is-saved' : '' }}"
                  data-save-url="{{ route('products.save', $product->id) }}"
                  aria-pressed="{{ $isSaved ? 'true' : 'false' }}"
                  title="{{ $isSaved ? 'Remove from saved' : 'Save for later' }}">
            <i data-lucide="bookmark" aria-hidden="true"></i>
          </button>
        @endif
      @endauth
    </div>

    @if(isset($body))
      <div class="pc-body">{{ $body }}</div>
    @endif

    <div class="card-footer">
      <div class="price-row">
        <div class="price-tag">₱{{ number_format($product->price, 2) }}</div>
        <div class="price-right">
          @if(isset($footer)){{ $footer }}@endif
        </div>
      </div>
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
      if (!btn || e.defaultPrevented || e.button !== 0) return;

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
