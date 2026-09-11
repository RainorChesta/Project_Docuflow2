<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        if (app()->environment('testing') && !$request->headers->has('X-Test-Enforce-Verification') && !session('test_enforce_verification')) {
            return $next($request);
        }

        if (!$user->isVerified()) {
            // Whitelisted routes for unverified users
            $exemptRoutes = [
                'verification.pending',
                'verification.status',
                'logout',
                'language.switch',
            ];

            $currentRouteName = $request->route() ? $request->route()->getName() : null;

            if ($currentRouteName && in_array($currentRouteName, $exemptRoutes)) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'AccountPendingVerification',
                    'message' => __('Akun Anda sedang menunggu verifikasi oleh administrator sistem.'),
                    'redirect_url' => route('verification.pending'),
                ], 403);
            }

            return redirect()->route('verification.pending');
        }

        // If user is already verified and accesses the pending notice, redirect to dashboard
        if ($request->routeIs('verification.pending')) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
