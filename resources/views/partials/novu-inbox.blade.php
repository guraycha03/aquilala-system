{{--
    Novu Inbox — the in-app notification feed, mounted beside the existing
    notification bell. Renders only when Novu is configured and an account is
    signed in; every value is server-supplied so no key reaches the page.

    Subscribers are our own user ids. When an HMAC secret is configured the
    hash proves identity server-side on every load; without it the raw id is
    accepted (HMAC off) and this block simply skips the hash.
--}}
@php
    $novuSubId = (string) auth()->id();
    $novuJs    = '3.19.2';
    // The CDN URLs are built here because the '@' in '@novu/js@3.19.2' sits
    // directly before where '{{' would go — Blade reads '@{{' as an escaped
    // echo and prints the braces verbatim. ('@novu/js@' + version + rest.)
    $novuCssUrl = 'https://unpkg.com/@novu/js@' . $novuJs . '/dist/index.css';
    $novuJsUrl  = 'https://esm.sh/@novu/js@' . $novuJs . '/ui?bundle';
@endphp
<link rel="stylesheet" href="{{ $novuCssUrl }}">
<div id="novu-inbox" class="novu-inbox-mount"></div>
<script type="module">
    import { NovuUI } from '{{ $novuJsUrl }}';

    const el = document.getElementById('novu-inbox');
    if (el && !el.dataset.novuMounted) {
        el.dataset.novuMounted = '1';

        const novu = new NovuUI({
            options: {
                applicationIdentifier: @json((string) config('novu.app_id')),
                subscriberId: @json($novuSubId),
                @if($novuHash = \App\Services\NovuService::subscriberHash($novuSubId))
                    subscriberHash: @json($novuHash),
                @endif
            },
            appearance: {
                variables: {
                    colorPrimary: '#648268',
                    colorPrimaryForeground: '#ffffff',
                    colorBackground: '#ffffff',
                    colorForeground: '#2f3d31',
                    colorSecondary: '#eef1ea',
                    colorSecondaryForeground: '#2f3d31',
                    colorCounter: '#648268',
                    colorCounterForeground: '#ffffff',
                    colorNeutral: '#dfe4da',
                    colorRing: '#8ba383',
                    borderRadius: '10px'
                }
            }
        });

        novu.mountComponent({
            name: 'Inbox',
            props: {
                // Feed items open the page they are about (payload.link),
                // through Turbo like any other in-app navigation.
                onNotificationClick: function (notification) {
                    const data = (notification && (notification.payload || notification.data)) || {};
                    const url = data.link || data.url || (notification && notification.link);
                    if (!url) return;
                    if (window.Turbo) window.Turbo.visit(url);
                    else window.location.href = url;
                }
            },
            element: el
        });

        // Turbo swaps the whole body on navigation — take the feed (and its
        // socket) down with the old page instead of leaking it behind the
        // cached snapshot.
        document.addEventListener('turbo:before-cache', function () {
            try { novu.unmountComponent(el); } catch (e) {}
        }, { once: true });
    }
</script>
