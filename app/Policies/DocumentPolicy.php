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

        // Signature Request Signer Exception:
        // Users who are assigned to sign this document are granted view access to inspect
        // the preview during signature approval, even across different companies or branches.
        // This grants VIEW access ONLY (update, delete, approve, and manageAccess remain restricted).
        if ($document->signatureRequests()->where('target_user_id', $user->id)->exists()) {
            return true;
        }

        // Owner always has access to their own documents
        if ($user->id === $document->owner_id) {
            return true;
        }

        // Explicit share access (user share, division share, or anyone with link)
        // allows access across different companies/branches
        if (app(DocumentShareService::class)->resolveEffectiveRole($document, $user) !== null) {
            return true;
        }

        $contextService = app(\App\Services\CompanyContextService::class);
        $activeBranchId = $contextService->getActiveBranchId($user);
        $activeCompanyId = $contextService->getActiveCompanyId($user);

        // 1. Strict Active Context Isolation
        // A document MUST belong to the active context (or be distributed to it) to be viewed.
        $inScope = false;

        if ($activeBranchId) {
            if ($document->branch_id && (int)$document->branch_id === (int)$activeBranchId) {
                $inScope = true;
            } elseif (!$document->branch_id && $document->company_id && (int)$document->company_id === (int)$activeCompanyId) {
                $inScope = true;
            } elseif ($document->distributions()->where('target_branch_id', $activeBranchId)->exists()) {
                $inScope = true;
            }
        } elseif ($activeCompanyId) {
            $docCompanyId = $document->company_id ?? $document->branch?->company_id;
            if ($docCompanyId && (int)$docCompanyId === (int)$activeCompanyId) {
                $inScope = true;
            } elseif ($document->distributions()->whereHas('targetBranch', fn($b) => $b->where('company_id', $activeCompanyId))->exists()) {
                $inScope = true;
            }
        } else {
            // "All Branches" context
            $userBranchIds = $user->allBranchIds();
            $userCompanyIds = $user->allCompanyIds();

            if ($document->branch_id && in_array($document->branch_id, $userBranchIds, true)) {
                $inScope = true;
            } elseif ($document->company_id && in_array($document->company_id, $userCompanyIds, true)) {
                $inScope = true;
            } elseif ($document->distributions()->whereIn('target_branch_id', $userBranchIds)->exists()) {
                $inScope = true;
            }
        }

        if (!$inScope) {
            return false;
        }

        // 2. Role / Access check (only reached if in scope)
        if ($document->isGeneral()) return true;

        if ($document->isDivision()
            && $document->division_id
            && in_array($document->division_id, $user->allDivisionIds(), true)) {
            return true;
        }

        return false;
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

    public function manageScope(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;
        return $user->id === $document->owner_id || $user->isAdmin() || $user->isDirector();
    }

    public function approve(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;
        if ($user->isAdmin() || $user->isDirector()) return true;
        return $user->isHead() && ($user->division_id === $document->division_id || in_array($document->division_id, $user->allDivisionIds(), true));
    }

    public function reject(User $user, Document $document): bool
    {
        return $this->approve($user, $document);
    }

    public function rename(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;
        return $user->isAdmin() || $user->isDirector();
    }

    public function requestRename(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;
        if ($user->id === $document->owner_id || $user->isAdmin() || $user->isDirector()) return true;
        if ($user->isHead() && ($user->division_id === $document->division_id || in_array($document->division_id, $user->allDivisionIds(), true))) {
            return true;
        }
        return $this->update($user, $document);
    }

    public function approveRename(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;

        // Admin and Director can approve rename requests (including their own)
        if ($user->isAdmin() || $user->isDirector()) {
            return true;
        }

        // Division Head can approve requests in their division (including their own)
        if ($user->isHead() && ($user->division_id === $document->division_id || in_array($document->division_id, $user->allDivisionIds(), true))) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Document $document): bool
    {
        if (!$this->view($user, $document)) return false;
        if ($user->isAdmin() || $user->isDirector()) return true;

        // Owner boleh hapus dokumen selama belum punya versi approved (active).
        return $user->id === $document->owner_id
            && !$document->versions()->where('status', 'active')->exists();
    }

    public function restore(User $user, Document $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDirector()) {
            if ($document->owner_id === $user->id) return true;
            $branchIds = $user->allBranchIds();
            $companyIds = $user->allCompanyIds();
            if ($document->branch_id && in_array($document->branch_id, $branchIds, true)) return true;
            if ($document->company_id && in_array($document->company_id, $companyIds, true)) return true;
            return false;
        }

        if ($user->isHead()) {
            if ($document->owner_id === $user->id) return true;
            return $document->isDivision() && in_array($document->division_id, $user->allDivisionIds(), true);
        }

        return $user->id === $document->owner_id;
    }

    public function forceDelete(User $user, Document $document): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDirector()) {
            if ($document->owner_id === $user->id) return true;
            $branchIds = $user->allBranchIds();
            $companyIds = $user->allCompanyIds();
            if ($document->branch_id && in_array($document->branch_id, $branchIds, true)) return true;
            if ($document->company_id && in_array($document->company_id, $companyIds, true)) return true;
            return false;
        }

        // Owner boleh hapus permanen dokumen selama belum pernah punya versi approved (active).
        return $user->id === $document->owner_id
            && !$document->versions()->where('status', 'active')->exists();
    }
}
