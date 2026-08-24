<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Services\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContextSwitchController extends Controller
{
    public function __construct(
        protected CompanyContextService $contextService
    ) {}

    /**
     * Switch active company and/or branch.
     */
    public function switch(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $companyId = (int) $validated['company_id'];
        
        // Authorize company
        if (!$user->isAdmin() && !$user->companies()->where('companies.id', $companyId)->exists()) {
            return back()->with('error', 'Unauthorized company access.');
        }

        session(['active_company_id' => $companyId]);

        $branchId = $validated['branch_id'] ?? null;
        if ($branchId) {
            $branch = Branch::where('id', $branchId)->where('company_id', $companyId)->first();
            if ($branch) {
                if ($user->isAdmin() || $user->branches()->where('branches.id', $branch->id)->exists()) {
                    session(['active_branch_id' => $branch->id]);
                }
            }
        } else {
            // Auto select default branch for that company
            $defaultBranch = $this->contextService->getAvailableBranches($user, $companyId)->first();
            if ($defaultBranch) {
                session(['active_branch_id' => $defaultBranch->id]);
            } else {
                session()->forget('active_branch_id');
            }
        }

        return back()->with('success', 'Context switched.');
    }

    /**
     * Get branches for a specific company (for dynamic dropdowns).
     */
    public function branchesForCompany(Request $request, Company $company): JsonResponse
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->companies()->where('companies.id', $company->id)->exists()) {
            return response()->json([], 403);
        }

        $branches = $this->contextService->getAvailableBranches($user, $company->id);

        return response()->json($branches);
    }
}
