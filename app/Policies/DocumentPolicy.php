<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentShareService;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($user->isAdmin() || $user->isDirector()) return true;
        
        // Owner can always view their own documents, bypassing active branch isolation.
        // This is necessary so they don't get locked out right after creating a document in another branch.
        if ($user->id === $document->owner_id) return true;

        $contextService = app(\App\Services\CompanyContextService::class);
        $activeBranchId = $contextService->getActiveBranchId($user);
        $activeCompanyId = $contextService->getActiveCompanyId($user);

        // Enforce active branch and company isolation:
        // When a user selects a company or branch, only documents matching that active context can be accessed.
        if ($activeBranchId) {
            if ($document->branch_id) {
                if ((int)$document->branch_id !== (int)$activeBranchId) {
                    return false;
                }
            } elseif ($document->company_id) {
                if ((int)$document->company_id !== (int)$activeCompanyId) {
                    return false;
                }
            } else {
                $userBranchIds = $user->allBranchIds();
                $userCompanyIds = $user->allCompanyIds();
                if (!empty($userBranchIds) || !empty($userCompanyIds)) {
                    return false;
                }
            }
        } elseif ($activeCompanyId) {
            $docCompanyId = $document->company_id ?? $document->branch?->company_id;
            if ($docCompanyId) {
                if ((int)$docCompanyId !== (int)$activeCompanyId) {
                    return false;
                }
            } else {
                $userBranchIds = $user->allBranchIds();
                $userCompanyIds = $user->allCompanyIds();
                if (!empty($userBranchIds) || !empty($userCompanyIds)) {
                    return false;
                }
            }
        } else {
            // Check company & branch restriction across all assigned (e.g. testing / fallback)
            $userBranchIds = $user->allBranchIds();
            $userCompanyIds = $user->allCompanyIds();

            if ($document->branch_id && !empty($userBranchIds)) {
                if (!in_array($document->branch_id, $userBranchIds, true)) {
                    return false;
                }
            }

            if ($document->company_id && !empty($userCompanyIds)) {
                if (!in_array($document->company_id, $userCompanyIds, true)) {
                    return false;
                }
            }
        }

        if ($document->isGeneral()) return true;

        // Division-scoped docs: visible to members of that division.
        if ($document->isDivision()
            && $document->division_id
            && in_array($document->division_id, $user->allDivisionIds(), true)) {
            return true;
        }

        // Any share (personal, division, or link) grants view.
        return app(DocumentShareService::class)->resolveEffectiveRole($document, $user) !== null;
    }

    public function create(User $user): bool
    {
        // Any active user (including Director) may create documents; personal/general docs
        // do not require a division.
        return (bool) ($user->is_active ?? true);
    }

    public function update(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;
        if ($user->id === $document->owner_id || $user->isAdmin() || $user->isDirector()) return true;

        $role = app(DocumentShareService::class)->resolveEffectiveRole($document, $user);

        return in_array($role, ['owner', 'editor'], true);
    }

    public function manageAccess(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;
        return $user->id === $document->owner_id || $user->isAdmin() || $user->isDirector();
    }

    public function approve(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;
        if ($user->isAdmin() || $user->isDirector()) return true;
        return $user->isHead() && $user->division_id === $document->division_id;
    }

    public function delete(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;
        if ($user->isAdmin() || $user->isDirector()) return true;

        // Owner boleh hapus dokumen selama belum punya versi approved (active).
        return $user->id === $document->owner_id
            && !$document->versions()->where('status', 'active')->exists();
    }
}
