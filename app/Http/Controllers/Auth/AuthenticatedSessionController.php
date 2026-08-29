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
        $contextService = app(\App\Services\CompanyContextService::class);
        $activeBranchId = $contextService->getActiveBranchId($user);
        $activeCompanyId = $contextService->getActiveCompanyId($user);
        $userBranchIds = $user->allBranchIds();
        $userCompanyIds = $user->allCompanyIds();

        $baseDocQuery = $user->documents();
        if ($activeBranchId) {
            $baseDocQuery->where('branch_id', $activeBranchId);
        } elseif ($activeCompanyId) {
            $baseDocQuery->where(function ($q) use ($activeCompanyId) {
                $q->where('company_id', $activeCompanyId)
                  ->orWhereHas('branch', fn($b) => $b->where('company_id', $activeCompanyId));
            });
        } elseif (!empty($userBranchIds)) {
            $baseDocQuery->where(function ($q) use ($userBranchIds, $userCompanyIds) {
                $q->whereIn('branch_id', $userBranchIds)
                  ->orWhere(function ($sub) use ($userCompanyIds) {
                      $sub->whereNull('branch_id')
                          ->whereIn('company_id', $userCompanyIds);
                  });
            });
        } elseif (!empty($userCompanyIds)) {
            $baseDocQuery->whereIn('company_id', $userCompanyIds);
        }

        $expiringCount = $baseDocQuery
            ->where('is_expired', false)
            ->whereHas('currentVersion', fn($q) => $q->where('status', 'active'))
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
