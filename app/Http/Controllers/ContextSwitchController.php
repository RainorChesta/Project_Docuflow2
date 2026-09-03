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
    public function switch(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'company_id' => 'required',
            'branch_id' => 'nullable',
        ]);

        $companyId = (int) $validated['company_id'];
        $company = Company::find($companyId);

        if (!$company) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => __('Perusahaan tidak ditemukan.')], 404);
            }
            return back()->with('error', __('Perusahaan tidak ditemukan.'));
        }

        // Authorize company
        if (!$user->isAdmin() && !$user->companies()->where('companies.id', $companyId)->exists()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => __('Akses perusahaan tidak diizinkan.')], 403);
            }
            return back()->with('error', __('Akses perusahaan tidak diizinkan.'));
        }

        session(['active_company_id' => $companyId]);

        $branchId = $validated['branch_id'] ?? null;
        $activeBranchId = null;

        if (!empty($branchId) && is_numeric($branchId)) {
            $branch = Branch::where('id', (int) $branchId)->where('company_id', $companyId)->first();
            if ($branch && ($user->isAdmin() || $user->branches()->where('branches.id', $branch->id)->exists())) {
                session(['active_branch_id' => $branch->id]);
                $activeBranchId = $branch->id;
            } else {
                $defaultBranch = $this->contextService->getAvailableBranches($user, $companyId)->first();
                if ($defaultBranch) {
                    session(['active_branch_id' => $defaultBranch->id]);
                    $activeBranchId = $defaultBranch->id;
                } else {
                    session()->forget('active_branch_id');
                }
            }
        } else {
            // Auto select default branch for that company
            $defaultBranch = $this->contextService->getAvailableBranches($user, $companyId)->first();
            if ($defaultBranch) {
                session(['active_branch_id' => $defaultBranch->id]);
                $activeBranchId = $defaultBranch->id;
            } else {
                session()->forget('active_branch_id');
            }
        }

        // Division Logic
        $divisionId = $request->input('division_id');
        if ($activeBranchId) {
            $availableDivisions = $this->contextService->getAvailableDivisions($user, $activeBranchId);
            
            if ($availableDivisions->isEmpty()) {
                session()->forget('active_division_id');
            } elseif ($availableDivisions->count() === 1) {
                // Auto-select if only 1 division exists
                session(['active_division_id' => $availableDivisions->first()->id]);
            } else {
                // More than 1 division exists
                if (!empty($divisionId) && is_numeric($divisionId)) {
                    $division = $availableDivisions->firstWhere('id', (int) $divisionId);
                    if ($division) {
                        session(['active_division_id' => $division->id]);
                    } else {
                        // Fallback to first if invalid
                        session(['active_division_id' => $availableDivisions->first()->id]);
                    }
                } else {
                    // Check if current session division is still valid in this branch
                    $currentDivisionId = session('active_division_id');
                    if ($currentDivisionId && $availableDivisions->firstWhere('id', $currentDivisionId)) {
                        // keep it
                    } else {
                        session(['active_division_id' => $availableDivisions->first()->id]);
                    }
                }
            }
        } else {
            session()->forget('active_division_id');
        }

        session()->save();

        // If switching while viewing/editing a document not belonging to the new context,
        // redirect to documents index instead of throwing a 403 error.
        $referer = $request->headers->get('referer');
        $destination = null;

        if ($referer) {
            $refererPath = parse_url($referer, PHP_URL_PATH) ?? '';
            if (preg_match('#/documents/(\d+)(/edit|/preview)?#', $refererPath, $matches)) {
                $docId = (int) $matches[1];
                $doc = \App\Models\Document::find($docId);
                if ($doc) {
                    $activeBranchId = session('active_branch_id');
                    $activeCompanyId = session('active_company_id');
                    $docInScope = false;

                    if ($user->isAdmin() || $user->isDirector()) {
                        $docInScope = true;
                    } elseif ($activeBranchId && (int) $doc->branch_id === (int) $activeBranchId) {
                        $docInScope = true;
                    } elseif (!$doc->branch_id && $activeCompanyId && (int) $doc->company_id === (int) $activeCompanyId) {
                        $docInScope = true;
                    } elseif ($activeBranchId && $doc->distributions()->where('target_branch_id', $activeBranchId)->exists()) {
                        $docInScope = true;
                    }

                    if ($docInScope) {
                        $destination = $referer;
                    } else {
                        $destination = route('documents.index');
                    }
                }
            }
        }

        if (!$destination) {
            $destination = $referer ?: route('dashboard');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Konteks perusahaan & cabang berhasil dialihkan.'),
                'active_company_id' => session('active_company_id'),
                'active_branch_id' => session('active_branch_id'),
                'redirect' => $destination,
            ]);
        }

        return redirect()->to($destination)->with('success', __('Konteks perusahaan & cabang berhasil dialihkan.'));
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
