<?php

namespace App\Services;

use App\Models\ArchiveEntry;
use App\Models\ArchiveEvent;
use App\Models\ArchiveRequest;
use App\Models\ArchiveSnapshot;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\SignatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArchiveSnapshotService
{
    public function __construct(
        protected ArchiveAuditService $auditService,
        protected ?OnlyOfficeService $onlyOfficeService = null
    ) {}

    /**
     * Capture an immutable snapshot of a document version before archive registration.
     */
    public function captureSnapshot(ArchiveRequest $request, ?User $actor = null): ArchiveSnapshot
    {
        $document = $request->document()->withTrashed()->firstOrFail();
        $version = $request->requestedVersion ?: $document->displayVersion();

        if (!$version) {
            throw new \InvalidArgumentException("No document version available to snapshot for request #{$request->id}");
        }

        // Rotate ONLYOFFICE document key if available to terminate any concurrent editor session
        if ($this->onlyOfficeService) {
            try {
                $this->onlyOfficeService->rotateDocumentKey($document, $version);
            } catch (\Throwable $e) {
                // Key rotation warning non-fatal if service not configured
            }
        }

        $diskName = config('onlyoffice.storage_disk', 'local');
        $disk = Storage::disk($diskName);

        $companyId = $request->company_id ?: ($document->company_id ?: 1);
        $year = now()->format('Y');
        $uuid = (string) Str::uuid();

        $isHtmlSnapshot = false;
        $htmlContent = null;
        $fileBytes = null;
        $originalFilename = $version->file_original_name ?: ($document->title . '.docx');
        $mimeType = $version->file_mime ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

        if ($version->file_path && $disk->exists($version->file_path)) {
            $fileBytes = $disk->get($version->file_path);
            $extension = pathinfo($version->file_path, PATHINFO_EXTENSION) ?: 'docx';
        } elseif (!empty($version->content)) {
            $isHtmlSnapshot = true;
            $htmlContent = $this->buildCanonicalHtmlSnapshot($document, $version);
            $fileBytes = $htmlContent;
            $originalFilename = ($document->title ?: 'dokumen') . '.html';
            $mimeType = 'text/html';
            $extension = 'html';
        } else {
            throw new \RuntimeException("Neither file nor content available to archive for version #{$version->id}");
        }

        $contentHash = hash('sha256', $fileBytes);
        $fileSize = strlen($fileBytes);
        $objectKey = "archives/{$companyId}/{$year}/{$uuid}.{$extension}";

        // Persist immutable snapshot file into dedicated archives/ directory
        $disk->put($objectKey, $fileBytes);

        if (!$disk->exists($objectKey)) {
            throw new \RuntimeException("Failed to persist immutable snapshot file to {$objectKey}");
        }

        // Verify written bytes
        $writtenBytes = $disk->get($objectKey);
        if (hash('sha256', $writtenBytes) !== $contentHash) {
            $disk->delete($objectKey);
            throw new \RuntimeException("Cryptographic verification failed for persisted snapshot at {$objectKey}");
        }

        // Assemble comprehensive cryptographic manifest payload
        $manifestPayload = $this->buildManifestPayload($document, $version, $request, $contentHash, $fileSize, $actor);
        $manifestHash = hash('sha256', json_encode($manifestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $snapshot = ArchiveSnapshot::create([
            'archive_request_id' => $request->id,
            'storage_disk' => $diskName,
            'object_key' => $objectKey,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'content_hash_sha256' => $contentHash,
            'manifest_hash_sha256' => $manifestHash,
            'manifest_payload' => $manifestPayload,
            'is_html_snapshot' => $isHtmlSnapshot,
            'html_content' => $htmlContent,
            'copied_at' => now(),
            'validated_at' => now(),
            'last_verified_at' => now(),
            'integrity_status' => ArchiveSnapshot::INTEGRITY_VALID,
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_SNAPSHOT_CAPTURED,
            actor: $actor,
            archiveRequest: $request,
            reason: 'Immutable archive snapshot captured and verified.',
            afterState: [
                'snapshot_id' => $snapshot->id,
                'object_key' => $objectKey,
                'content_hash' => $contentHash,
                'file_size' => $fileSize,
            ]
        );

        return $snapshot;
    }

    /**
     * Perform on-demand cryptographic integrity verification of an archived record.
     */
    public function verifyIntegrity(ArchiveEntry $entry, ?User $actor = null): array
    {
        $snapshot = $entry->snapshot;

        if (!$snapshot) {
            return [
                'status' => 'missing',
                'passed' => false,
                'message' => __('No archive snapshot associated with this entry.'),
                'recorded_hash' => null,
                'computed_hash' => null,
            ];
        }

        if ($entry->isDisposed()) {
            return [
                'status' => ArchiveSnapshot::INTEGRITY_DISPOSED,
                'passed' => true,
                'message' => __('Archive record has been lawfully disposed of pursuant to disposition certificate.'),
                'recorded_hash' => $snapshot->content_hash_sha256,
                'computed_hash' => null,
            ];
        }

        $disk = Storage::disk($snapshot->storage_disk);

        if (!$snapshot->object_key || !$disk->exists($snapshot->object_key)) {
            $snapshot->update([
                'integrity_status' => ArchiveSnapshot::INTEGRITY_MISSING,
                'last_verified_at' => now(),
            ]);

            $this->auditService->log(
                eventType: ArchiveEvent::EVENT_INTEGRITY_VERIFIED,
                actor: $actor,
                archiveEntry: $entry,
                reason: 'Snapshot file missing from storage disk during integrity verification.',
                afterState: ['integrity_status' => ArchiveSnapshot::INTEGRITY_MISSING]
            );

            return [
                'status' => ArchiveSnapshot::INTEGRITY_MISSING,
                'passed' => false,
                'message' => __('Snapshot object was not found on storage disk.'),
                'recorded_hash' => $snapshot->content_hash_sha256,
                'computed_hash' => null,
            ];
        }

        $bytes = $disk->get($snapshot->object_key);
        $computedHash = hash('sha256', $bytes);

        $passed = hash_equals($snapshot->content_hash_sha256, $computedHash);
        $status = $passed ? ArchiveSnapshot::INTEGRITY_VALID : ArchiveSnapshot::INTEGRITY_MISMATCH;

        $snapshot->update([
            'integrity_status' => $status,
            'last_verified_at' => now(),
        ]);

        $this->auditService->log(
            eventType: ArchiveEvent::EVENT_INTEGRITY_VERIFIED,
            actor: $actor,
            archiveEntry: $entry,
            reason: $passed ? 'Cryptographic SHA-256 integrity check passed.' : 'Cryptographic SHA-256 MISMATCH DETECTED!',
            afterState: [
                'integrity_status' => $status,
                'recorded_hash' => $snapshot->content_hash_sha256,
                'computed_hash' => $computedHash,
            ]
        );

        return [
            'status' => $status,
            'passed' => $passed,
            'message' => $passed
                ? __('Integrity verified: SHA-256 bitwise match with immutable registration record.')
                : __('Integrity violation: Current file checksum does not match the registered snapshot hash!'),
            'recorded_hash' => $snapshot->content_hash_sha256,
            'computed_hash' => $computedHash,
            'verified_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Build canonical HTML document content for rich-text records without docx files.
     */
    protected function buildCanonicalHtmlSnapshot(Document $document, DocumentVersion $version): string
    {
        $title = e($document->title);
        $number = e($document->document_number ?? 'DRAFT');
        $date = now()->format('Y-m-d H:i:s');
        $body = $version->content ?? '';

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{$title} - Official Archive Record</title>
    <style>
        body { font-family: 'Times New Roman', serif; margin: 2.5cm; color: #111; line-height: 1.6; }
        .archive-seal { border: 2px solid #333; padding: 12px; margin-bottom: 24px; font-size: 11pt; background: #fafafa; }
        .archive-seal h4 { margin: 0 0 6px 0; text-transform: uppercase; font-size: 12pt; letter-spacing: 1px; }
        .content { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="archive-seal">
        <h4>Dokumen Arsip Resmi DokuFlow</h4>
        <div><strong>Nomor Dokumen Asli:</strong> {$number}</div>
        <div><strong>Judul:</strong> {$title}</div>
        <div><strong>Versi Sumber:</strong> v{$version->version_number}</div>
        <div><strong>Tanggal Snapshot:</strong> {$date}</div>
    </div>
    <div class="content">
        {$body}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Assemble cryptographic metadata manifest for archival evidence.
     */
    protected function buildManifestPayload(
        Document $document,
        DocumentVersion $version,
        ArchiveRequest $request,
        string $contentHash,
        int $fileSize,
        ?User $actor
    ): array {
        // Collect digital signature requests for this document
        $signatures = SignatureRequest::where('document_id', $document->id)
            ->with('targetUser:id,name,email,nip')
            ->get()
            ->map(fn($sig) => [
                'target_user_id' => $sig->target_user_id,
                'target_user_name' => $sig->targetUser?->name,
                'target_user_nip' => $sig->targetUser?->nip,
                'status' => $sig->status,
                'is_used' => (bool) $sig->is_used,
                'used_at' => $sig->used_at?->toIso8601String(),
                'preset_position' => $sig->preset_position,
            ])->toArray();

        // Collect approval steps
        $approvalSteps = $version->approvalSteps()
            ->with(['assignedUser:id,name,system_role', 'actionBy:id,name,system_role'])
            ->orderBy('step_order')
            ->get()
            ->map(fn($st) => [
                'step_order' => $st->step_order,
                'step_number' => $st->step_order,
                'step_name' => $st->step_name,
                'step_type' => $st->step_type,
                'assigned_user_id' => $st->assigned_user_id,
                'assigned_user_name' => $st->assignedUser?->name,
                'action_by_id' => $st->action_by_id,
                'action_by_name' => $st->actionBy?->name,
                'user_id' => $st->action_by_id ?: $st->assigned_user_id,
                'user_name' => $st->actionBy?->name ?: $st->assignedUser?->name,
                'user_role' => $st->actionBy?->system_role ?: ($st->assignedUser?->system_role ?: $st->assigned_role),
                'status' => $st->status,
                'action_at' => $st->action_at?->toIso8601String(),
                'reviewed_at' => $st->action_at?->toIso8601String(),
                'review_notes' => $st->notes,
            ])->toArray();

        return [
            'archive_request_id' => $request->id,
            'source_document' => [
                'id' => $document->id,
                'document_number' => $document->document_number,
                'title' => $document->title,
                'format_choice' => $document->format_choice,
                'created_at' => $document->created_at?->toIso8601String(),
                'owner_id' => $document->owner_id,
                'owner_name' => $document->owner?->name,
                'company' => [
                    'id' => $document->company_id,
                    'name' => $document->company?->name,
                    'code' => $document->company?->code,
                ],
                'branch' => [
                    'id' => $document->branch_id,
                    'name' => $document->branch?->name,
                    'code' => $document->branch?->code ?: $document->branch?->effective_code,
                ],
                'unit_kerja' => [
                    'id' => $document->unit_kerja_id,
                    'name' => $document->unitKerja?->nama_unit_kerja,
                    'code' => $document->unitKerja?->kode_unit_kerja,
                ],
            ],
            'source_version' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'status' => $version->status,
                'author_id' => $version->author_id,
                'author_name' => $version->author_name ?: $version->author?->name,
                'reviewer_id' => $version->reviewer_id,
                'reviewed_at' => $version->reviewed_at?->toIso8601String(),
                'original_filename' => $version->file_original_name,
                'file_mime' => $version->file_mime,
            ],
            'approval_steps' => $approvalSteps,
            'signatures' => $signatures,
            'content_verification' => [
                'sha256_hash' => $contentHash,
                'file_size' => $fileSize,
                'captured_at' => now()->toIso8601String(),
                'captured_by_user_id' => $actor?->id,
            ],
        ];
    }
}

