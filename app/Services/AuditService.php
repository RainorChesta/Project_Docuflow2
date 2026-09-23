<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Collection;

class AuditService
{
    public function log(?User $user, string $action, string $targetType, int $targetId, ?array $metadata = null): AuditLog
    {
        $metadata = $metadata ?? [];
        if (!isset($metadata['ip_address']) && request()) {
            $metadata['ip_address'] = request()->ip();
        }
        if (!isset($metadata['user_agent']) && request()) {
            $metadata['user_agent'] = request()->userAgent();
        }

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Build a unified, consolidated audit trail timeline for a document.
     */
    public function getDocumentAuditTrail(Document $document): Collection
    {
        $document->loadMissing([
            'owner.unitKerja',
            'unitKerja',
            'branch.company',
            'versions.author.unitKerja',
            'versions.reviewer.unitKerja',
            'versions.approvalSteps.actionBy.unitKerja',
            'versions.approvalSteps.assignedUser.unitKerja',
            'versions.approvalSteps.signatureRequest.targetUser.unitKerja',
            'rollbackRequestedBy.unitKerja',
            'pendingRollbackVersion',
        ]);

        $versionIds = $document->versions->pluck('id')->toArray();

        // 1. Fetch system AuditLog records
        $logs = AuditLog::with('user.unitKerja')
            ->where(function ($q) use ($document, $versionIds) {
                $q->where(function ($sub) use ($document) {
                    $sub->where('target_type', 'document')
                        ->where('target_id', $document->id);
                })->orWhere(function ($sub) use ($versionIds) {
                    $sub->where('target_type', 'document_version')
                        ->whereIn('target_id', $versionIds);
                });
            })
            ->get();

        $events = collect();

        // Helper to find log IP/UserAgent
        $findLogMeta = function (string $actionKeyword, ?int $versionId = null, ?int $userId = null) use ($logs) {
            return $logs->first(function ($l) use ($actionKeyword, $versionId, $userId) {
                $matchAction = str_contains($l->action, $actionKeyword);
                $matchUser = $userId ? ($l->user_id === $userId) : true;
                $matchVersion = $versionId ? (($l->target_type === 'document_version' && $l->target_id === $versionId) || ($l->metadata['version_id'] ?? null) == $versionId) : true;
                return $matchAction && $matchUser && $matchVersion;
            });
        };

        // 2. Single Consolidated Document Creation Entry (v1.0)
        $docCreateLog = $logs->firstWhere('action', 'document.created') ?? $findLogMeta('created', null, $document->created_by);
        $events->push([
            'id' => 'doc_create_' . $document->id,
            'source' => 'document',
            'action' => 'document.created',
            'timestamp' => $document->created_at,
            'actor' => $document->owner,
            'actor_name' => $document->owner?->name ?? ($docCreateLog->metadata['author_name'] ?? 'Pembuat Dokumen'),
            'actor_role' => $document->owner?->system_role ?? 'Staff',
            'actor_unit' => $document->owner?->unitKerja?->nama_unit_kerja ?? $document->unitKerja?->nama_unit_kerja,
            'ip_address' => $docCreateLog?->metadata['ip_address'] ?? null,
            'user_agent' => $docCreateLog?->metadata['user_agent'] ?? null,
            'version_number' => '1.0',
            'notes' => null,
            'metadata' => [
                'document_id' => $document->id,
            ],
        ]);

        // 3. Process Versions (Subsequent versions v2, v3... and Approval Steps)
        foreach ($document->versions as $version) {
            $isFirstVersion = ($version->version_number == '1.0' || $version->version_number == '1');

            // For subsequent versions (v > 1), record version submission
            if (!$isFirstVersion) {
                $verLog = $findLogMeta('version.', $version->id, $version->created_by);
                $isRename = $version->isRename();
                $isExplicitUpload = ($verLog && $verLog->action === 'version.uploaded') || ($verLog && ($verLog->metadata['source'] ?? null) === 'file_upload');

                // Check if this version was created as a revision following a rejected version
                $isRevisionAfterReject = $document->versions
                    ->where('version_number', '<', $version->version_number)
                    ->where('status', 'rejected')
                    ->isNotEmpty();

                $action = match (true) {
                    $isRename => 'document.rename_requested',
                    $isExplicitUpload => 'version.uploaded',
                    $isRevisionAfterReject => 'version.revision_submitted',
                    default => 'version.created',
                };

                $events->push([
                    'id' => 'ver_create_' . $version->id,
                    'source' => 'version',
                    'action' => $action,
                    'timestamp' => $version->created_at,
                    'actor' => $version->author,
                    'actor_name' => $version->author_name ?? $version->author?->name ?? 'Pembuat Revisi',
                    'actor_role' => $version->author?->system_role ?? 'Staff',
                    'actor_unit' => $version->author?->unitKerja?->nama_unit_kerja ?? $document->unitKerja?->nama_unit_kerja,
                    'ip_address' => $verLog?->metadata['ip_address'] ?? null,
                    'user_agent' => $verLog?->metadata['user_agent'] ?? null,
                    'version_number' => $version->version_number,
                    'notes' => $version->change_summary ?? null,
                    'metadata' => [
                        'version_id' => $version->id,
                        'is_rename' => $isRename,
                        'is_upload' => $isExplicitUpload,
                        'is_revision' => $isRevisionAfterReject,
                        'old_title' => $version->old_title,
                        'file_original_name' => $isExplicitUpload ? $version->file_original_name : null,
                    ],
                ]);
            }

            // Approval Steps (Approved, Rejected, Bypassed)
            foreach ($version->approvalSteps as $step) {
                if ($step->action_at) {
                    $stepActor = $step->actionBy ?? $step->assignedUser;
                    $stepLog = $findLogMeta($step->status, $version->id, $stepActor?->id);

                    $events->push([
                        'id' => 'step_action_' . $step->id,
                        'source' => 'approval_step',
                        'action' => 'approval_step.' . $step->status,
                        'timestamp' => $step->action_at,
                        'actor' => $stepActor,
                        'actor_name' => $stepActor?->name ?? 'Approver',
                        'actor_role' => $stepActor?->system_role ?? $step->assigned_role,
                        'actor_unit' => $stepActor?->unitKerja?->nama_unit_kerja,
                        'ip_address' => $stepLog?->metadata['ip_address'] ?? null,
                        'user_agent' => $stepLog?->metadata['user_agent'] ?? null,
                        'version_number' => $version->version_number,
                        'notes' => $step->notes,
                        'metadata' => [
                            'step_name' => $step->step_name,
                            'step_type' => $step->step_type,
                            'step_order' => $step->step_order,
                            'status' => $step->status,
                            'signature_request_id' => $step->signature_request_id,
                        ],
                    ]);
                }
            }

            // Version level rejection if no step was recorded
            if ($version->status === 'rejected' && $version->reviewed_at && $version->approvalSteps->where('status', 'rejected')->isEmpty()) {
                $revLog = $findLogMeta('rejected', $version->id, $version->reviewed_by);
                $events->push([
                    'id' => 'ver_reject_' . $version->id,
                    'source' => 'version',
                    'action' => 'version.rejected',
                    'timestamp' => $version->reviewed_at,
                    'actor' => $version->reviewer,
                    'actor_name' => $version->reviewer?->name ?? 'Reviewer',
                    'actor_role' => $version->reviewer?->system_role ?? 'Reviewer',
                    'actor_unit' => $version->reviewer?->unitKerja?->nama_unit_kerja,
                    'ip_address' => $revLog?->metadata['ip_address'] ?? null,
                    'user_agent' => $revLog?->metadata['user_agent'] ?? null,
                    'version_number' => $version->version_number,
                    'notes' => $version->notes,
                    'metadata' => [
                        'version_id' => $version->id,
                    ],
                ]);
            }
        }

        // 4. Document-Level Special Events (Rollbacks & Renames)
        foreach ($logs as $log) {
            $action = $log->action;
            if (in_array($action, ['rollback.requested', 'rollback.approved', 'rollback.rejected', 'document.rename_approved', 'document.rename_rejected'])) {
                $meta = $log->metadata ?? [];
                $events->push([
                    'id' => 'log_' . $log->id,
                    'source' => 'audit_log',
                    'action' => $action,
                    'timestamp' => $log->created_at,
                    'actor' => $log->user,
                    'actor_name' => $log->user?->name ?? 'System',
                    'actor_role' => $log->user?->system_role ?? 'User',
                    'actor_unit' => $log->user?->unitKerja?->nama_unit_kerja,
                    'ip_address' => $meta['ip_address'] ?? null,
                    'user_agent' => $meta['user_agent'] ?? null,
                    'version_number' => $meta['version_number'] ?? null,
                    'notes' => $meta['notes'] ?? ($meta['reason'] ?? null),
                    'metadata' => $meta,
                ]);
            }
        }

        // 5. Clean, sort descending, and remove duplicate entries
        return $events
            ->filter(fn($e) => !empty($e['timestamp']))
            ->sortByDesc('timestamp')
            ->unique(function ($item) {
                $timeKey = $item['timestamp'] ? $item['timestamp']->format('Y-m-d H:i') : '';
                return $item['action'] . '_' . ($item['version_number'] ?? '') . '_' . ($item['actor_name'] ?? '') . '_' . $timeKey;
            })
            ->values();
    }

    /**
     * Parse raw User-Agent header string into a clean, human-readable Browser & OS name.
     */
    public static function parseDevice(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        $os = 'Unknown OS';
        if (stripos($userAgent, 'windows nt 10.0') !== false || stripos($userAgent, 'windows nt 11.0') !== false) {
            $os = 'Windows';
        } elseif (stripos($userAgent, 'windows nt 6.3') !== false || stripos($userAgent, 'windows nt 6.2') !== false || stripos($userAgent, 'windows nt 6.1') !== false) {
            $os = 'Windows';
        } elseif (stripos($userAgent, 'iphone') !== false) {
            $os = 'iOS (iPhone)';
        } elseif (stripos($userAgent, 'ipad') !== false) {
            $os = 'iOS (iPad)';
        } elseif (stripos($userAgent, 'macintosh') !== false || stripos($userAgent, 'mac os x') !== false) {
            $os = 'macOS';
        } elseif (stripos($userAgent, 'android') !== false) {
            $os = 'Android';
        } elseif (stripos($userAgent, 'linux') !== false) {
            $os = 'Linux';
        }

        $browser = 'Browser';
        if (stripos($userAgent, 'edg/') !== false || stripos($userAgent, 'edge/') !== false) {
            $browser = 'Edge';
        } elseif (stripos($userAgent, 'opr/') !== false || stripos($userAgent, 'opera') !== false) {
            $browser = 'Opera';
        } elseif (stripos($userAgent, 'chrome/') !== false || stripos($userAgent, 'crios/') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($userAgent, 'firefox/') !== false || stripos($userAgent, 'fxios/') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($userAgent, 'safari/') !== false && stripos($userAgent, 'chrome/') === false) {
            $browser = 'Safari';
        }

        return "{$browser} • {$os}";
    }
}
