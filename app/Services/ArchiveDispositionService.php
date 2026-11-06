<?php

namespace App\Services;

use App\Models\ArchiveDispositionRequest;
use App\Models\ArchiveEntry;
use App\Models\ArchiveEvent;
use App\Models\ArchiveSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArchiveDispositionService
{
    public function __construct(
        protected ArchiveAuditService $auditService,
        protected ArchiveRetentionService $retentionService
    ) {}

    /**
     * Submit a formal disposition request for an archive entry.
     */
    public function requestDisposition(
        ArchiveEntry $entry,
        string $actionType,
        string $justification,
        User $actor,
        ?int $extensionDays = null
    ): ArchiveDispositionRequest {
        if ($entry->isUnderLegalHold()) {
            throw new \LogicException(__('Cannot request disposition for an archive under active legal hold.'));
        }

        if ($entry->isDisposed() || $entry->isVoided()) {
            throw new \LogicException(__('Archive is already disposed or voided.'));
        }

        if ($entry->isPermanent() && $actionType === ArchiveDispositionRequest::ACTION_DESTROY) {
            throw new \LogicException(__('Permanent archives cannot be destroyed.'));
        }

        $request = ArchiveDispositionRequest::create([
            'company_id' => $entry->company_id,
            'archive_entry_id' => $entry->id,
            'requested_by' => $actor->id,
            'requested_at' => now(),
            'action_type' => $actionType,
            'justification' => trim($justification),
            'extension_days' => $extensionDays,
            'status' => ArchiveDispositionRequest::STATUS_PENDING,
        ]);

        $beforeState = ['status' => $entry->status];
        $entry->update(['status' => ArchiveEntry::STATUS_DISPOSITION_PENDING]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_DISPOSITION_REQUESTED,
            actor: $actor,
            archiveEntry: $entry,
            reason: "Disposition requested: {$actionType}",
            beforeState: $beforeState,
            afterState: [
                'status' => ArchiveEntry::STATUS_DISPOSITION_PENDING,
                'disposition_request_id' => $request->id,
                'action_type' => $actionType,
            ],
            metadata: ['justification' => $justification]
        );

        return $request;
    }

    /**
     * Reject a pending disposition request.
     */
    public function rejectDisposition(
        ArchiveDispositionRequest $request,
        string $notes,
        User $actor
    ): ArchiveDispositionRequest {
        if (!$request->isPending()) {
            throw new \LogicException(__('Only pending disposition requests can be rejected.'));
        }

        $entry = $request->archiveEntry;

        $request->update([
            'status' => ArchiveDispositionRequest::STATUS_REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_notes' => trim($notes),
        ]);

        $entry->update([
            'status' => $entry->isEligibleForDisposition()
                ? ArchiveEntry::STATUS_ELIGIBLE_FOR_DISPOSITION
                : ArchiveEntry::STATUS_UNDER_REVIEW,
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_DISPOSITION_APPROVED,
            actor: $actor,
            archiveEntry: $entry,
            reason: "Disposition request rejected: {$notes}",
            afterState: ['disposition_status' => ArchiveDispositionRequest::STATUS_REJECTED]
        );

        return $request;
    }

    /**
     * Authorize and execute disposition in an atomic transaction.
     */
    public function executeDisposition(
        ArchiveDispositionRequest $request,
        User $actor,
        ?string $reviewNotes = null
    ): ArchiveDispositionRequest {
        if (!$request->isPending()) {
            throw new \LogicException(__('Only pending disposition requests can be executed.'));
        }

        return DB::transaction(function () use ($request, $actor, $reviewNotes) {
            $entry = ArchiveEntry::lockForUpdate()->findOrFail($request->archive_entry_id);

            // Double check legal holds
            if ($entry->isUnderLegalHold() || $entry->activeLegalHolds()->exists()) {
                throw new \SecurityException(__('Execution blocked: Archive is currently subject to an active legal hold!'));
            }

            if ($entry->isDisposed() || $entry->isVoided()) {
                throw new \LogicException(__('Archive is already disposed or voided.'));
            }

            $action = $request->action_type;
            $companyCode = $entry->company_code_snapshot ?: 'CMP';
            $year = now()->format('Y');
            $certSeq = strtoupper(Str::random(8));
            $certNumber = "CERT-DISP/{$companyCode}/{$year}/{$certSeq}";

            $certSnapshot = [
                'certificate_number' => $certNumber,
                'archive_number' => $entry->archive_number,
                'archive_uuid' => $entry->uuid,
                'document_title' => $entry->title,
                'classification' => $entry->classification_code_snapshot,
                'action_executed' => $action,
                'justification' => $request->justification,
                'review_notes' => $reviewNotes,
                'authorized_by' => [
                    'id' => $actor->id,
                    'name' => $actor->name,
                    'role' => $actor->system_role,
                ],
                'requested_by' => [
                    'id' => $request->requested_by,
                    'name' => $request->requester?->name,
                ],
                'executed_at' => now()->toIso8601String(),
                'hash_witness' => [
                    'content_hash_sha256' => $entry->snapshot?->content_hash_sha256,
                    'manifest_hash_sha256' => $entry->snapshot?->manifest_hash_sha256,
                ],
            ];

            if ($action === ArchiveDispositionRequest::ACTION_DESTROY) {
                // Delete physical binary file from disk
                $snapshot = $entry->snapshot;
                if ($snapshot && $snapshot->object_key) {
                    $disk = Storage::disk($snapshot->storage_disk);
                    if ($disk->exists($snapshot->object_key)) {
                        $disk->delete($snapshot->object_key);
                    }

                    $snapshot->update([
                        'integrity_status' => ArchiveSnapshot::INTEGRITY_DISPOSED,
                        'html_content' => null,
                        'last_verified_at' => now(),
                    ]);
                }

                $entry->update([
                    'status' => ArchiveEntry::STATUS_DISPOSED,
                    'disposed_at' => now(),
                    'disposed_by' => $actor->id,
                    'disposition_certificate_number' => $certNumber,
                ]);
            } elseif ($action === ArchiveDispositionRequest::ACTION_PERMANENT) {
                $entry->update([
                    'status' => ArchiveEntry::STATUS_PERMANENT,
                    'final_disposition_action' => 'permanent',
                    'retention_due_at' => null,
                ]);
            } elseif ($action === ArchiveDispositionRequest::ACTION_EXTEND) {
                $days = $request->extension_days ?: 365;
                $this->retentionService->extendRetention($entry, $days, $request->justification, $actor);
            }

            $request->update([
                'status' => ArchiveDispositionRequest::STATUS_EXECUTED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
                'executed_by' => $actor->id,
                'executed_at' => now(),
                'certificate_number' => $certNumber,
                'certificate_snapshot' => $certSnapshot,
            ]);

            $this->auditService->log(
                eventType: ArchiveEvent::EVENT_DISPOSITION_EXECUTED,
                actor: $actor,
                archiveEntry: $entry,
                reason: "Disposition executed: {$action}. Certificate: {$certNumber}",
                afterState: [
                    'status' => $entry->status,
                    'certificate_number' => $certNumber,
                    'action' => $action,
                ],
                metadata: $certSnapshot
            );

            return $request;
        });
    }
}

