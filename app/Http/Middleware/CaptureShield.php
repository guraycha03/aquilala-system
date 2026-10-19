<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-page capture shield — ONE word to opt a page in or out.
 *
 * Usage on any route in routes/web.php (add it, remove it, no other edits
 * anywhere are needed):
 *
 *      Route::get('/users', [AdminUserController::class, 'index'])
 *          ->name('users.index')
 *          ->middleware('shield');
 *
 * What that single call buys for that one page:
 *
 *  1. Response header `Permissions-Policy: display-capture=()` — blocks the
 *     *in-browser* screen-capture API (navigator.mediaDevices.getDisplayMedia,
 *     i.e. tab-recording tools that try to grab this page). Note: like every
 *     response header it is honoured on full page loads (first visit,
 *     refresh, PWA cold start). Turbo Drive visits are same-document swaps,
 *     so the JS half below is what covers those.
 *
 *  2. Shares $captureShield with the views — the layouts print
 *     data-capture-shield on <body>, which is the flag js/app-lockdown.js
 *     reads to raise the blank branded cover while the page is hidden or
 *     the window is unfocused (OS app-switcher / alt-tab snapshots), and to
 *     engage the best-effort PrintScreen clipboard wipe.
 *
 * The response header only covers this response — never a redirect's, never
 * another page's — so leaving the middleware off anywhere means that page
 * is completely untouched.
 *
 * HONEST LIMIT: this cannot stop an OS screenshot taken while the page is
 * on screen (PrtScn, Win+Shift+S, iOS side+volume, a second phone), nor
 * iOS screenshots ever. Those need a native Android wrapper (FLAG_SECURE);
 * everything here is friction, not a security boundary. The real boundary
 * stays auth + role authorization + audit logging.
 *
 * @see public/js/app-lockdown.js            client half (cover + clipboard)
 * @see public/css/app-lockdown.css          cover styles (bump ?v= when edited)
 */
class CaptureShield
{
    /**
     * Route middleware alias: 'shield'
     * (registered in bootstrap/app.php)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // BEFORE the response is rendered so every view in this request —
        // the layout included — can read it.
        view()->share('captureShield', true);

        $response = $next($request);

        // Keep any Permissions-Policy a future global middleware may send:
        // directives merge, last-writer-wins per feature would be wrong.
        $existing = $response->headers->get('Permissions-Policy');
        $response->headers->set(
            'Permissions-Policy',
            $existing ? $existing.', display-capture=()' : 'display-capture=()'
        );

        return $response;
    }
}
