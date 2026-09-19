<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountVerificationController extends Controller
{
    /**
     * Display the locked / pending verification screen.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->isVerified()) {
            return redirect()->route('dashboard');
        }

        $statusData = $this->getVerificationPayload($user);

        return view('auth.verification-pending', array_merge([
            'user' => $user,
        ], $statusData));
    }

    /**
     * Get live verification status as JSON for real-time polling / WebSocket response.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $statusData = $this->getVerificationPayload($user);

        return response()->json(array_merge($statusData, [
            'redirect_url' => $statusData['is_verified'] ? route('dashboard') : null,
        ]));
    }

    /**
     * Build the structured verification status payload for a user.
     */
    private function getVerificationPayload($user): array
    {
        if (!$user) {
            return [
                'is_verified' => false,
                'has_unit_kerja' => false,
                'unit_kerja_names' => [],
                'has_division' => false,
                'division_names' => [],
                'has_company' => false,
                'company_names' => [],
                'has_branch' => false,
                'branch_names' => [],
                'has_company_and_branch' => false,
            ];
        }

        $user->load(['unitKerjas', 'companies', 'branches']);

        $hasUnitKerja = $user->unitKerjas->isNotEmpty();
        $hasCompany = $user->companies->isNotEmpty();
        $hasBranch = $user->branches->isNotEmpty();
        $isVerified = $user->isVerified();

        $unitKerjaNames = $user->unitKerjas->pluck('name')->all();
        $companyNames = $user->companies->pluck('name')->all();
        $branchNames = $user->branches->pluck('name')->all();

        return [
            'is_verified' => $isVerified,
            'isVerified' => $isVerified,
            'has_unit_kerja' => $hasUnitKerja,
            'hasUnitKerja' => $hasUnitKerja,
            'unit_kerja_names' => $unitKerjaNames,
            'unitKerjaNames' => $unitKerjaNames,
            'has_division' => $hasUnitKerja,
            'hasDivision' => $hasUnitKerja,
            'division_names' => $unitKerjaNames,
            'divisionNames' => $unitKerjaNames,
            'has_company' => $hasCompany,
            'hasCompany' => $hasCompany,
            'company_names' => $companyNames,
            'companyNames' => $companyNames,
            'has_branch' => $hasBranch,
            'hasBranch' => $hasBranch,
            'branch_names' => $branchNames,
            'branchNames' => $branchNames,
            'has_company_and_branch' => ($hasCompany && $hasBranch),
            'hasCompanyAndBranch' => ($hasCompany && $hasBranch),
        ];
    }
}
