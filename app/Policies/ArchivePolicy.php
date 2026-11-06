<?php

namespace App\Policies;

use App\Models\ArchiveEntry;
use App\Models\ArchiveLegalHold;
use App\Models\User;

class ArchivePolicy
{
    /**
     * Determine whether the user can view any archives.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Determine whether the user can view the archive entry.
     */
    public function view(User $user, ArchiveEntry $entry): bool
    {
        if (!$user->is_active) {
            return false;
        }

        // Multi-tenant isolation: User must belong to the archive's company
        if ($entry->company_id && !in_array($entry->company_id, $user->allCompanyIds(), true)) {
            return false;
        }

        // Admin & Direktur have full access across their companies
        if ($user->isAdmin() || $user->isDirector()) {
            return true;
        }

        // PIC Klinik can access records in their branches
        if ($user->isPicKlinik()) {
            return $entry->branch_id ? in_array($entry->branch_id, $user->allBranchIds(), true) : true;
        }

        // Sensitivity check for Head and Staff
        return match ($entry->sensitivity_level) {
            'public' => true,
            'internal' => $this->canAccessInternal($user, $entry),
            'confidential' => $this->canAccessConfidential($user, $entry),
            'restricted' => $this->canAccessRestricted($user, $entry),
            default => false,
        };
    }

    /**
     * Determine whether the user can download or preview original snapshot bytes.
     */
    public function download(User $user, ArchiveEntry $entry): bool
    {
        return $this->view($user, $entry);
    }

    /**
     * Determine whether the user can correct metadata (descriptive fields only, with mandatory reason).
     */
    public function correctMetadata(User $user, ArchiveEntry $entry): bool
    {
        if (!$this->view($user, $entry) || $entry->isDisposed() || $entry->isVoided()) {
            return false;
        }

        if ($user->isAdmin() || $user->isDirector()) {
            return true;
        }

        if ($user->isPicKlinik() && $entry->branch_id && in_array($entry->branch_id, $user->allBranchIds(), true)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reactivate an archive entry as a new working document copy.
     */
    public function reactivate(User $user, ArchiveEntry $entry): bool
    {
        if (!$this->view($user, $entry) || $entry->isDisposed() || $entry->isVoided()) {
            return false;
        }

        return $user->is_active;
    }

    /**
     * Determine whether the user can place a legal hold.
     */
    public function placeHold(User $user, ArchiveEntry $entry): bool
    {
        if (!$this->view($user, $entry) || $entry->isDisposed() || $entry->isVoided()) {
            return false;
        }

        return $user->isAdmin() || $user->isDirector();
    }

    /**
     * Determine whether the user can release an existing legal hold.
     */
    public function releaseHold(User $user, ArchiveEntry $entry, ?ArchiveLegalHold $hold = null): bool
    {
        if (!$this->view($user, $entry)) {
            return false;
        }

        return $user->isAdmin() || $user->isDirector();
    }

    /**
     * Determine whether the user can request disposition.
     */
    public function requestDisposition(User $user, ArchiveEntry $entry): bool
    {
        if (!$this->view($user, $entry) || $entry->isDisposed() || $entry->isVoided() || $entry->isPermanent()) {
            return false;
        }

        if ($entry->isUnderLegalHold()) {
            return false;
        }

        return $user->isAdmin() || $user->isDirector() || $user->isPicKlinik();
    }

    /**
     * Determine whether the user can approve disposition.
     */
    public function approveDisposition(User $user, ArchiveEntry $entry): bool
    {
        if (!$this->view($user, $entry) || $entry->isDisposed() || $entry->isVoided() || $entry->isPermanent()) {
            return false;
        }

        if ($entry->isUnderLegalHold()) {
            return false;
        }

        return $user->isAdmin() || $user->isDirector();
    }

    /**
     * Determine whether the user can void a registered archive entry.
     */
    public function void(User $user, ArchiveEntry $entry): bool
    {
        if (!$this->view($user, $entry) || $entry->isDisposed() || $entry->isVoided()) {
            return false;
        }

        return $user->isAdmin() || $user->isDirector();
    }

    protected function canAccessInternal(User $user, ArchiveEntry $entry): bool
    {
        if (!$entry->branch_id && !$entry->unit_kerja_id) {
            return true;
        }

        if ($entry->branch_id && in_array($entry->branch_id, $user->allBranchIds(), true)) {
            return true;
        }

        if ($entry->unit_kerja_id && in_array($entry->unit_kerja_id, $user->allUnitKerjaIds($entry->branch_id), true)) {
            return true;
        }

        return $entry->owner_id === $user->id || $entry->registered_by === $user->id;
    }

    protected function canAccessConfidential(User $user, ArchiveEntry $entry): bool
    {
        if ($entry->owner_id === $user->id || $entry->registered_by === $user->id) {
            return true;
        }

        if ($user->isHead() && $entry->unit_kerja_id && in_array($entry->unit_kerja_id, $user->allUnitKerjaIds($entry->branch_id), true)) {
            return true;
        }

        return false;
    }

    protected function canAccessRestricted(User $user, ArchiveEntry $entry): bool
    {
        return $entry->owner_id === $user->id || $entry->registered_by === $user->id || $entry->approved_by === $user->id;
    }
}
