<?php

namespace App\Services;

use App\Models\ArchiveEntry;
use App\Models\ArchiveRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Cache;

class ArchiveEligibilityService
{
    /**
     * Evaluate preflight eligibility for archiving a document and version.
     */
    public function checkEligibility(Document $document, ?DocumentVersion $version = null): array
    {
        $version = $version ?? $document->displayVersion();
        $reasons = [];
        $checks = [];

        // 1. Document Trash Status
        $notTrashed = !$document->trashed();
        $checks['not_trashed'] = [
            'label' => __('Document is not in trash'),
            'passed' => $notTrashed,
        ];
        if (!$notTrashed) {
            $reasons[] = __('Document is currently in trash.');
        }

        // 2. Version Existence & Finalization Status
        $versionExists = (bool) $version;
        $isVersionActive = $version && $version->status === 'active' && is_null($version->discarded_at);
        $checks['version_finalized'] = [
            'label' => __('Document version is finalized and active'),
            'passed' => $versionExists && $isVersionActive,
            'details' => $version ? "v{$version->version_number} ({$version->status})" : null,
        ];
        if (!$versionExists) {
            $reasons[] = __('Document has no readable version.');
        } elseif (!$isVersionActive) {
            $reasons[] = __('Only finalized and active document versions can be archived (current status: :status).', ['status' => $version->status]);
        }

        // 3. ONLYOFFICE Active Session Lock
        $cacheKey = 'onlyoffice_active_' . $document->id;
        $hasActiveEditor = Cache::has($cacheKey);
        $checks['editor_idle'] = [
            'label' => __('No active document editor sessions'),
            'passed' => !$hasActiveEditor,
        ];
        if ($hasActiveEditor) {
            $reasons[] = __('The document is currently opened for editing in ONLYOFFICE. Close all editor sessions first.');
        }

        // 4. Pending Approvals
        $hasPendingApprovals = false;
        if ($version) {
            $hasPendingApprovals = $version->approvalSteps()
                ->where('status', 'pending')
                ->exists();
        }
        $checks['no_pending_approvals'] = [
            'label' => __('No pending approval steps'),
            'passed' => !$hasPendingApprovals,
        ];
        if ($hasPendingApprovals) {
            $reasons[] = __('Document version has approval steps that are still pending.');
        }

        // 5. Unresolved Digital Signatures
        $hasPendingSignatures = \App\Models\SignatureRequest::where('document_id', $document->id)
            ->where('is_used', false)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'pending');
            })
            ->exists();
        $checks['no_pending_signatures'] = [
            'label' => __('No pending digital signatures'),
            'passed' => !$hasPendingSignatures,
        ];
        if ($hasPendingSignatures) {
            $reasons[] = __('Document has digital signature requests that have not been completed.');
        }

        // 6. Existing Registered Archive Check
        $alreadyArchived = false;
        if ($version) {
            $alreadyArchived = ArchiveEntry::where('document_version_id', $version->id)
                ->where('status', '!=', ArchiveEntry::STATUS_VOIDED)
                ->exists();
        }
        $checks['not_already_archived'] = [
            'label' => __('Version is not already registered in archive'),
            'passed' => !$alreadyArchived,
        ];
        if ($alreadyArchived) {
            $reasons[] = __('This document version has already been registered in the archive.');
        }

        // 7. Active In-Flight Archive Request
        $hasInFlightRequest = false;
        if ($version) {
            $hasInFlightRequest = ArchiveRequest::where('document_id', $document->id)
                ->where('requested_version_id', $version->id)
                ->whereIn('state', [
                    ArchiveRequest::STATE_DRAFT,
                    ArchiveRequest::STATE_PENDING_APPROVAL,
                    ArchiveRequest::STATE_APPROVED,
                    ArchiveRequest::STATE_PREPARING_SNAPSHOT,
                    ArchiveRequest::STATE_READY_TO_REGISTER,
                ])
                ->exists();
        }
        $checks['no_in_flight_request'] = [
            'label' => __('No active archive requests in progress'),
            'passed' => !$hasInFlightRequest,
        ];
        if ($hasInFlightRequest) {
            $reasons[] = __('An archive request for this version is already pending or in progress.');
        }

        $eligible = empty($reasons);

        return [
            'eligible' => $eligible,
            'reasons' => $reasons,
            'checks' => $checks,
            'document' => $document,
            'version' => $version,
        ];
    }
}

