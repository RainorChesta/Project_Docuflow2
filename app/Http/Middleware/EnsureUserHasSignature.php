<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (app()->environment('testing') && !$request->headers->has('X-Test-Enforce-Signature')) {
            return $next($request);
        }

        if ($user && !$user->hasSignature()) {
            // Exempt routes that must be accessible even without signature
            $exemptRoutes = [
                'profile.edit',
                'profile.update',
                'profile.destroy',
                'profile.signature.show',
                'profile.signature.store',
                'profile.signature.destroy',
                'logout',
            ];

            $currentRouteName = $request->route() ? $request->route()->getName() : null;

            if ($currentRouteName && !in_array($currentRouteName, $exemptRoutes)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'MandatorySignatureRequired',
                        'message' => 'Anda wajib membuat tanda tangan digital (TTD) terlebih dahulu di halaman profil.',
                        'redirect_url' => route('profile.edit', ['must_sign' => 1]),
                    ], 403);
                }

                return redirect()->route('profile.edit', ['must_sign' => 1])
                    ->with('warning', 'Wajib Membuat TTD: Anda harus menggambar dan menyimpan Tanda Tangan Digital (TTD) pada profil sebelum dapat mengakses fitur sistem lainnya.');
            }
        }

        return $next($request);
    }
}
