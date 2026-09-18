<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
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
     * Get active division ID from session, or resolve default.
     * Only relevant when active branch is Pusat (is_pusat = true).
     */
    public function getActiveDivisionId(?User $user = null): ?int
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }

        $branchId = $this->getActiveBranchId($user);
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch && !$branch->is_pusat) {
                return null;
            }
        }

        $availableDivisions = $this->getAvailableDivisions($user, $branchId);
        $sessionDivisionId = session('active_division_id');
        
        if ($sessionDivisionId) {
            $division = $availableDivisions->firstWhere('id', (int) $sessionDivisionId);
            if ($division) {
                return (int) $sessionDivisionId;
            }
        }

        $defaultDivision = $availableDivisions->first();
        if ($defaultDivision) {
            session(['active_division_id' => $defaultDivision->id]);
            return $defaultDivision->id;
        }

        return null;
    }

    /**
     * Get active division model from session, or resolve default.
     */
    public function getActiveDivision(?User $user = null): ?Division
    {
        $divisionId = $this->getActiveDivisionId($user);
        return $divisionId ? Division::find($divisionId) : null;
    }

    /**
     * Get active unit kerja ID from session, or resolve default.
     * Only relevant when active branch is Cabang PT (is_pusat = false).
     */
    public function getActiveUnitKerjaId(?User $user = null): ?int
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }

        $branchId = $this->getActiveBranchId($user);
        if (!$branchId) {
            return null;
        }

        $branch = Branch::find($branchId);
        if (!$branch || $branch->is_pusat) {
            return null;
        }

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
     * Get available unit kerjas under branch for user.
     */
    public function getAvailableUnitKerjas(?User $user = null, ?int $branchId = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return collect();
        }

        $branchId = $branchId ?? $this->getActiveBranchId($user);
        if (!$branchId) {
            return collect();
        }

        $branch = Branch::find($branchId);
        if (!$branch || $branch->is_pusat) {
            return collect();
        }

        if ($user->isAdmin() || $user->isDirector()) {
            return UnitKerja::orderBy('kode_unit_kerja')->get();
        }

        $allUkIds = $user->allUnitKerjaIds($branchId);
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

    /**
     * Get all available divisions globally or for specific Pusat branch for user.
     */
    public function getAvailableDivisions(?User $user = null, ?int $branchId = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return collect();
        }

        if ($branchId !== null) {
            $branch = Branch::find($branchId);
            if ($branch && !$branch->is_pusat) {
                return collect();
            }
        }

        if ($user->isAdmin()) {
            return Division::orderBy('name')->get();
        }

        $divisionIds = $user->allDivisionIds($branchId);
        return Division::whereIn('id', $divisionIds)->orderBy('name')->get();
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

    private function getDefaultDivision(User $user): ?Division
    {
        if ($user->isAdmin()) {
            return Division::orderBy('name')->first();
        }

        $divisionIds = $user->allDivisionIds();
        return Division::whereIn('id', $divisionIds)->orderBy('name')->first();
    }
}
