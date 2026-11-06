<?php

namespace App\Services;

use App\Models\ArchiveEntry;
use App\Models\ArchiveEvent;
use App\Models\ArchiveRetentionAssignment;
use App\Models\ArchiveRetentionPolicy;
use App\Models\User;
use Carbon\Carbon;

class ArchiveRetentionService
{
    public function __construct(
        protected ArchiveAuditService $auditService
    ) {}

    /**
     * Compute retention schedule dates from policy and trigger event.
     */
    public function calculateSchedule(
        ArchiveRetentionPolicy $policy,
        ?Carbon $registrationDate = null,
        ?Carbon $documentDate = null,
        ?Carbon $expirationDate = null
    ): array {
        $regDate = $registrationDate ? $registrationDate->copy() : now();

        $triggerDate = match ($policy->trigger_event) {
            'document_date' => $documentDate ? $documentDate->copy() : $regDate,
            'expiration_date' => $expirationDate ? $expirationDate->copy() : $regDate,
            default => $regDate,
        };

        $activeUntil = $triggerDate->copy()->addYears($policy->active_retention_years);

        if ($policy->final_disposition === 'permanent') {
            $retentionDueAt = null;
        } elseif ($policy->total_retention_days > 0) {
            $retentionDueAt = $triggerDate->copy()->addDays($policy->total_retention_days);
        } else {
            $totalYears = $policy->active_retention_years + $policy->inactive_retention_years;
            $retentionDueAt = $triggerDate->copy()->addYears($totalYears);
        }

        return [
            'trigger_event' => $policy->trigger_event,
            'trigger_date' => $triggerDate->toDateString(),
            'active_until' => $activeUntil->toDateString(),
            'retention_due_at' => $retentionDueAt ? $retentionDueAt->toDateString() : null,
            'final_action' => $policy->final_disposition,
            'policy_version' => $policy->version,
        ];
    }

    /**
     * Apply retention policy to an archive entry and record the assignment.
     */
    public function assignPolicy(
        ArchiveEntry $entry,
        ArchiveRetentionPolicy $policy,
        ?User $actor = null,
        ?string $reason = null
    ): ArchiveRetentionAssignment {
        $regDate = $entry->registered_at ? Carbon::parse($entry->registered_at) : now();
        $docDate = $entry->record_date ? Carbon::parse($entry->record_date) : null;
        $expDate = $entry->effective_date ? Carbon::parse($entry->effective_date) : null;

        $schedule = $this->calculateSchedule($policy, $regDate, $docDate, $expDate);

        // Mark existing active assignments as superseded
        $entry->retentionAssignments()->where('status', 'active')->update(['status' => 'superseded']);

        $assignment = ArchiveRetentionAssignment::create([
            'archive_entry_id' => $entry->id,
            'retention_policy_id' => $policy->id,
            'policy_version' => $policy->version,
            'trigger_event' => $schedule['trigger_event'],
            'trigger_date' => $schedule['trigger_date'],
            'active_until' => $schedule['active_until'],
            'eligible_from' => $schedule['retention_due_at'] ?? $schedule['active_until'],
            'final_action' => $schedule['final_action'],
            'calculation_snapshot' => $schedule,
            'status' => 'active',
            'assigned_by' => $actor?->id,
            'reason' => $reason,
        ]);

        $status = $policy->final_disposition === 'permanent'
            ? ArchiveEntry::STATUS_PERMANENT
            : $entry->status;

        $entry->update([
            'retention_policy_id' => $policy->id,
            'retention_trigger_date' => $schedule['trigger_date'],
            'retention_active_until' => $schedule['active_until'],
            'retention_due_at' => $schedule['retention_due_at'],
            'final_disposition_action' => $schedule['final_action'],
            'status' => $status,
        ]);

        return $assignment;
    }

    /**
     * Extend retention period for an archive entry.
     */
    public function extendRetention(
        ArchiveEntry $entry,
        int $extensionDays,
        string $reason,
        User $actor
    ): ArchiveRetentionAssignment {
        if ($entry->isDisposed() || $entry->isVoided()) {
            throw new \LogicException(__('Cannot extend retention for disposed or voided archive.'));
        }

        $currentDue = $entry->retention_due_at ? Carbon::parse($entry->retention_due_at) : now();
        $newDue = $currentDue->copy()->addDays($extensionDays);

        $before = [
            'retention_due_at' => $entry->retention_due_at?->toDateString(),
            'status' => $entry->status,
        ];

        // Supersede active assignments
        $entry->retentionAssignments()->where('status', 'active')->update(['status' => 'extended']);

        $assignment = ArchiveRetentionAssignment::create([
            'archive_entry_id' => $entry->id,
            'retention_policy_id' => $entry->retention_policy_id,
            'policy_version' => 1,
            'trigger_event' => 'retention_extension',
            'trigger_date' => now()->toDateString(),
            'active_until' => $entry->retention_active_until?->toDateString() ?? now()->toDateString(),
            'eligible_from' => $newDue->toDateString(),
            'final_action' => $entry->final_disposition_action,
            'calculation_snapshot' => [
                'previous_due_at' => $currentDue->toDateString(),
                'extension_days' => $extensionDays,
                'new_due_at' => $newDue->toDateString(),
                'reason' => $reason,
            ],
            'status' => 'active',
            'assigned_by' => $actor->id,
            'reason' => $reason,
        ]);

        $newStatus = $entry->status === ArchiveEntry::STATUS_ELIGIBLE_FOR_DISPOSITION
            ? ArchiveEntry::STATUS_UNDER_REVIEW
            : $entry->status;

        $entry->update([
            'retention_due_at' => $newDue->toDateString(),
            'status' => $newStatus,
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_RETENTION_EXTENDED,
            actor: $actor,
            archiveEntry: $entry,
            reason: $reason,
            beforeState: $before,
            afterState: [
                'retention_due_at' => $newDue->toDateString(),
                'status' => $newStatus,
                'extension_days' => $extensionDays,
            ]
        );

        return $assignment;
    }
}

