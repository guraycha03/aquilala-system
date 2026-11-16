@extends('layouts.sidebar')

@section('content')

@php
    $fallbackImage = 'images/crop.png';
    $fallbackFull  = public_path($fallbackImage);
    $fallbackUrl   = asset($fallbackImage) . (file_exists($fallbackFull) ? '?v=' . filemtime($fallbackFull) : '');

    // Banner background with automatic cache-busting timestamp (?v=...)
    $bannerRelPath = 'images/crop-page.jpg';
    $bannerFull    = public_path($bannerRelPath);
    $bannerBgImage = file_exists($bannerFull)
        ? asset($bannerRelPath) . '?v=' . filemtime($bannerFull)
        : '';

    $categoryLabels = [
        'grain'     => 'Grain',
        'vegetable' => 'Vegetable',
        'fruit'     => 'Fruit',
        'root'      => 'Root Crop',
        'herb'      => 'Herb & Spice',
        'fiber'     => 'Fiber & Industrial',
        'legume'    => 'Legume',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // CROP IMAGE PLACEHOLDERS (in public/images/)
    // Uncomment a line once you drop the matching image into public/images/.
    // Note: Even while commented out, if you save a file matching the crop's
    // lowercase slug (e.g. tomato.png, sweet-potato.png) in public/images/,
    // the resolver below will auto-detect it and append ?v=filemtime!
    // ─────────────────────────────────────────────────────────────────────────
    $cropImages = [
        // Grains
        // 'Rice'         => 'rice.png',
        // 'Corn'         => 'corn.png',
        // 'Wheat'        => 'wheat.png',

        // Fruits
        // 'Coconut'      => 'coconut.png',
        // 'Pili'         => 'pili.png',
        // 'Banana'       => 'banana.png',
        // 'Mango'        => 'mango.png',
        // 'Papaya'       => 'papaya.png',
        // 'Pineapple'    => 'pineapple.png',
        // 'Jackfruit'    => 'jackfruit.png',
        // 'Calamansi'    => 'calamansi.png',
        // 'Watermelon'   => 'watermelon.png',
        // 'Orange'       => 'orange.png',
        // 'Lemon'        => 'lemon.png',

        // Vegetables
        // 'Tomato'       => 'tomato.png',
        // 'Eggplant'     => 'eggplant.png',
        // 'Okra'         => 'okra.png',
        // 'String Bean'  => 'string-bean.png',
        // 'Squash'       => 'squash.png',
        // 'Ampalaya'     => 'ampalaya.png',
        // 'Pepper'       => 'pepper.png',
        // 'Cabbage'      => 'cabbage.png',
        // 'Sayote'       => 'sayote.png',
        // 'Luffa'        => 'luffa.png',
        // 'Cucumber'     => 'cucumber.png',
        // 'Lettuce'      => 'lettuce.png',
        // 'Malunggay'    => 'malunggay.png',
        // 'Kangkong'     => 'kangkong.png',

        // Root Crops
        // 'Cassava'      => 'cassava.png',
        // 'Sweet Potato' => 'sweet-potato.png',
        // 'Taro'         => 'taro.png',
        // 'Potato'       => 'potato.png',
        // 'Carrot'       => 'carrot.png',

        // Herbs & Spices
        // 'Onion'        => 'onion.png',
        // 'Garlic'       => 'garlic.png',
        // 'Ginger'       => 'ginger.png',
        // 'Turmeric'     => 'turmeric.png',
        // 'Lemongrass'   => 'lemongrass.png',

        // Legumes
        // 'Mung Bean'    => 'mung-bean.png',
        // 'Soybean'      => 'soybean.png',
        // 'Peanut'       => 'peanut.png',

        // Fiber & Industrial
        // 'Abaca'        => 'abaca.png',
        // 'Coffee'       => 'coffee.png',
        // 'Cacao'        => 'cacao.png',
        // 'Rubber'       => 'rubber.png',
        // 'Sugarcane'    => 'sugarcane.png',
        // 'Tobacco'      => 'tobacco.png',
        // 'Cotton'       => 'cotton.png',
    ];

    // Resolves the crop image with ?v=filemtime cache-busting:
    // 1. Checks uncommented entry in $cropImages
    // 2. Checks explicit $crop['image'] from controller/data
    // 3. Auto-detects public/images/{slug}.{png|webp|jpg|jpeg}
    // 4. Falls back cleanly to images/crop.png if missing
    $resolveCropImage = function ($crop) use ($cropImages, $fallbackUrl) {
        $name = trim((string) ($crop['name'] ?? ''));
        $candidates = [];

        foreach ($cropImages as $key => $file) {
            if (strcasecmp($key, $name) === 0 && !empty($file)) {
                $candidates[] = ltrim($file, '/');
            }
        }

        if (!empty($crop['image'])) {
            $candidates[] = ltrim($crop['image'], '/');
        }

        if ($name !== '') {
            $slugDash = \Illuminate\Support\Str::slug($name, '-');
            $slugUnderscore = \Illuminate\Support\Str::slug($name, '_');
            foreach (['png', 'webp', 'jpg', 'jpeg'] as $ext) {
                $candidates[] = "{$slugDash}.{$ext}";
                if ($slugUnderscore !== $slugDash) {
                    $candidates[] = "{$slugUnderscore}.{$ext}";
                }
            }
        }

        foreach (array_unique($candidates) as $filename) {
            $relativePath = str_starts_with($filename, 'images/') ? $filename : "images/{$filename}";
            $fullPath = public_path($relativePath);
            if (file_exists($fullPath)) {
                return asset($relativePath) . '?v=' . filemtime($fullPath);
            }
        }

        return $fallbackUrl;
    };

    // Shared pastel crop color map
    $cropColors = [
        // Grains
        'Rice' => '#E8F5E9', 'Corn' => '#FFF59D', 'Wheat' => '#F5E6CA',
        // Fruits
        'Coconut' => '#E0D5C1', 'Pili' => '#D7CCC8', 'Banana' => '#FFF176',
        'Mango' => '#FFCC80', 'Papaya' => '#FFB74D', 'Pineapple' => '#FFE082',
        'Jackfruit' => '#E6EE9C', 'Calamansi' => '#C5E1A5', 'Watermelon' => '#FFAB91',
        'Orange' => '#FFB74D', 'Lemon' => '#FFF59D',
        // Vegetables
        'Tomato' => '#FF8A80', 'Eggplant' => '#D1C4E9', 'Okra' => '#C8E6C9',
        'String Bean' => '#A5D6A7', 'Squash' => '#FFE082', 'Ampalaya' => '#A5D6A7',
        'Pepper' => '#FF8A80', 'Cabbage' => '#C8E6C9', 'Sayote' => '#DCEDC8',
        'Luffa' => '#C8E6C9', 'Cucumber' => '#A5D6A7', 'Lettuce' => '#DCEDC8',
        'Malunggay' => '#A5D6A7', 'Kangkong' => '#C8E6C9',
        // Root Crops
        'Cassava' => '#D7CCC8', 'Sweet Potato' => '#E1BEE7', 'Taro' => '#E1BEE7',
        'Potato' => '#F5E6CA', 'Carrot' => '#FFCC80',
        // Herbs & Spices
        'Onion' => '#F8BBD0', 'Garlic' => '#F5F5F5', 'Ginger' => '#E0D5C1',
        'Turmeric' => '#FFE082', 'Lemongrass' => '#DCEDC8',
        // Legumes
        'Mung Bean' => '#C8E6C9', 'Soybean' => '#FFF59D', 'Peanut' => '#E0D5C1',
        // Fiber & Industrial
        'Abaca' => '#D7CCC8', 'Coffee' => '#D7CCC8', 'Cacao' => '#CFD8DC',
        'Rubber' => '#ECEFF1', 'Sugarcane' => '#DCEDC8', 'Tobacco' => '#D7CCC8',
        'Cotton' => '#FAFAFA',
        'default' => '#E8F5E9',
    ];

    // Returns an ultra-light, almost faded tint of the crop color
    $getCropColor = function ($name) use ($cropColors) {
        $name = trim((string) $name);
        $baseHex = '#E8F5E9';
        if ($name !== '') {
            foreach ($cropColors as $key => $hex) {
                if (strcasecmp($key, $name) === 0) {
                    $baseHex = $hex;
                    break;
                }
            }
        }
        return "color-mix(in srgb, {$baseHex} 24%, #FFFFFF)";
    };

    // Count crops per category for filter badges
    $categoryCounts = ['all' => count($crops)];
    foreach ($crops as $c) {
        $cat = $c['category'] ?? 'vegetable';
        $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
    }
@endphp

<style>
  :root {
    /* Light mode botanical sage palette */
    --cd-sage: #6E9B7D;
    --cd-sage-hover: #5E8A6D;
    --cd-sage-ink: #476E54;
    --cd-sage-tint: #EDF4EF;
    --cd-sage-tint-strong: #E1EDE5;
    --cd-sage-border: #D2E3D7;
    --cd-sage-border-light: #BDD3C4;

    --cd-bg: #F6F9F7;
    --cd-surface: #FFFFFF;
    --cd-surface-hover: #FCFDFC;
    --cd-surface-subtle: #F9FBF9;
    --cd-border: #E2EAE5;
    --cd-border-subtle: #EEF3F0;
    --cd-border-hover: #C8D9CE;

    --cd-text-main: #1E2923;
    --cd-text-secondary: #4D5E54;
    --cd-text-muted: #708277;
    --cd-placeholder: #96A59C;

    --cd-pill-bg: rgba(255, 255, 255, 0.92);
    --cd-card-shadow: 0 8px 20px rgba(110, 155, 125, 0.08);
    --cd-drawer-shadow: -10px 0 36px rgba(30, 41, 35, 0.12);
    --cd-scroll-track: #F4F8F5;
    --cd-icon-ring: rgba(0, 0, 0, 0.045);

    --cd-radius-lg: 16px;
    --cd-radius-md: 12px;
    --cd-radius-sm: 8px;

    /* Fallback height of system top header on mobile screens */
    --cd-mobile-header-height: 56px;
  }

  /* ── Dark theme (scoped to [data-theme="dark"] matching farmer/dashboard.blade.php) ── */
  [data-theme="dark"] {
    --cd-sage: #16301f;
    --cd-sage-hover: #1f422b;
    --cd-sage-ink: #6ee7a0;
    --cd-sage-tint: #16301f;
    --cd-sage-tint-strong: #1c3d28;
    --cd-sage-border: #2a4d36;
    --cd-sage-border-light: #9CC4A3;

    --cd-bg: #0b1120;
    --cd-surface: #111827;
    --cd-surface-hover: #162032;
    --cd-surface-subtle: #0f172a;
    --cd-border: #334155;
    --cd-border-subtle: #1e293b;
    --cd-border-hover: #9CC4A3;

    --cd-text-main: #d6f5e3;
    --cd-text-secondary: #9fb3a6;
    --cd-text-muted: #7d8f86;
    --cd-placeholder: #64748b;

    --cd-pill-bg: rgba(17, 24, 39, 0.92);
    --cd-card-shadow: 0 8px 22px rgba(0, 0, 0, 0.35);
    --cd-drawer-shadow: -10px 0 36px rgba(0, 0, 0, 0.55);
    --cd-scroll-track: #0f172a;
    --cd-icon-ring: rgba(255, 255, 255, 0.08);
  }

  .cd-page {
    min-height: 100vh;
    background-color: var(--cd-bg);
    color: var(--cd-text-main);
    font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  }

  .cd-container {
    max-width: 1320px;
    margin: 0 auto;
    padding: 28px 32px 64px;
  }

  /* ── Breadcrumb ── */
  .cd-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--cd-text-muted);
    margin-bottom: 20px;
  }
  .cd-breadcrumb a {
    color: var(--cd-text-muted) !important;
    text-decoration: none;
    transition: color 0.15s ease;
  }
  .cd-breadcrumb a:hover {
    color: var(--cd-sage-ink) !important;
  }
  [data-theme="dark"] .cd-breadcrumb a:hover {
    color: #a7f3d0 !important;
  }
  .cd-breadcrumb-current {
    color: var(--cd-text-main);
    font-weight: 600;
  }

  /* ── Hero Banner ── */
  .cd-hero {
    position: relative;
    border-radius: var(--cd-radius-lg);
    border: 1px solid var(--cd-sage-border);
    padding: 32px 34px;
    margin-bottom: 24px;
    box-shadow: 0 2px 10px rgba(71, 110, 84, 0.05);
    overflow: hidden;
    background-color: #3E5947;
    background-size: cover;
    background-position: center;
  }
  .cd-hero::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #8BB599 0%, #B4D2BE 100%);
  }
  .cd-hero-top {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    flex-wrap: wrap;
  }
  .cd-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #E3F0E7;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
    margin-bottom: 8px;
  }
  .cd-hero h1 {
    font-size: 26px;
    font-weight: 800;
    color: #FFFFFF;
    text-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
    margin: 0 0 8px 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
  }
  .cd-hero-desc {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.92);
    text-shadow: 0 1px 6px rgba(0, 0, 0, 0.35);
    margin: 0;
    line-height: 1.6;
    max-width: 620px;
  }
  .cd-trust-pill {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--cd-pill-bg);
    backdrop-filter: blur(6px);
    border: 1px solid var(--cd-sage-border);
    padding: 10px 16px;
    border-radius: var(--cd-radius-md);
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(71, 110, 84, 0.05);
  }
  .cd-trust-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: var(--cd-sage-tint-strong);
    color: var(--cd-sage-ink);
    border: 1px solid var(--cd-sage-border);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .cd-trust-text strong {
    display: block;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--cd-sage-ink);
    line-height: 1.2;
  }
  .cd-trust-text span {
    display: block;
    font-size: 11.5px;
    color: var(--cd-text-secondary);
    margin-top: 2px;
  }

  /* ── Search & Category Filter Toolbar ── */
  .cd-toolbar {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-bottom: 20px;
  }
  .cd-search-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }
  .cd-search-wrap {
    position: relative;
    flex: 1;
    min-width: 260px;
  }
  .cd-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--cd-text-muted);
    pointer-events: none;
  }
  .cd-search,
  input.cd-search {
    width: 100%;
    padding: 11px 38px 11px 42px;
    background-color: var(--cd-surface) !important;
    border: 1px solid var(--cd-border) !important;
    border-radius: var(--cd-radius-md);
    font-size: 14px;
    color: var(--cd-text-main) !important;
    font-family: inherit;
    box-shadow: none !important;
    outline: none !important;
  }
  .cd-search::placeholder {
    color: var(--cd-placeholder) !important;
  }
  .cd-search:hover,
  .cd-search:focus,
  .cd-search:focus-visible,
  .cd-search:active,
  input.cd-search:hover,
  input.cd-search:focus,
  input.cd-search:focus-visible,
  input.cd-search:active {
    outline: none !important;
    border-color: var(--cd-border-hover) !important;
    background-color: var(--cd-surface) !important;
    box-shadow: none !important;
    --tw-ring-shadow: 0 0 #0000 !important;
    --tw-ring-offset-shadow: 0 0 #0000 !important;
  }
  .cd-search::-webkit-search-cancel-button,
  .cd-search::-webkit-search-decoration {
    -webkit-appearance: none;
    appearance: none;
  }
  .cd-search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: var(--cd-sage-tint);
    border: none;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    color: var(--cd-sage-ink);
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
  }
  .cd-search-clear.visible {
    display: flex;
  }
  .cd-search-clear:hover {
    background: var(--cd-sage-tint-strong);
  }

  /* Horizontal scrollable category pills */
  .cd-filter-scroll {
    display: flex;
    align-items: center;
    gap: 8px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: none;
    -ms-overflow-style: none;
  }
  .cd-filter-scroll::-webkit-scrollbar {
    display: none;
  }
  .cd-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 600;
    background: var(--cd-surface);
    border: 1px solid var(--cd-border);
    color: var(--cd-text-secondary);
    cursor: pointer;
    white-space: nowrap;
    font-family: inherit;
    transition: all 0.15s ease;
  }
  .cd-filter-btn:hover {
    border-color: var(--cd-border-hover);
    color: var(--cd-text-main);
    background: var(--cd-surface-subtle);
  }
  .cd-filter-btn.active {
    background: var(--cd-sage);
    border-color: var(--cd-sage);
    color: #ffffff;
  }
  [data-theme="dark"] .cd-filter-btn.active {
    background: #16301f;
    border-color: #6ee7a0;
    color: #d6f5e3;
  }
  .cd-filter-count {
    font-size: 11px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 999px;
    background: var(--cd-sage-tint);
    color: var(--cd-sage-ink);
  }
  .cd-filter-btn.active .cd-filter-count {
    background: rgba(255, 255, 255, 0.24);
    color: #ffffff;
  }
  [data-theme="dark"] .cd-filter-btn.active .cd-filter-count {
    background: rgba(110, 231, 160, 0.18);
    color: #6ee7a0;
  }

  /* Status bar */
  .cd-status-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    color: var(--cd-text-muted);
    margin-bottom: 14px;
  }
  .cd-status-bar strong {
    color: var(--cd-text-main);
    font-weight: 700;
  }
  .cd-reset-link {
    background: none;
    border: none;
    color: var(--cd-sage-ink) !important;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
    display: none;
  }
  [data-theme="dark"] .cd-reset-link:hover {
    color: #a7f3d0 !important;
  }
  .cd-reset-link.visible {
    display: inline;
  }

  /* ── Directory Grid & Cards ── */
  .cd-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(275px, 1fr));
    gap: 16px;
  }

  .cd-card {
    background: var(--cd-surface);
    border: 1px solid var(--cd-border);
    border-radius: var(--cd-radius-md);
    padding: 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 14px;
    cursor: pointer;
    text-align: left;
    color: var(--cd-text-main);
    transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease, background-color 0.16s ease;
    position: relative;
  }
  .cd-card:hover,
  .cd-card:focus-visible {
    outline: none;
    border-color: var(--cd-sage-border-light);
    background-color: var(--cd-surface-hover);
    transform: translateY(-2px);
    box-shadow: var(--cd-card-shadow);
  }
  .cd-card.is-selected {
    border-color: var(--cd-sage-border-light);
    background-color: var(--cd-sage-tint);
    box-shadow: 0 0 0 2px rgba(156, 196, 163, 0.35);
  }

  .cd-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
  }
  .cd-card-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    border: 1px solid var(--cd-icon-ring);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
  }
  .cd-card-icon img {
    width: 34px;
    height: 34px;
    object-fit: contain;
  }
  .cd-card-title-group {
    min-width: 0;
    flex: 1;
  }
  .cd-card-name {
    font-size: 15.5px;
    font-weight: 700;
    color: var(--cd-text-main) !important;
    line-height: 1.25;
    margin: 0 0 5px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .cd-card-tag {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 9px;
    border-radius: 6px;
    background: var(--cd-sage-tint);
    color: var(--cd-sage-ink);
    border: 1px solid var(--cd-sage-border);
  }

  /* Quick-glance agronomic metrics on card face */
  .cd-card-specs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid var(--cd-border-subtle);
  }
  .cd-spec-mini {
    min-width: 0;
  }
  .cd-spec-mini-label {
    display: block;
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--cd-text-muted);
    margin-bottom: 2px;
  }
  .cd-spec-mini-val {
    display: block;
    font-size: 12.5px;
    font-weight: 500;
    color: var(--cd-text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .cd-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 600;
    color: var(--cd-sage-ink);
    padding-top: 2px;
  }
  .cd-card:hover .cd-card-footer {
    color: var(--cd-sage-hover);
  }
  [data-theme="dark"] .cd-card:hover .cd-card-footer {
    color: #a7f3d0;
  }
  .cd-card-footer svg {
    transition: transform 0.15s ease;
  }
  .cd-card:hover .cd-card-footer svg {
    transform: translateX(3px);
  }

  /* ── Empty State (Open Illustration Style + Dark Mode Consistent) ── */
  .cd-empty {
    display: none;
    background: var(--cd-surface);
    border: 1px dashed var(--cd-border);
    border-radius: var(--cd-radius-lg);
    padding: 64px 24px;
    text-align: center;
  }
  .cd-empty-illustration {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--cd-sage);
    opacity: 0.85;
    margin-bottom: 16px;
  }
  [data-theme="dark"] .cd-empty-illustration {
    color: #6ee7a0;
    opacity: 0.8;
  }
  .cd-empty-illustration svg {
    width: 60px;
    height: 60px;
    stroke-width: 1.5;
  }
  .cd-empty h3 {
    font-size: 17px;
    font-weight: 700;
    color: var(--cd-text-main) !important;
    margin: 0 0 6px 0;
  }
  .cd-empty p {
    font-size: 13.5px;
    color: var(--cd-text-secondary) !important;
    max-width: 380px;
    margin: 0 auto 20px auto;
    line-height: 1.5;
  }
  .cd-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: var(--cd-radius-sm);
    background: var(--cd-sage);
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    font-family: inherit;
    transition: background-color 0.15s ease, border-color 0.15s ease;
  }
  .cd-btn-primary:hover {
    background: var(--cd-sage-hover);
  }
  [data-theme="dark"] .cd-btn-primary {
    background: #16301f;
    border-color: #2a4d36;
    color: #6ee7a0 !important;
  }
  [data-theme="dark"] .cd-btn-primary:hover {
    background: #1f422b;
    border-color: #6ee7a0;
    color: #a7f3d0 !important;
  }

  /* ── Overlay-Free Reference Drawer ── */
  .cd-drawer {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    max-width: 490px;
    background: var(--cd-surface);
    border-left: 1px solid var(--cd-border);
    box-shadow: var(--cd-drawer-shadow);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    transform: translateX(102%);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .cd-drawer.open {
    transform: translateX(0);
  }

  .cd-drawer-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--cd-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: var(--cd-surface-subtle);
  }
  .cd-drawer-badge {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--cd-sage-ink);
    background: var(--cd-sage-tint);
    border: 1px solid var(--cd-sage-border);
    padding: 4px 10px;
    border-radius: 6px;
  }
  .cd-drawer-close {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--cd-border);
    background: var(--cd-surface);
    color: var(--cd-text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
  }
  .cd-drawer-close:hover {
    background: var(--cd-sage-tint);
    border-color: var(--cd-sage-border);
    color: var(--cd-sage-ink);
  }

  /* Custom Botanical Scrollbar for Crop Detail Drawer */
  .cd-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    scrollbar-width: thin;
    scrollbar-color: var(--cd-sage-border-light) var(--cd-scroll-track);
  }
  .cd-drawer-body::-webkit-scrollbar {
    width: 6px;
  }
  .cd-drawer-body::-webkit-scrollbar-track {
    background: var(--cd-scroll-track);
    border-radius: 999px;
    margin: 6px 0;
  }
  .cd-drawer-body::-webkit-scrollbar-thumb {
    background-color: var(--cd-sage-border);
    border-radius: 999px;
    border: 1px solid var(--cd-scroll-track);
    transition: background-color 0.15s ease;
  }
  .cd-drawer-body::-webkit-scrollbar-thumb:hover {
    background-color: var(--cd-sage-border-light);
  }
  .cd-drawer-body::-webkit-scrollbar-button {
    display: none;
  }

  .cd-drawer-hero {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
  }
  .cd-drawer-icon {
    width: 66px;
    height: 66px;
    border-radius: 16px;
    border: 1px solid var(--cd-icon-ring);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .cd-drawer-icon img {
    width: 44px;
    height: 44px;
    object-fit: contain;
  }
  .cd-drawer-title {
    font-size: 22px;
    font-weight: 800;
    color: var(--cd-text-main) !important;
    margin: 0 0 6px 0;
    line-height: 1.2;
  }
  .cd-drawer-cat {
    display: inline-block;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 6px;
    background: var(--cd-sage-tint);
    color: var(--cd-sage-ink);
    border: 1px solid var(--cd-sage-border);
  }

  .cd-drawer-section {
    margin-bottom: 22px;
  }
  .cd-drawer-section-title {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--cd-text-muted) !important;
    margin: 0 0 10px 0;
  }
  .cd-drawer-desc {
    font-size: 14px;
    font-weight: 400;
    letter-spacing: 0.01em;
    line-height: 1.65;
    color: var(--cd-text-secondary) !important;
    background: var(--cd-surface-subtle);
    border: 1px solid var(--cd-border);
    padding: 14px 16px;
    border-radius: var(--cd-radius-md);
    margin: 0;
  }

  /* Structured 2-column agronomic table */
  .cd-spec-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }
  .cd-spec-box {
    background: var(--cd-surface-subtle);
    border: 1px solid var(--cd-border);
    border-radius: var(--cd-radius-sm);
    padding: 12px 14px;
  }
  .cd-spec-box.full-width {
    grid-column: 1 / -1;
  }
  .cd-spec-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--cd-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 5px;
  }
  .cd-spec-value {
    font-size: 13.5px;
    font-weight: 400;
    letter-spacing: 0.012em;
    color: var(--cd-text-main);
    line-height: 1.5;
  }

  /* Rounded Rectangle Variety Boxes with Generous Padding */
  .cd-varieties-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }
  .cd-variety-chip {
    display: inline-flex;
    align-items: center;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.01em;
    line-height: 1.3;
    color: var(--cd-sage-ink);
    background: var(--cd-sage-tint);
    border: 1px solid var(--cd-sage-border);
    padding: 8px 14px;
    border-radius: 8px;
  }

  .cd-drawer-footer {
    padding: 14px 24px;
    border-top: 1px solid var(--cd-border);
    background: var(--cd-surface-subtle);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }
  .cd-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: var(--cd-radius-sm);
    border: 1px solid var(--cd-border);
    background: var(--cd-surface);
    color: var(--cd-text-main);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.15s ease;
  }
  .cd-nav-btn:hover:not(:disabled) {
    border-color: var(--cd-sage-border-light);
    color: var(--cd-sage-ink);
    background: var(--cd-sage-tint);
  }
  .cd-nav-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }

  /* ── Responsive Breakpoints ── */
  @media (max-width: 768px) {
    .cd-container {
      padding: 18px 16px 48px;
    }
    .cd-hero {
      padding: 22px 20px;
    }
    .cd-hero h1 {
      font-size: 21px;
    }
    .cd-trust-pill {
      width: 100%;
    }
    .cd-grid {
      grid-template-columns: 1fr;
      gap: 12px;
    }

    /* Full-screen sub-page view reaching right up to the bottom of the system header */
    .cd-drawer {
      top: var(--cd-mobile-header-height);
      left: 0;
      right: 0;
      bottom: 0;
      max-width: 100%;
      max-height: none;
      height: calc(100vh - var(--cd-mobile-header-height));
      height: calc(100dvh - var(--cd-mobile-header-height));
      border-left: none;
      border-top: 1px solid var(--cd-border);
      border-radius: 0;
      box-shadow: none;
      transform: translateX(100%);
    }
    .cd-drawer.open {
      transform: translateX(0);
    }
    .cd-drawer-header {
      padding: 14px 18px;
    }
    .cd-drawer-body {
      padding: 20px 18px;
    }
    .cd-drawer-footer {
      padding: 12px 18px;
    }
    .cd-spec-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="cd-page">
  <div class="cd-container">

    {{-- ═══ 1. Breadcrumb ═══ --}}
    <nav class="cd-breadcrumb" aria-label="Breadcrumb">
      <a href="{{ route('dashboard.farmer') }}">Dashboard</a>
      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      <span class="cd-breadcrumb-current">Crops Directory</span>
    </nav>

    {{-- ═══ 2. Hero Banner (Cache-Busted Inline Background Image) ═══ --}}
    <header class="cd-hero" @if($bannerBgImage) style="background-image: url('{{ $bannerBgImage }}');" @endif>
      <div class="cd-hero-top">
        <div>
          <div class="cd-eyebrow">
            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            Agronomic Reference Library
          </div>
          <h1>Bulan &amp; Bicol Crop Directory</h1>
          <p class="cd-hero-desc">
            Verified cultivation profiles, planting windows, soil requirements, and local varieties adapted for Bulan, Sorsogon and the Bicol region—calibrated with the Decision Formula system.
          </p>
        </div>

        <div class="cd-trust-pill">
          <div class="cd-trust-icon">
            <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
          </div>
          <div class="cd-trust-text">
            <strong>{{ count($crops) }} Verified Crops</strong>
            <span>Regional Decision Formula Data</span>
          </div>
        </div>
      </div>
    </header>

    {{-- ═══ 3. Search & Category Filter Bar ═══ --}}
    <div class="cd-toolbar" data-fc aria-label="Search and filter crops">
      <div class="cd-search-row">
        <div class="cd-search-wrap">
          <svg class="cd-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input id="cdSearchInput" type="search" placeholder="Search by crop name, local variety, soil type, or season..." class="cd-search" autocomplete="off" />
          <button type="button" id="cdSearchClear" class="cd-search-clear" aria-label="Clear search">&times;</button>
        </div>
      </div>

      <button type="button" class="btn-filter" data-fc-toggle aria-expanded="false"
              aria-label="Show filter options" title="Filters">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
        <span class="btn-filter-dot" data-fc-dot hidden></span>
      </button>

      <div class="filter-panel" data-fc-panel hidden>
        <div id="cdCategoryFilters" class="cd-filter-scroll" role="tablist" aria-label="Crop categories">
          <button type="button" class="cd-filter-btn active" data-category="all">
            All Crops <span class="cd-filter-count">{{ $categoryCounts['all'] ?? 0 }}</span>
          </button>
          @foreach($categoryLabels as $catKey => $catTitle)
            @if(($categoryCounts[$catKey] ?? 0) > 0)
              <button type="button" class="cd-filter-btn" data-category="{{ $catKey }}">
                {{ $catTitle }} <span class="cd-filter-count">{{ $categoryCounts[$catKey] }}</span>
              </button>
            @endif
          @endforeach
        </div>
      </div>
    </div>

    {{-- ═══ 4. Live Status Bar ═══ --}}
    <div class="cd-status-bar">
      <div>Showing <strong id="cdVisibleCount">{{ count($crops) }}</strong> of <strong>{{ count($crops) }}</strong> crops</div>
      <button type="button" id="cdResetFilters" class="cd-reset-link">Reset filters</button>
    </div>

    {{-- ═══ 5. Scannable Directory Grid ═══ --}}
    <div id="cdCropGrid" class="cd-grid">
      @foreach($crops as $index => $crop)
        @php
            $category = $crop['category'] ?? 'vegetable';
            $imgSrc = $resolveCropImage($crop);
            $label = $categoryLabels[$category] ?? ucfirst($category);
            $cropColor = $getCropColor($crop['name']);

            $varietiesStr = !empty($crop['varieties']) ? implode(' ', $crop['varieties']) : '';
            $searchIndex = strtolower(trim(
                ($crop['search'] ?? $crop['name']) . ' ' .
                $label . ' ' .
                ($crop['best_season'] ?? '') . ' ' .
                ($crop['soil_type'] ?? '') . ' ' .
                $varietiesStr
            ));

            $cropPayload = [
                'name'            => $crop['name'],
                'categoryLabel'   => $label,
                'image'           => $imgSrc,
                'color'           => $cropColor,
                'planting_method' => $crop['planting_method'] ?? '—',
                'days_to_harvest' => $crop['days_to_harvest'] ?? '—',
                'best_season'     => $crop['best_season'] ?? '—',
                'planting_window' => $crop['planting_window'] ?? '—',
                'soil_type'       => $crop['soil_type'] ?? '—',
                'water_needs'     => $crop['water_needs'] ?? '—',
                'spacing'         => $crop['spacing'] ?? '—',
                'varieties'       => $crop['varieties'] ?? [],
                'description'     => $crop['description'] ?? 'No agronomic description available.',
            ];
        @endphp

        <article class="cd-card"
                 tabindex="0"
                 role="button"
                 aria-label="View cultivation reference for {{ $crop['name'] }}"
                 data-category="{{ $category }}"
                 data-search="{{ $searchIndex }}"
                 data-crop='@json($cropPayload)'>

          <div>
            <div class="cd-card-header">
              <div class="cd-card-icon" style="background:{{ $cropColor }};">
                <img src="{{ $imgSrc }}" alt="{{ $crop['name'] }}" loading="lazy" onerror="this.onerror=null;this.src='{{ $fallbackUrl }}';">
              </div>
              <div class="cd-card-title-group">
                <h2 class="cd-card-name">{{ $crop['name'] }}</h2>
                <span class="cd-card-tag">{{ $label }}</span>
              </div>
            </div>

            <div class="cd-card-specs" style="margin-top: 14px;">
              <div class="cd-spec-mini">
                <span class="cd-spec-mini-label">Harvest Cycle</span>
                <span class="cd-spec-mini-val">{{ $crop['days_to_harvest'] ?? '—' }}</span>
              </div>
              <div class="cd-spec-mini">
                <span class="cd-spec-mini-label">Best Season</span>
                <span class="cd-spec-mini-val">{{ $crop['best_season'] ?? '—' }}</span>
              </div>
            </div>
          </div>

          <div class="cd-card-footer">
            <span>View Cultivation Guide</span>
            <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </div>
        </article>
      @endforeach
    </div>

    {{-- ═══ 6. Empty State (Open Illustration) ═══ --}}
    <div id="cdEmptyState" class="cd-empty">
      <div class="cd-empty-illustration" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-groceries">
          <path d="M10 15h4"/>
          <path d="M19.424 11.095a2.5 2.5 0 00.471-2.309 2 2 0 00.424-3.286 2 2 0 00-2.143-3.322 2 2 0 00-.676.502 2 2 0 00-3.287.424 2.5 2.5 0 00-3.189 2.06A3 3 0 0012 11l4-4"/>
          <path d="M4 12.006A1 1 0 014.994 11H19a1 1 0 011 1v7a2 2 0 01-2 2H6a2 2 0 01-2-2z"/>
          <path d="M7 11a4 4 0 01-4-4V5a1 1 0 011-1h2a4 4 0 013.584 2.222"/>
        </svg>
      </div>
      <h3>No matching crops in directory</h3>
      <p>We couldn't find any crops matching your current search or category filter.</p>
      <button type="button" id="cdEmptyResetBtn" class="cd-btn-primary">Show All Crops</button>
    </div>

  </div>
</div>

{{-- ═══ 7. Overlay-Free Crop Reference Drawer / Mobile Full-Page View ═══ --}}
<aside id="cdDrawer" class="cd-drawer" role="dialog" aria-labelledby="cdDrawerTitle" aria-hidden="true">
  <div class="cd-drawer-header">
    <span class="cd-drawer-badge">Crop Reference Sheet</span>
    <button type="button" id="cdDrawerClose" class="cd-drawer-close" aria-label="Close reference sheet" title="Close">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>

  <div class="cd-drawer-body">
    <div class="cd-drawer-hero">
      <div id="cdDrawerIcon" class="cd-drawer-icon">
        <img id="cdDrawerImg" src="" alt="" onerror="this.onerror=null;this.src='{{ $fallbackUrl }}';">
      </div>
      <div>
        <h2 id="cdDrawerTitle" class="cd-drawer-title"></h2>
        <span id="cdDrawerCat" class="cd-drawer-cat"></span>
      </div>
    </div>

    <div class="cd-drawer-section">
      <h3 class="cd-drawer-section-title">Overview &amp; Regional Suitability</h3>
      <p id="cdDrawerDesc" class="cd-drawer-desc"></p>
    </div>

    <div class="cd-drawer-section">
      <h3 class="cd-drawer-section-title">Agronomic Specifications</h3>
      <div class="cd-spec-grid">
        <div class="cd-spec-box">
          <div class="cd-spec-label">Planting Method</div>
          <div id="cdSpecMethod" class="cd-spec-value"></div>
        </div>
        <div class="cd-spec-box">
          <div class="cd-spec-label">Days to Harvest</div>
          <div id="cdSpecHarvest" class="cd-spec-value"></div>
        </div>
        <div class="cd-spec-box">
          <div class="cd-spec-label">Best Season</div>
          <div id="cdSpecSeason" class="cd-spec-value"></div>
        </div>
        <div class="cd-spec-box">
          <div class="cd-spec-label">Planting Window</div>
          <div id="cdSpecWindow" class="cd-spec-value"></div>
        </div>
        <div class="cd-spec-box">
          <div class="cd-spec-label">Preferred Soil Type</div>
          <div id="cdSpecSoil" class="cd-spec-value"></div>
        </div>
        <div class="cd-spec-box">
          <div class="cd-spec-label">Water Requirement</div>
          <div id="cdSpecWater" class="cd-spec-value"></div>
        </div>
        <div class="cd-spec-box full-width">
          <div class="cd-spec-label">Recommended Spacing</div>
          <div id="cdSpecSpacing" class="cd-spec-value"></div>
        </div>
      </div>
    </div>

    <div id="cdVarietiesSection" class="cd-drawer-section">
      <h3 class="cd-drawer-section-title">Varieties Grown in Sorsogon</h3>
      <div id="cdDrawerVarieties" class="cd-varieties-list"></div>
    </div>
  </div>

  <div class="cd-drawer-footer">
    <button type="button" id="cdPrevCrop" class="cd-nav-btn">
      <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Previous
    </button>
    <span id="cdDrawerCounter" style="font-size:12px; color:var(--cd-text-muted); font-weight:600;"></span>
    <button type="button" id="cdNextCrop" class="cd-nav-btn">
      Next
      <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
  </div>
</aside>

<script>
(function() {
    const searchInput     = document.getElementById('cdSearchInput');
    const searchClear     = document.getElementById('cdSearchClear');
    const categoryBtns    = document.querySelectorAll('#cdCategoryFilters .cd-filter-btn');
    const cropCards       = Array.from(document.querySelectorAll('#cdCropGrid .cd-card'));
    const emptyState      = document.getElementById('cdEmptyState');
    const visibleCountEl  = document.getElementById('cdVisibleCount');
    const resetFiltersBtn = document.getElementById('cdResetFilters');
    const emptyResetBtn   = document.getElementById('cdEmptyResetBtn');

    // Drawer elements
    const drawer          = document.getElementById('cdDrawer');
    const closeBtn        = document.getElementById('cdDrawerClose');
    const prevBtn         = document.getElementById('cdPrevCrop');
    const nextBtn         = document.getElementById('cdNextCrop');
    const counterEl       = document.getElementById('cdDrawerCounter');

    let activeCategory = 'all';
    let visibleCards   = [...cropCards];
    let currentIndex   = -1;

    // Auto-detect system header height on mobile so the drawer meets its bottom edge cleanly
    function syncMobileHeaderOffset() {
        const headerEl = document.querySelector('header:not(.cd-hero), nav.navbar, .topbar, .mobile-header, [data-topbar]');
        if (headerEl && window.innerWidth <= 768) {
            const rect = headerEl.getBoundingClientRect();
            if (rect.height > 0 && rect.top <= 10) {
                document.documentElement.style.setProperty('--cd-mobile-header-height', rect.height + 'px');
            }
        }
    }
    syncMobileHeaderOffset();
    window.addEventListener('resize', syncMobileHeaderOffset);

    function applyFilters() {
        const query = searchInput.value.toLowerCase().trim();
        searchClear.classList.toggle('visible', query.length > 0);

        visibleCards = [];

        cropCards.forEach(card => {
            const haystack = card.getAttribute('data-search') || '';
            const category = card.getAttribute('data-category');
            const matchesSearch   = !query || haystack.includes(query);
            const matchesCategory = activeCategory === 'all' || category === activeCategory;
            const show = matchesSearch && matchesCategory;

            card.style.display = show ? '' : 'none';
            if (show) visibleCards.push(card);
        });

        visibleCountEl.textContent = visibleCards.length;
        emptyState.style.display = visibleCards.length > 0 ? 'none' : 'block';

        const isFiltered = query.length > 0 || activeCategory !== 'all';
        resetFiltersBtn.classList.toggle('visible', isFiltered);
    }

    function resetAll() {
        searchInput.value = '';
        activeCategory = 'all';
        categoryBtns.forEach(b => b.classList.toggle('active', b.getAttribute('data-category') === 'all'));
        applyFilters();
    }

    function openDrawer(card) {
        const idx = visibleCards.indexOf(card);
        if (idx === -1) return;
        currentIndex = idx;

        syncMobileHeaderOffset();

        const data = JSON.parse(card.getAttribute('data-crop'));

        cropCards.forEach(c => c.classList.remove('is-selected'));
        card.classList.add('is-selected');

        document.getElementById('cdDrawerIcon').style.background = data.color;
        const imgEl = document.getElementById('cdDrawerImg');
        imgEl.src = data.image;
        imgEl.alt = data.name;

        document.getElementById('cdDrawerTitle').textContent = data.name;
        document.getElementById('cdDrawerCat').textContent   = data.categoryLabel;
        document.getElementById('cdDrawerDesc').textContent  = data.description;

        document.getElementById('cdSpecMethod').textContent  = data.planting_method;
        document.getElementById('cdSpecHarvest').textContent = data.days_to_harvest;
        document.getElementById('cdSpecSeason').textContent  = data.best_season;
        document.getElementById('cdSpecWindow').textContent  = data.planting_window;
        document.getElementById('cdSpecSoil').textContent    = data.soil_type;
        document.getElementById('cdSpecWater').textContent   = data.water_needs;
        document.getElementById('cdSpecSpacing').textContent = data.spacing;

        const varietiesSection = document.getElementById('cdVarietiesSection');
        const varietiesList    = document.getElementById('cdDrawerVarieties');
        varietiesList.innerHTML = '';

        if (Array.isArray(data.varieties) && data.varieties.length > 0) {
            data.varieties.forEach(v => {
                const chip = document.createElement('span');
                chip.className = 'cd-variety-chip';
                chip.textContent = v;
                varietiesList.appendChild(chip);
            });
            varietiesSection.style.display = 'block';
        } else {
            varietiesSection.style.display = 'none';
        }

        counterEl.textContent = `${currentIndex + 1} of ${visibleCards.length}`;
        prevBtn.disabled = currentIndex <= 0;
        nextBtn.disabled = currentIndex >= visibleCards.length - 1;

        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        drawer.querySelector('.cd-drawer-body').scrollTop = 0;

        if (window.innerWidth <= 768) {
            document.body.style.overflow = 'hidden';
        }
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        cropCards.forEach(c => c.classList.remove('is-selected'));
        document.body.style.overflow = '';
    }

    // Search & Filter Listeners
    searchInput.addEventListener('input', applyFilters);
    searchClear.addEventListener('click', () => {
        searchInput.value = '';
        applyFilters();
        searchInput.focus();
    });

    resetFiltersBtn.addEventListener('click', resetAll);
    emptyResetBtn.addEventListener('click', resetAll);

    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            categoryBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeCategory = this.getAttribute('data-category');
            applyFilters();
        });
    });

    // Open drawer on card click
    cropCards.forEach(card => {
        card.addEventListener('click', (e) => {
            e.stopPropagation();
            if (drawer.classList.contains('open') && card.classList.contains('is-selected')) {
                closeDrawer();
            } else {
                openDrawer(card);
            }
        });
        card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openDrawer(card);
            }
        });
    });

    // Close drawer via top-right X button
    closeBtn.addEventListener('click', closeDrawer);

    // Close drawer when clicking anywhere outside the drawer
    document.addEventListener('click', (e) => {
        if (!drawer.classList.contains('open')) return;
        const clickedInsideDrawer = drawer.contains(e.target);
        const clickedCard = e.target.closest('.cd-card');
        if (!clickedInsideDrawer && !clickedCard) {
            closeDrawer();
        }
    });

    // Prev / Next navigation inside drawer
    prevBtn.addEventListener('click', () => {
        if (currentIndex > 0) openDrawer(visibleCards[currentIndex - 1]);
    });

    nextBtn.addEventListener('click', () => {
        if (currentIndex < visibleCards.length - 1) openDrawer(visibleCards[currentIndex + 1]);
    });

    document.addEventListener('keydown', e => {
        if (!drawer.classList.contains('open')) return;
        if (e.key === 'Escape') closeDrawer();
        if (e.key === 'ArrowLeft' && currentIndex > 0) openDrawer(visibleCards[currentIndex - 1]);
        if (e.key === 'ArrowRight' && currentIndex < visibleCards.length - 1) openDrawer(visibleCards[currentIndex + 1]);
    });
})();
</script>

@endsection