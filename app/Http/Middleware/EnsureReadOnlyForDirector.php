<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReadOnlyForDirector
{
    /**
     * Handle an incoming request.
     * Restrict Director from performing write operations (POST, PUT, PATCH, DELETE).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDirector()) {
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                // Allow switching context or changing password/language
                if ($request->routeIs('context.switch') || $request->routeIs('language.switch') || $request->routeIs('logout') || $request->routeIs('profile.update')) {
                    return $next($request);
                }

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Director role has read-only access.'], 403);
                }

                return back()->with('error', 'Role Direktur hanya memiliki hak akses lihat data (read-only).');
            }
        }

        return $next($request);
    }
}
