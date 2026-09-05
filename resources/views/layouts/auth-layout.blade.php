<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Aquilala</title>
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
        .form-brand-name span {
            color: #5DC257;
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
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            padding: 48px;
            color: #ffffff;
            flex-direction: column;
            justify-content: center;
        }

        .visual-pane h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: -0.025em;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .visual-pane h2 span { color: #6aeb2f; }
        .visual-pane p {
            color: #e2ebe5;
            font-size: 15px;
            line-height: 1.6;
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
</head>
<body class="@if(isset($hideVisualPane) && $hideVisualPane) auth-body--tall @endif">

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
        <div class="visual-pane" data-bg="{{ asset('images/login-img.png') }}">
            <div>
                <h2>Aqui<span>lala</span></h2>
                <p>Irrigators Association Incorporated</p>
                <div style="margin-top: 32px; height: 2px; width: 40px; background: #348110; border-radius: 2px; box-shadow: 0 1px 3px rgba(0,0,0,0.3);"></div>
                <p style="margin-top: 24px; font-size: 14px; color: #ffffff; font-weight: 600; text-shadow: 0 1px 4px rgba(0,0,0,0.4);">
                    Access recommended farm inputs, compare curated store offerings, and plan your costs with guided decision support.
                </p>
            </div>
        </div>
        @endunless
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var pane = document.querySelector('.visual-pane');
            if (pane && pane.dataset.bg) {
                pane.style.backgroundImage = 'linear-gradient(135deg, rgba(51, 118, 20, 0.62), rgba(20, 61, 35, 0.55)), url("' + pane.dataset.bg + '")';
            }
        });
    </script>

    @yield('scripts')
</body>
</html>