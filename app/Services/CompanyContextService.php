<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\UnitKerja;
use App\Models\User;

class CompanyContextService
{
    /**
     * Get active company ID from session, or resolve default.
     */
    public function getActiveCompanyId(?User $user = null): ?int
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }

        $sessionCompanyId = session('active_company_id');
        if ($sessionCompanyId) {
            // Verify access
            if ($user->isAdmin() || $user->companies()->where('companies.id', $sessionCompanyId)->exists()) {
                return (int) $sessionCompanyId;
            }
        }

        // Default: first assigned company or first company in DB if admin
        $company = $this->getDefaultCompany($user);
        if ($company) {
            session(['active_company_id' => $company->id]);
            return $company->id;
        }

        return null;
    }

    /**
     * Get active branch ID from session, or resolve default.
     */
    public function getActiveBranchId(?User $user = null): ?int
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }

        $companyId = $this->getActiveCompanyId($user);
        $sessionBranchId = session('active_branch_id');

        if ($sessionBranchId) {
            $branch = Branch::find($sessionBranchId);
            if ($branch && (!$companyId || $branch->company_id === $companyId)) {
                if ($user->isAdmin() || $user->branches()->where('branches.id', $sessionBranchId)->exists()) {
                    return (int) $sessionBranchId;
                }
            }
        }

        $branch = $this->getDefaultBranch($user, $companyId);
        if ($branch) {
            session(['active_branch_id' => $branch->id]);
            return $branch->id;
        }

        return null;
    }

    /**
     * Get active unit kerja ID from session, or resolve default.
     * Operates consistently across both regular branches and head-office branches.
     */
    public function getActiveUnitKerjaId(?User $user = null): ?int
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }

        $branchId = $this->getActiveBranchId($user);
        $sessionUnitKerjaId = session('active_unit_kerja_id');
        $available = $this->getAvailableUnitKerjas($user, $branchId);

        if ($sessionUnitKerjaId) {
            $uk = $available->firstWhere('id', (int) $sessionUnitKerjaId);
            if ($uk) {
                return (int) $sessionUnitKerjaId;
            }
        }

        $defaultUk = $available->first();
        if ($defaultUk) {
            session(['active_unit_kerja_id' => $defaultUk->id]);
            return $defaultUk->id;
        }

        return null;
    }

    /**
     * Get active unit kerja model from session, or resolve default.
     */
    public function getActiveUnitKerja(?User $user = null): ?UnitKerja
    {
        $unitKerjaId = $this->getActiveUnitKerjaId($user);
        return $unitKerjaId ? UnitKerja::find($unitKerjaId) : null;
    }

    /**
     * Get available unit kerjas under branch/context for user.
     */
    public function getAvailableUnitKerjas(?User $user = null, ?int $branchId = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return collect();
        }

        $branchId = $branchId ?? $this->getActiveBranchId($user);

        if ($user->isAdmin() || $user->isDirector()) {
            return UnitKerja::orderBy('kode_unit_kerja')->get();
        }

        $allUkIds = $user->allUnitKerjaIds($branchId);
        if (empty($allUkIds)) {
            return collect();
        }

        return UnitKerja::whereIn('id', $allUkIds)
            ->orderBy('kode_unit_kerja')
            ->get();
    }

    /**
     * Get available companies for user.
     */
    public function getAvailableCompanies(?User $user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return collect();
        }

        if ($user->isAdmin()) {
            return Company::orderBy('name')->get();
        }

        return $user->companies()->orderBy('name')->get();
    }

    /**
     * Get available branches under active company for user.
     */
    public function getAvailableBranches(?User $user = null, ?int $companyId = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return collect();
        }

        $companyId = $companyId ?? $this->getActiveCompanyId($user);
        if (!$companyId) {
            return collect();
        }

        if ($user->isAdmin()) {
            return Branch::where('company_id', $companyId)->orderBy('is_pusat', 'desc')->orderBy('name')->get();
        }

        return $user->branches()->where('company_id', $companyId)->orderBy('is_pusat', 'desc')->orderBy('name')->get();
    }

    private function getDefaultCompany(User $user): ?Company
    {
        if ($user->isAdmin()) {
            return Company::orderBy('name')->first();
        }

        return $user->companies()->orderBy('name')->first();
    }

    private function getDefaultBranch(User $user, ?int $companyId): ?Branch
    {
        if (!$companyId) {
            return null;
        }

        if ($user->isAdmin()) {
            return Branch::where('company_id', $companyId)->orderBy('is_pusat', 'desc')->orderBy('name')->first();
        }

        return $user->branches()->where('company_id', $companyId)->orderBy('is_pusat', 'desc')->orderBy('name')->first();
    }
}
