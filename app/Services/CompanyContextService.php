<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
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
     */
    public function getActiveDivisionId(?User $user = null): ?int
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }

        $sessionDivisionId = session('active_division_id');
        
        if ($sessionDivisionId) {
            $division = Division::find($sessionDivisionId);
            if ($division) {
                if ($user->isAdmin() || in_array($sessionDivisionId, $user->allDivisionIds())) {
                    return (int) $sessionDivisionId;
                }
            }
        }

        $division = $this->getDefaultDivision($user);
        if ($division) {
            session(['active_division_id' => $division->id]);
            return $division->id;
        }

        return null;
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
     * Get all available divisions globally for user.
     */
    public function getAvailableDivisions(?User $user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return collect();
        }

        if ($user->isAdmin()) {
            return Division::orderBy('name')->get();
        }

        $divisionIds = $user->allDivisionIds();
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
