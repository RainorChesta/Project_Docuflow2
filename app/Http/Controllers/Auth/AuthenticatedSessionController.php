<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $expiringCount = \App\Models\Document::visibleTo($user)
            ->where('is_expired', false)
            ->get()
            ->filter(function ($doc) {
                if (!$doc->expires_at) return false;
                $days = now()->startOfDay()->diffInDays($doc->expires_at->startOfDay(), false);
                return $days >= 0 && $days <= 3;
            })->count();

        if ($expiringCount > 0) {
            $request->session()->flash('urgent_expiring_count', $expiringCount);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
