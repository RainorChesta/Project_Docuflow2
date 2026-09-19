<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
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
     * Switch active company, branch, and unit kerja context.
     */
    public function switch(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'company_id' => 'required',
            'branch_id' => 'nullable',
            'unit_kerja_id' => 'nullable',
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
            $defaultBranch = $this->contextService->getAvailableBranches($user, $companyId)->first();
            if ($defaultBranch) {
                session(['active_branch_id' => $defaultBranch->id]);
                $activeBranchId = $defaultBranch->id;
            } else {
                session()->forget('active_branch_id');
            }
        }

        // Unit Kerja Context Logic
        $activeBranch = $activeBranchId ? Branch::find($activeBranchId) : null;
        $unitKerjaId = $request->input('unit_kerja_id');
        $availableUnitKerjas = $this->contextService->getAvailableUnitKerjas($user, $activeBranch?->id);

        if ($availableUnitKerjas->isEmpty()) {
            session()->forget('active_unit_kerja_id');
        } elseif ($availableUnitKerjas->count() === 1) {
            session(['active_unit_kerja_id' => $availableUnitKerjas->first()->id]);
        } else {
            if (!empty($unitKerjaId) && is_numeric($unitKerjaId)) {
                $uk = $availableUnitKerjas->firstWhere('id', (int) $unitKerjaId);
                if ($uk) {
                    session(['active_unit_kerja_id' => $uk->id]);
                } else {
                    session(['active_unit_kerja_id' => $availableUnitKerjas->first()->id]);
                }
            } else {
                $currentUkId = session('active_unit_kerja_id');
                if ($currentUkId && $availableUnitKerjas->firstWhere('id', $currentUkId)) {
                    // keep it
                } else {
                    session(['active_unit_kerja_id' => $availableUnitKerjas->first()->id]);
                }
            }
        }

        session()->forget('active_division_id');
        session()->save();

        // Redirect logic
        $referer = $request->headers->get('referer');
        $destination = null;

        if ($referer) {
            $refererPath = parse_url($referer, PHP_URL_PATH) ?? '';
            if (preg_match('#/documents/(\d+)(/edit|/preview)?#', $refererPath, $matches)) {
                $docId = (int) $matches[1];
                $doc = Document::find($docId);
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
                'message' => __('Konteks perusahaan, cabang & unit kerja berhasil dialihkan.'),
                'active_company_id' => session('active_company_id'),
                'active_branch_id' => session('active_branch_id'),
                'active_unit_kerja_id' => session('active_unit_kerja_id'),
                'redirect' => $destination,
            ]);
        }

        return redirect()->to($destination)->with('success', __('Konteks perusahaan, cabang & unit kerja berhasil dialihkan.'));
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
