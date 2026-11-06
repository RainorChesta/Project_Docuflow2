<?php

namespace App\Policies;

use App\Models\ArchiveRequest;
use App\Models\User;

class ArchiveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, ArchiveRequest $request): bool
    {
        if (!$user->is_active) {
            return false;
        }

        if ($request->company_id && !in_array($request->company_id, $user->allCompanyIds(), true)) {
            return false;
        }

        if ($user->isAdmin() || $user->isDirector()) {
            return true;
        }

        if ($request->requested_by === $user->id || $request->approver_id === $user->id) {
            return true;
        }

        if ($user->isPicKlinik() && $request->branch_id && in_array($request->branch_id, $user->allBranchIds(), true)) {
            return true;
        }

        if ($user->isHead() && $request->unit_kerja_id && in_array($request->unit_kerja_id, $user->allUnitKerjaIds($request->branch_id), true)) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, ArchiveRequest $request): bool
    {
        if (!$request->isEditable()) {
            return false;
        }

        return $request->requested_by === $user->id || $user->isAdmin();
    }

    public function submit(User $user, ArchiveRequest $request): bool
    {
        return $this->update($user, $request);
    }

    public function approve(User $user, ArchiveRequest $request): bool
    {
        if (!$request->isPendingApproval()) {
            return false;
        }

        if ($request->company_id && !in_array($request->company_id, $user->allCompanyIds(), true)) {
            return false;
        }

        if ($user->isAdmin() || $user->isDirector()) {
            return true;
        }

        if ($user->isPicKlinik() && $request->branch_id && in_array($request->branch_id, $user->allBranchIds(), true)) {
            return true;
        }

        if ($user->isHead() && $request->unit_kerja_id && in_array($request->unit_kerja_id, $user->allUnitKerjaIds($request->branch_id), true)) {
            return true;
        }

        return false;
    }

    public function reject(User $user, ArchiveRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    public function returnForCorrection(User $user, ArchiveRequest $request): bool
    {
        return $this->approve($user, $request);
    }

    public function cancel(User $user, ArchiveRequest $request): bool
    {
        if (in_array($request->state, [ArchiveRequest::STATE_REGISTERED, ArchiveRequest::STATE_CANCELED], true)) {
            return false;
        }

        return $request->requested_by === $user->id || $user->isAdmin() || $user->isDirector();
    }

    public function delete(User $user, ArchiveRequest $request): bool
    {
        // Registered requests cannot be deleted directly
        if ($request->state === ArchiveRequest::STATE_REGISTERED) {
            return false;
        }

        // Company isolation check
        if ($request->company_id && !in_array($request->company_id, $user->allCompanyIds(), true)) {
            return false;
        }

        // Canceled, rejected, failed, or draft requests can be permanently deleted
        if (in_array($request->state, [
            ArchiveRequest::STATE_CANCELED,
            ArchiveRequest::STATE_REJECTED,
            ArchiveRequest::STATE_FAILED,
            ArchiveRequest::STATE_DRAFT,
        ], true)) {
            return $request->requested_by === $user->id || $user->isAdmin() || $user->isDirector();
        }

        return false;
    }

    public function finalize(User $user, ArchiveRequest $request): bool
    {
        if (!in_array($request->state, [
            ArchiveRequest::STATE_APPROVED,
            ArchiveRequest::STATE_READY_TO_REGISTER,
            ArchiveRequest::STATE_FAILED,
            ArchiveRequest::STATE_PREPARING_SNAPSHOT,
        ], true)) {
            return false;
        }

        if ($request->company_id && !in_array($request->company_id, $user->allCompanyIds(), true)) {
            return false;
        }

        return $user->isAdmin() || $user->isDirector() || $user->isPicKlinik() || $request->approved_by === $user->id;
    }
}
