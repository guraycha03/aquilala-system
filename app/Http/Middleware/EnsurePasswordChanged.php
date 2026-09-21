<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Force members with a temporary password to set a permanent one before
     * they can use the rest of the application. Exempt routes are hard-coded
     * by route name so the flow itself never loops.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mustChangePassword()) {
            $route = $request->route()?->getName();

            if (! in_array($route, ['password.change', 'password.change.post', 'logout'], true)) {
                return redirect()->route('password.change');
            }
        }

        return $next($request);
    }
}