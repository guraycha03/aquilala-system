<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- ────────────────────────────────────────────────────────────────────
         SEO block (the ONLY indexable page in the app is the public
         landing/login view — every other auth screen is noindex by default).

         Pages opt in/out with sections:
             @section('title', '…')          page <title> (+ "| Aquilala" suffix)
             @section('description', '…')    meta description
             @section('keywords', '…')       meta keywords
             @section('robots', 'index, follow')          login does this
             @section('canonical', url('/'))              login does this
         ──────────────────────────────────────────────────────────────── --}}
    @php
        $siteName = 'Aquilala Irrigators Association Inc.';

        $pageTitle = trim($__env->yieldContent('title'));
        $seoTitle = $pageTitle === ''
            ? $siteName . ' — Member Sign In'
            : (str_contains($pageTitle, $siteName) ? $pageTitle : $pageTitle . ' | Aquilala');

        $seoDescription = trim($__env->yieldContent('description'));
        if ($seoDescription === '') {
            $seoDescription = 'Official member portal of the Aquilala Irrigators Association Inc. (Aqui lala) — '
                . 'sign in for crop, harvest, quotation and farm services in Barangay Aquino, Bulan, Sorsogon.';
        }

        $seoKeywords = trim($__env->yieldContent('keywords'));
        if ($seoKeywords === '') {
            $seoKeywords = 'Aquilala, Aqui lala, Aquilala Irrigators, Aquilala Irrigators Association, '
                . 'Aquilala Bulan, Aquilala Sorsogon, Bulan Sorsogon farming services, irrigation association Philippines, '
                . 'Barangay Aquino Bulan Sorsogon, farmer member portal, agricultural services Sorsogon';
        }

        $seoRobots    = trim($__env->yieldContent('robots')) ?: 'noindex, nofollow';
        $seoCanonical = trim($__env->yieldContent('canonical')) ?: url()->current();
        $seoImage     = asset('assets/og-banner.png');
    @endphp

    {{-- Search result snippet (SERP) --}}
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    {{-- Google Search Console ownership verification (permanent — Google
         re-checks periodically, so never remove this line). --}}
    <meta name="google-site-verification" content="j0hIuCOLDdacy0xajDuaRgm5ZDVPNlMcBDFcDrrM0DY" />

    {{-- Favicons / home-screen icons --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">

    {{-- ── Open Graph: link preview on Facebook, Messenger, Viber,
         WhatsApp, LinkedIn, iMessage, Telegram ── --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="en_PH">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $siteName }} — Barangay Aquino, Bulan, Sorsogon">

    {{-- ── Twitter / X card ── --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="twitter:image:alt" content="{{ $siteName }} — Barangay Aquino, Bulan, Sorsogon">

    {{-- ── Schema.org: entity mapping so Google resolves "Aquilala" and the
         typo/space variant "Aqui lala" (and partial matches such as
         "Aquilala Irrigators", "Aquilala Bulan") to this association ── --}}
    <script type="application/ld+json">
    {{-- NOTE: the '@context' key is written as '@' . 'context' on purpose —
         a literal "@context" gets compiled as a Blade directive. --}}
    {!! json_encode([
        '@' . 'context'  => 'https://schema.org',
        '@type'          => 'Organization',
        '@id'            => url('/') . '#organization',
        'name'           => $siteName,
        'alternateName'  => [
            'Aquilala',
            'Aqui lala',
            'Aquilala Irrigators',
            'Aquilala Irrigators Association',
            'Aquilala IA',
            'Aquilala Irrigators Association Inc',
        ],
        'url'            => url('/'),
        'logo'           => asset('images/logo.png'),
        'image'          => $seoImage,
        'description'    => 'Agricultural organization serving irrigators and farmers of Barangay Aquino, Bulan, Sorsogon, Philippines — member portal for crop, harvest, quotation and farm services.',
        'address'        => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'IA Office, Barangay Aquino',
            'addressLocality' => 'Bulan',
            'addressRegion'   => 'Sorsogon',
            'addressCountry'  => 'PH',
        ],
        'areaServed'     => [
            '@type' => 'AdministrativeArea',
            'name'  => 'Bulan, Sorsogon, Philippines',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    {{-- PWA: same install/offline support as the main app layout — see the
         comment block in layouts/sidebar.blade.php. --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#f3f6f4" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0b1220" media="(prefers-color-scheme: dark)">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Aquilala">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/apple-touch-icon.png') }}">
    <script src="{{ asset('js/pwa-register.js') }}" defer></script>
    <script src="{{ asset('js/net-status.js') }}" defer></script>
    {{-- Same global app lockdown as the app shell (selection, copy, long-press
         menus, printing — forms stay fully editable) so login/register behave
         identically. ?v= bump on edit: sw.js caches .css/.js cache-first. --}}
    <link rel="stylesheet" href="{{ asset('css/app-lockdown.css') }}?v=4">
    <script src="{{ asset('js/app-lockdown.js') }}?v=4" defer></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html { scrollbar-width: none; -ms-overflow-style: none; }
        html::-webkit-scrollbar { width: 0; height: 0; display: none; }
        body { 
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; 
            background: #f3f6f4; 
            color: #2c3530; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Global: consistent, readable letter-spacing on all validation / feedback text */
        .field-error, .field-feedback, .form-error, .form-error-message,
        .form-errors, .form-errors li, .form-errors ul, .alert-error, .alert-error li,
        [id$="-feedback"], [id$="-error"], [id$="Feedback"], [id$="Error"] {
            letter-spacing: 0.05em;
        }

        /* Registration page: long form, allow natural scrolling instead of
           flex-centering which could clip the top of tall content. */
        body.auth-body--tall {
            align-items: flex-start;
            overflow-y: auto;
            padding: 32px 0;
        }

        /* Remove browser-default focus outline; styled borders handle focus state */
        input:focus, input:focus-visible, input:focus-within,
        select:focus, select:focus-visible,
        textarea:focus, textarea:focus-visible {
            outline: none !important;
        }

        /* Field border weight — match the app's 1.5px fields (the calm,
           substantial weight of the harvest-report forms); 1px read as
           thin and sharp. The login phone wrapper matches separately. */
        input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="hidden"]),
        select, textarea {
            border-width: 1.5px !important;
        }

        /* At-rest field border colour — fields that never declare one would
           fall back to the browser's dark gray default. Element-level, so
           every page's own field styling still wins. */
        input:not([type="checkbox"]):not([type="radio"]):not([type="range"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="hidden"]),
        textarea {
            border-color: #e2e8f0;
        }

        /* Global select/dropdown styling — white field, rounded corners, custom arrow,
           and a forced light dropdown popup (never an OS-dark "black box") */
        select {
            color-scheme: light;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.5rem 2.5rem 0.5rem 0.75rem;
            background-color: #ffffff;
            outline: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23143423' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            font-family: inherit;
        }
        select:not([disabled]) { cursor: pointer; }
        select:focus {
            border-color: #4A7A54;
            box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
        }

        /* Responsive Wrapper Container */
        .auth-container {
            width: 100%;
            max-width: 540px;
            margin: 24px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            display: grid;
            grid-template-columns: 1fr;
            transition: max-width 0.3s ease;
        }

        /* Left Hand Form Panel */
        .form-pane {
            padding: 32px;
        }

        /* Top Brand Header Component */
        .form-brand-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 28px;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 20px;
        }
        .form-brand-logo {
            width: 56px; /* Slightly adjusted to accommodate img aspect ratio nicely */
            height: 56px;
            margin-bottom: 10px;
            object-fit: contain;
        }
        .form-brand-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #143d23;
        }
        /* "lala" — a vivid brand green instead of the old muted sage
           (#8FB89A), which read as grey next to the dark "Aqui". Bright
           enough to notice, dark enough to stay AA-legible on white. */
        .form-brand-name span {
            color: #16a34a;
        }
        .form-brand-sub {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #606d64;
            margin-top: 2px;
        }

        /* Right Hand Welcome Panel - Hidden on Mobile */
        .visual-pane {
            display: none;
            position: relative;
            overflow: hidden;
            padding: 48px;
            color: #ffffff;
            flex-direction: column;
            justify-content: center;
        }

        /* Crossfading background slides */
        .visual-pane__slides {
            position: absolute;
            inset: 0;
            z-index: 0;
        }
        .visual-pane__slide {
            position: absolute;
            inset: 0;
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            opacity: 0;
            transition: opacity 2s ease-in-out;
            will-change: opacity;
        }
        .visual-pane__slide.is-active { opacity: 1; }

        /* Keep the welcome copy above the slides */
        .visual-pane__content {
            position: relative;
            z-index: 1;
        }

        .visual-pane h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: 0.08em;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        /* "lala" over the photo cover on wide screens: bright lime with a
           glow so it never disappears into the green-tinted image overlay
           (the old #8FB89A blended straight into it). */
        .visual-pane h2 span {
            color: #b9f44a;
            text-shadow: 0 0 16px rgba(185, 244, 74, 0.55), 0 2px 6px rgba(0, 0, 0, 0.45);
        }
        .visual-pane p {
            color: #e2ebe5;
            font-size: 15px;
            line-height: 1.6;
            letter-spacing: 0.03em;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* Media Query for Desktop Viewports */
        @media (min-width: 992px) {
            .auth-container {
                max-width: 1024px;
                grid-template-columns: 1.1fr 0.9fr;
                min-height: 600px;
            }
            .visual-pane {
                display: flex;
            }
            .form-pane {
                padding: 48px;
            }
            /* Registration page: no side image, give the form full width */
            .auth-container--wide {
                grid-template-columns: 1fr;
                max-width: 720px;
                min-height: 0;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="@if(isset($hideVisualPane) && $hideVisualPane) auth-body--tall @endif"@if($captureShield ?? false) data-capture-shield @endif>

    <div class="auth-container @if(isset($hideVisualPane) && $hideVisualPane) auth-container--wide @endif">
        <div class="form-pane">
            
            <div class="form-brand-header">
                <img src="{{ asset('images/logo.png') }}" class="form-brand-logo" alt="Aquilala Logo">
                <div class="form-brand-name">Aqui<span>lala</span></div>
                <div class="form-brand-sub">Irrigators Association Inc.</div>
            </div>

            @yield('form-content')
        </div>

        {{-- Right Hand Welcome Panel - Hidden on Mobile --}}
        {{-- The register page sets $hideVisualPane = true to give the
             form more room and avoid a long side image. --}}
        @unless(isset($hideVisualPane) && $hideVisualPane)
        @php
            $loginSlides = [
                'images/login-images/login-img-1.png',
                'images/login-images/login-img-2.jpg',
                'images/login-images/login-img-3.jpg',
                'images/login-images/login-img-4.jpg',
                'images/login-images/login-img-5.jpg',
                'images/login-images/login-img-6.jpg',
            ];
        @endphp
        <div class="visual-pane">
            <div class="visual-pane__slides" aria-hidden="true">
                @foreach($loginSlides as $i => $slide)
                    <div class="visual-pane__slide {{ $i === 0 ? 'is-active' : '' }}"
                         style="background-image: linear-gradient(135deg, rgba(51, 118, 20, 0.72), rgba(20, 61, 35, 0.68)), url('{{ asset($slide) }}');"></div>
                @endforeach
            </div>
            <div class="visual-pane__content">
                <h2>Aqui<span>lala</span></h2>
                <p>Irrigators Association Incorporated</p>
                <div style="margin-top: 32px; height: 2px; width: 40px; background: #348110; border-radius: 2px; box-shadow: 0 1px 3px rgba(0,0,0,0.3);"></div>
                <p style="margin-top: 24px; font-size: 14px; color: #ffffff; font-weight: 600; text-shadow: 0 1px 4px rgba(0,0,0,0.4);">
                    Sign in to use the association's farm services and information.
                </p>
            </div>
        </div>
        @endunless
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var slides = document.querySelectorAll('.visual-pane__slide');
            if (slides.length < 2) return;

            var current = 0;
            var transitionMs = 2000; // must match the CSS transition duration

            setInterval(function () {
                var prev = current;
                current = (current + 1) % slides.length;

                // Bring the incoming slide on top, then fade it in over the current one
                slides[current].style.zIndex = 2;
                slides[current].classList.add('is-active');

                // Once the crossfade completes, retire the previous slide
                setTimeout(function () {
                    slides[prev].classList.remove('is-active');
                    slides[prev].style.zIndex = '';
                    slides[current].style.zIndex = '';
                }, transitionMs);
            }, 6000);
        });
    </script>

    @yield('scripts')
</body>
</html>