<?php

use App\Http\Middleware\CaptureShield;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->trustHosts(at: [
            '.loca.lt',
        ]);

        // Login must not hard-fail on CSRF: on flaky mobile/ngrok connections the
        // browser replays the submit with an empty or already-rotated token, which
        // previously caused endless 419 "Page Expired" errors. Everything else
        // (logout, forms, etc.) stays fully protected.
        $middleware->validateCsrfTokens(except: [
            'login',
        ]);

        // Per-role route protection: Route::middleware('role:admin')
        $middleware->alias([
            'role' => RoleMiddleware::class,
            // Redirects members with a temporary password to the change-
            // password page until they set a permanent one.
            'password.setup' => EnsurePasswordChanged::class,
            // Per-page capture shield: ->middleware('shield') on a route opts
            // that ONE page in (display-capture header + app-switcher cover).
            // Remove the token from the route to opt it back out.
            'shield' => CaptureShield::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Any other form that gets re-submitted after the CSRF token rotated
        // (e.g. a flaky mobile network replay) falls back to a friendly redirect
        // with a message instead of the bare 419 page. AJAX/JSON keeps default
        // handling so existing JS session-expiry prompts still work.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return redirect()->back()
                ->withInput($request->except('password'))
                ->with('error', 'Your session expired. Please try again.');
        });
    })->create();
