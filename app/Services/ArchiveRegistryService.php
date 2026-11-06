<?php

namespace App\Services;

use App\Models\ArchiveClassification;
use App\Models\ArchiveEntry;
use App\Models\ArchiveEvent;
use App\Models\ArchiveRelationship;
use App\Models\ArchiveRequest;
use App\Models\ArchiveRetentionPolicy;
use App\Models\ArchiveVoidRecord;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArchiveRegistryService
{
    public function __construct(
        protected ArchiveNumberingService $numberingService,
        protected ArchiveEligibilityService $eligibilityService,
        protected ArchiveSnapshotService $snapshotService,
        protected ArchiveRetentionService $retentionService,
        protected ArchiveAuditService $auditService
    ) {}

    /**
     * Create an archive request from an eligible document.
     */
    public function createRequest(
        Document $document,
        ArchiveClassification $classification,
        User $requester,
        array $attributes = [],
        ?string $idempotencyKey = null
    ): ArchiveRequest {
        $idempotencyKey = $idempotencyKey ?: (string) Str::uuid();

        // Check if an existing request with this idempotency key already exists
        $existing = ArchiveRequest::where('company_id', $document->company_id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $existing;
        }

        $version = !empty($attributes['requested_version_id'])
            ? DocumentVersion::findOrFail($attributes['requested_version_id'])
            : $document->displayVersion();

        // Check preflight eligibility
        $eligibility = $this->eligibilityService->checkEligibility($document, $version);
        if (!$eligibility['eligible']) {
            throw new \InvalidArgumentException(__('Document is not eligible for archiving: :reasons', [
                'reasons' => implode(', ', $eligibility['reasons']),
            ]));
        }

        $retentionPolicy = !empty($attributes['retention_policy_id'])
            ? ArchiveRetentionPolicy::findOrFail($attributes['retention_policy_id'])
            : $classification->defaultRetentionPolicy;

        $requiresApproval = $classification->requires_approval && !($requester->isAdmin() || $requester->isDirector());

        $request = ArchiveRequest::create([
            'company_id' => $document->company_id,
            'branch_id' => $document->branch_id,
            'unit_kerja_id' => $document->unit_kerja_id,
            'document_id' => $document->id,
            'requested_version_id' => $version->id,
            'predecessor_entry_id' => $attributes['predecessor_entry_id'] ?? null,
            'requested_by' => $requester->id,
            'classification_id' => $classification->id,
            'retention_policy_id' => $retentionPolicy?->id,
            'archive_title' => $attributes['archive_title'] ?? $document->title,
            'description' => $attributes['description'] ?? $document->summary,
            'archive_reason' => $attributes['archive_reason'] ?? null,
            'record_date' => $attributes['record_date'] ?? ($document->created_at?->toDateString() ?? now()->toDateString()),
            'effective_date' => $attributes['effective_date'] ?? ($document->expiration_date?->toDateString() ?? null),
            'sensitivity_level' => $attributes['sensitivity_level'] ?? $classification->confidentiality_level ?? 'internal',
            'tags' => $attributes['tags'] ?? [],
            'storage_location' => $attributes['storage_location'] ?? null,
            'state' => ArchiveRequest::STATE_DRAFT,
            'requires_approval' => $requiresApproval,
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_REQUEST_CREATED,
            actor: $requester,
            archiveRequest: $request,
            reason: 'Archive registration request drafted.',
            afterState: ['state' => $request->state, 'idempotency_key' => $idempotencyKey]
        );

        return $request;
    }

    /**
     * Submit an archive request for review or auto-approval.
     */
    public function submitRequest(ArchiveRequest $request, User $actor): ArchiveRequest
    {
        if (!$request->isEditable()) {
            throw new \LogicException(__('Archive request is not in editable state.'));
        }

        $nextState = $request->requires_approval
            ? ArchiveRequest::STATE_PENDING_APPROVAL
            : ArchiveRequest::STATE_APPROVED;

        $request->update([
            'state' => $nextState,
            'approved_at' => $nextState === ArchiveRequest::STATE_APPROVED ? now() : null,
            'approved_by' => $nextState === ArchiveRequest::STATE_APPROVED ? $actor->id : null,
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_REQUEST_SUBMITTED,
            actor: $actor,
            archiveRequest: $request,
            reason: $nextState === ArchiveRequest::STATE_APPROVED
                ? 'Request submitted and auto-approved based on role policy.'
                : 'Request submitted for review and approval.',
            afterState: ['state' => $request->state]
        );

        return $request;
    }

    /**
     * Approve an archive request.
     */
    public function approveRequest(ArchiveRequest $request, User $approver, ?string $notes = null): ArchiveRequest
    {
        if (!$request->isPendingApproval()) {
            throw new \LogicException(__('Only pending requests can be approved.'));
        }

        $request->update([
            'state' => ArchiveRequest::STATE_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'decision_notes' => $notes,
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_REQUEST_APPROVED,
            actor: $approver,
            archiveRequest: $request,
            reason: $notes ?: 'Archive request approved.',
            afterState: ['state' => ArchiveRequest::STATE_APPROVED, 'decision_notes' => $notes]
        );

        return $request;
    }

    /**
     * Reject an archive request.
     */
    public function rejectRequest(ArchiveRequest $request, User $actor, string $notes): ArchiveRequest
    {
        if (!$request->isPendingApproval()) {
            throw new \LogicException(__('Only pending requests can be rejected.'));
        }

        $request->update([
            'state' => ArchiveRequest::STATE_REJECTED,
            'approved_by' => $actor->id,
            'approved_at' => now(),
            'decision_notes' => $notes,
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_REQUEST_REJECTED,
            actor: $actor,
            archiveRequest: $request,
            reason: "Archive request rejected: {$notes}",
            afterState: ['state' => ArchiveRequest::STATE_REJECTED, 'decision_notes' => $notes]
        );

        return $request;
    }

    /**
     * Return an archive request for correction.
     */
    public function returnForCorrection(ArchiveRequest $request, User $actor, string $notes): ArchiveRequest
    {
        if (!$request->isPendingApproval()) {
            throw new \LogicException(__('Only pending requests can be returned for correction.'));
        }

        $request->update([
            'state' => ArchiveRequest::STATE_RETURNED,
            'decision_notes' => $notes,
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_REQUEST_RETURNED,
            actor: $actor,
            archiveRequest: $request,
            reason: "Request returned for correction: {$notes}",
            afterState: ['state' => ArchiveRequest::STATE_RETURNED, 'decision_notes' => $notes]
        );

        return $request;
    }

    /**
     * Cancel an archive request.
     */
    public function cancelRequest(ArchiveRequest $request, User $actor, ?string $reason = null): ArchiveRequest
    {
        if (in_array($request->state, [ArchiveRequest::STATE_REGISTERED, ArchiveRequest::STATE_CANCELED], true)) {
            throw new \LogicException(__('Cannot cancel request in state :state', ['state' => $request->state]));
        }

        $request->update([
            'state' => ArchiveRequest::STATE_CANCELED,
            'decision_notes' => $reason,
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_REQUEST_CANCELED,
            actor: $actor,
            archiveRequest: $request,
            reason: $reason ?: 'Archive request canceled by user.',
            afterState: ['state' => ArchiveRequest::STATE_CANCELED]
        );

        return $request;
    }

    /**
     * Permanently delete a non-registered archive request (e.g. canceled, rejected, draft, failed).
     */
    public function deleteRequest(ArchiveRequest $request, User $actor, ?string $reason = null): bool
    {
        if ($request->state === ArchiveRequest::STATE_REGISTERED) {
            throw new \LogicException(__('Cannot delete an already registered archive request.'));
        }

        $requestId = $request->id;
        $title = $request->archive_title;
        $state = $request->state;
        $companyId = $request->company_id;

        // Clean up unlinked snapshot file if present
        if ($request->snapshot && !$request->snapshot->archive_entry_id) {
            $disk = \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'));
            if ($request->snapshot->object_key && $disk->exists($request->snapshot->object_key)) {
                $disk->delete($request->snapshot->object_key);
            }
            $request->snapshot->delete();
        }

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_REQUEST_DELETED,
            actor: $actor,
            reason: $reason ?: "Deleted archive request #{$requestId} ('{$title}', state: {$state}).",
            beforeState: ['request_id' => $requestId, 'title' => $title, 'state' => $state],
            companyId: $companyId
        );

        $request->delete();

        return true;
    }

    /**
     * Finalize an approved archive request into an immutable official ArchiveEntry.
     * Guaranteed atomic and idempotent.
     */
    public function finalizeRegistration(ArchiveRequest $request, User $actor): ArchiveEntry
    {
        // 1. Idempotency Check: if already registered, return existing entry
        if ($request->registered_archive_entry_id) {
            $existing = ArchiveEntry::find($request->registered_archive_entry_id);
            if ($existing) {
                return $existing;
            }
        }

        if (!in_array($request->state, [
            ArchiveRequest::STATE_APPROVED,
            ArchiveRequest::STATE_READY_TO_REGISTER,
            ArchiveRequest::STATE_PREPARING_SNAPSHOT,
            ArchiveRequest::STATE_FAILED,
        ], true)) {
            throw new \LogicException(__('Request must be approved before registration (current state: :state).', ['state' => $request->state]));
        }

        $request->update(['state' => ArchiveRequest::STATE_PREPARING_SNAPSHOT]);

        // 2. Capture and verify immutable snapshot OUTSIDE the numbering counter lock
        try {
            $snapshot = $this->snapshotService->captureSnapshot($request, $actor);
        } catch (\Throwable $e) {
            $request->update([
                'state' => ArchiveRequest::STATE_FAILED,
                'last_error_message' => $e->getMessage(),
            ]);
            throw $e;
        }

        $request->update(['state' => ArchiveRequest::STATE_READY_TO_REGISTER]);

        // 3. Atomically allocate number and insert ArchiveEntry inside DB transaction
        return DB::transaction(function () use ($request, $snapshot, $actor) {
            // Re-lock request row
            $lockedRequest = ArchiveRequest::lockForUpdate()->findOrFail($request->id);
            if ($lockedRequest->registered_archive_entry_id) {
                $existing = ArchiveEntry::find($lockedRequest->registered_archive_entry_id);
                if ($existing) {
                    return $existing;
                }
            }

            $company = $lockedRequest->company;
            $branch = $lockedRequest->branch;
            $unitKerja = $lockedRequest->unitKerja;
            $classification = $lockedRequest->classification;
            $document = $lockedRequest->document()->withTrashed()->first();
            $version = $lockedRequest->requestedVersion;

            // Allocate official archive number
            $allocation = $this->numberingService->allocateNumber(
                company: $company,
                branch: $branch,
                unit: $unitKerja,
                classification: $classification,
                date: now()
            );

            $uuid = (string) Str::uuid();
            $qrToken = Str::random(48);

            $entry = ArchiveEntry::create([
                'uuid' => $uuid,
                'company_id' => $lockedRequest->company_id,
                'branch_id' => $lockedRequest->branch_id,
                'unit_kerja_id' => $lockedRequest->unit_kerja_id,
                'document_id' => $lockedRequest->document_id,
                'document_version_id' => $lockedRequest->requested_version_id,
                'request_id' => $lockedRequest->id,
                'predecessor_entry_id' => $lockedRequest->predecessor_entry_id,
                'archive_number' => $allocation['number'],
                'normalized_number' => $allocation['normalized'],
                'scheme_id' => $allocation['scheme_id'],
                'scheme_version' => $allocation['scheme_version'],
                'sequence_number' => $allocation['sequence'],
                'registration_period' => $allocation['registration_period'],
                'registered_at' => now(),
                'registered_by' => $actor->id,
                'approved_by' => $lockedRequest->approved_by ?: $actor->id,
                'classification_id' => $classification->id,
                'classification_code_snapshot' => $classification->code,
                'classification_name_snapshot' => $classification->name,
                'company_code_snapshot' => $company?->code ?: 'CMP',
                'company_name_snapshot' => $company?->name,
                'branch_code_snapshot' => $branch?->code ?: ($branch?->effective_code ?: 'PST'),
                'branch_name_snapshot' => $branch?->name,
                'unit_code_snapshot' => $unitKerja?->kode_unit_kerja ?: 'GEN',
                'unit_name_snapshot' => $unitKerja?->nama_unit_kerja,
                'title' => $lockedRequest->archive_title,
                'description' => $lockedRequest->description,
                'original_document_number' => $document?->document_number,
                'version_number_snapshot' => $version?->version_number ?: 1,
                'owner_id' => $document?->owner_id ?: $lockedRequest->requested_by,
                'owner_name_snapshot' => $document?->owner?->name ?: $lockedRequest->requester?->name,
                'sensitivity_level' => $lockedRequest->sensitivity_level,
                'archive_reason' => $lockedRequest->archive_reason,
                'record_date' => $lockedRequest->record_date,
                'effective_date' => $lockedRequest->effective_date,
                'tags' => $lockedRequest->tags,
                'storage_location' => $lockedRequest->storage_location,
                'status' => ArchiveEntry::STATUS_REGISTERED,
                'is_under_legal_hold' => false,
                'metadata_snapshot' => $snapshot->manifest_payload,
                'qr_token' => $qrToken,
            ]);

            // Link snapshot to entry
            $snapshot->update(['archive_entry_id' => $entry->id]);

            // Assign retention schedule
            if ($lockedRequest->retentionPolicy) {
                $this->retentionService->assignPolicy(
                    entry: $entry,
                    policy: $lockedRequest->retentionPolicy,
                    actor: $actor,
                    reason: 'Initial registration retention assignment'
                );
            }

            // Create lineage relationship if predecessor exists
            if ($lockedRequest->predecessor_entry_id) {
                ArchiveRelationship::create([
                    'from_entry_id' => $entry->id,
                    'to_entry_id' => $lockedRequest->predecessor_entry_id,
                    'relation_type' => ArchiveRelationship::TYPE_SUPERSEDES,
                    'created_by' => $actor->id,
                    'notes' => 'Superceding predecessor archive via registration',
                ]);
            }

            // Mark request as registered
            $lockedRequest->update([
                'state' => ArchiveRequest::STATE_REGISTERED,
                'registered_archive_entry_id' => $entry->id,
            ]);

            // Log official audit event
            $this->auditService->log(
                eventType: ArchiveEvent::EVENT_ARCHIVE_REGISTERED,
                actor: $actor,
                archiveEntry: $entry,
                archiveRequest: $lockedRequest,
                reason: "Official archive registered with number {$entry->archive_number}",
                afterState: [
                    'archive_number' => $entry->archive_number,
                    'uuid' => $entry->uuid,
                    'status' => $entry->status,
                ]
            );

            return $entry;
        });
    }

    /**
     * Correct descriptive metadata for an archive entry with mandatory audit logging.
     */
    public function correctMetadata(ArchiveEntry $entry, array $changes, string $reason, User $actor): ArchiveEntry
    {
        if ($entry->isDisposed() || $entry->isVoided()) {
            throw new \LogicException(__('Cannot edit metadata of disposed or voided archive.'));
        }

        // Whitelist descriptive fields only
        $allowed = ['title', 'description', 'archive_reason', 'tags', 'storage_location', 'sensitivity_level', 'record_date', 'effective_date'];
        $filtered = array_intersect_key($changes, array_flip($allowed));

        if (empty($filtered)) {
            throw new \InvalidArgumentException(__('No valid metadata fields provided for correction.'));
        }

        $before = array_intersect_key($entry->toArray(), $filtered);

        $entry->update($filtered);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_METADATA_CORRECTED,
            actor: $actor,
            archiveEntry: $entry,
            reason: $reason,
            beforeState: $before,
            afterState: $filtered,
            metadata: ['reason' => $reason]
        );

        return $entry;
    }

    /**
     * Reactivate an archive snapshot as a new working document copy.
     */
    public function reactivateAsWorkingCopy(ArchiveEntry $entry, User $actor, string $reason): Document
    {
        if ($entry->isDisposed() || $entry->isVoided()) {
            throw new \LogicException(__('Cannot reactivate a disposed or voided archive.'));
        }

        $snapshot = $entry->snapshot;
        if (!$snapshot) {
            throw new \RuntimeException(__('Archive snapshot not found.'));
        }

        return DB::transaction(function () use ($entry, $snapshot, $actor, $reason) {
            $origDoc = $entry->document()->withTrashed()->first();
            $docType = $origDoc?->document_type_id ?: \App\Models\DocumentType::where('company_id', $entry->company_id)->value('id') ?: \App\Models\DocumentType::first()?->id;

            $baseNum = $entry->original_document_number ?: ('DRAFT/' . now()->format('Ymd'));
            $docNumber = $baseNum . '-REV' . now()->format('His') . rand(10, 99);

            $workingDoc = Document::create([
                'title' => $entry->title . ' (Working Copy)',
                'document_number' => $docNumber,
                'document_type_id' => $docType,
                'summary' => $entry->description,
                'company_id' => $entry->company_id,
                'branch_id' => $entry->branch_id,
                'unit_kerja_id' => $entry->unit_kerja_id,
                'owner_id' => $actor->id,
                'visibility' => 'unit_kerja',
                'format_choice' => 'baru',
            ]);

            // Copy file snapshot to new document working path
            $disk = \Illuminate\Support\Facades\Storage::disk($snapshot->storage_disk);
            $extension = pathinfo($snapshot->original_filename ?: 'doc.docx', PATHINFO_EXTENSION) ?: 'docx';
            $workingPath = "documents/{$workingDoc->id}/v1.{$extension}";

            if ($snapshot->object_key && $disk->exists($snapshot->object_key)) {
                $disk->copy($snapshot->object_key, $workingPath);
            }

            $version = DocumentVersion::create([
                'document_id' => $workingDoc->id,
                'version_number' => 1,
                'content' => $snapshot->html_content ?: '<p>Reactivated from archive ' . e($entry->archive_number) . '</p>',
                'file_path' => $workingPath,
                'file_original_name' => $snapshot->original_filename,
                'file_mime' => $snapshot->mime_type,
                'author_id' => $actor->id,
                'author_name' => $actor->name,
                'status' => 'draft',
                'change_summary' => "Reactivated from Archive {$entry->archive_number}: {$reason}",
            ]);

            $workingDoc->update(['current_version_id' => $version->id]);

            // Track relationship lineage
            ArchiveRelationship::create([
                'from_entry_id' => $entry->id,
                'related_document_id' => $workingDoc->id,
                'relation_type' => ArchiveRelationship::TYPE_REACTIVATED_TO_DOCUMENT,
                'created_by' => $actor->id,
                'notes' => $reason,
            ]);

            $this->auditService->log(
                eventType: ArchiveEvent::EVENT_WORKING_COPY_REACTIVATED,
                actor: $actor,
                archiveEntry: $entry,
                reason: "Reactivated as new working document #{$workingDoc->id}: {$reason}",
                afterState: [
                    'working_document_id' => $workingDoc->id,
                    'working_version_id' => $version->id,
                ]
            );

            return $workingDoc;
        });
    }

    /**
     * Void an official archive entry.
     * The record is marked voided and its archive number is permanently recorded in archive_void_records
     * so that the sequence number is NEVER reused.
     */
    public function voidArchiveEntry(ArchiveEntry $entry, string $reason, User $actor): ArchiveVoidRecord
    {
        if ($entry->isVoided()) {
            throw new \LogicException(__('Archive entry is already voided.'));
        }

        return DB::transaction(function () use ($entry, $reason, $actor) {
            $voidRecord = ArchiveVoidRecord::create([
                'company_id' => $entry->company_id,
                'archive_entry_id' => $entry->id,
                'archive_number' => $entry->archive_number,
                'normalized_number' => $entry->normalized_number,
                'void_reason' => trim($reason),
                'voided_by' => $actor->id,
                'voided_at' => now(),
                'metadata_snapshot' => [
                    'title' => $entry->title,
                    'classification' => $entry->classification_code_snapshot,
                    'registered_at' => $entry->registered_at?->toIso8601String(),
                    'registered_by' => $entry->registered_by,
                    'sha256' => $entry->snapshot?->content_hash_sha256,
                ],
            ]);

            $before = ['status' => $entry->status];

            $entry->update([
                'status' => ArchiveEntry::STATUS_VOIDED,
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => $reason,
            ]);

            $this->auditService->log(
                eventType: ArchiveEvent::EVENT_ARCHIVE_VOIDED,
                actor: $actor,
                archiveEntry: $entry,
                reason: "Archive voided: {$reason}",
                beforeState: $before,
                afterState: [
                    'status' => ArchiveEntry::STATUS_VOIDED,
                    'void_record_id' => $voidRecord->id,
                ]
            );

            return $voidRecord;
        });
    }
}
