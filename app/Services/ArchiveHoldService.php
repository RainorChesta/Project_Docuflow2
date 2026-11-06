<?php

namespace App\Services;

use App\Models\ArchiveEntry;
use App\Models\ArchiveEvent;
use App\Models\ArchiveLegalHold;
use App\Models\User;

class ArchiveHoldService
{
    public function __construct(
        protected ArchiveAuditService $auditService
    ) {}

    /**
     * Apply a legal or audit hold on an archive entry.
     */
    public function applyHold(
        ArchiveEntry $entry,
        string $caseReference,
        string $title,
        string $reason,
        User $actor
    ): ArchiveLegalHold {
        if ($entry->isDisposed() || $entry->isVoided()) {
            throw new \LogicException(__('Cannot place legal hold on disposed or voided archive.'));
        }

        $hold = ArchiveLegalHold::create([
            'company_id' => $entry->company_id,
            'archive_entry_id' => $entry->id,
            'case_reference' => trim($caseReference),
            'title' => trim($title),
            'reason' => trim($reason),
            'status' => ArchiveLegalHold::STATUS_ACTIVE,
            'applied_by' => $actor->id,
            'applied_at' => now(),
        ]);

        $beforeState = ['is_under_legal_hold' => (bool) $entry->is_under_legal_hold];

        $entry->update(['is_under_legal_hold' => true]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_LEGAL_HOLD_APPLIED,
            actor: $actor,
            archiveEntry: $entry,
            reason: "Legal hold applied: {$caseReference} - {$title}",
            beforeState: $beforeState,
            afterState: [
                'is_under_legal_hold' => true,
                'hold_id' => $hold->id,
                'case_reference' => $caseReference,
            ],
            metadata: ['reason' => $reason]
        );

        return $hold;
    }

    /**
     * Release an existing legal hold.
     * Note: If other active holds remain on the record, the entry remains under hold.
     */
    public function releaseHold(
        ArchiveLegalHold $hold,
        string $releaseReason,
        User $actor
    ): ArchiveLegalHold {
        if (!$hold->isActive()) {
            throw new \LogicException(__('This legal hold has already been released.'));
        }

        $entry = $hold->archiveEntry;

        $hold->update([
            'status' => ArchiveLegalHold::STATUS_RELEASED,
            'released_by' => $actor->id,
            'released_at' => now(),
            'release_reason' => trim($releaseReason),
        ]);

        // Evaluate whether any other active holds remain
        $hasRemainingActiveHolds = $entry->activeLegalHolds()->exists();

        $beforeState = ['is_under_legal_hold' => true];
        $entry->update(['is_under_legal_hold' => $hasRemainingActiveHolds]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_LEGAL_HOLD_RELEASED,
            actor: $actor,
            archiveEntry: $entry,
            reason: "Legal hold released: {$hold->case_reference}",
            beforeState: $beforeState,
            afterState: [
                'is_under_legal_hold' => $hasRemainingActiveHolds,
                'hold_id' => $hold->id,
                'remaining_active_holds' => $hasRemainingActiveHolds,
            ],
            metadata: ['release_reason' => $releaseReason]
        );

        return $hold;
    }
}

