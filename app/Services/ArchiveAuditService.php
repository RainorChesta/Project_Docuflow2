<?php

namespace App\Services;

use App\Models\ArchiveEntry;
use App\Models\ArchiveEvent;
use App\Models\ArchiveRequest;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class ArchiveAuditService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Log an append-only archive event with cryptographic hash chaining.
     */
    public function log(
        string $eventType,
        ?User $actor = null,
        ?ArchiveEntry $archiveEntry = null,
        ?ArchiveRequest $archiveRequest = null,
        ?string $reason = null,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?array $metadata = null,
        ?int $companyId = null
    ): ArchiveEvent {
        $userCompanyIds = $actor?->allCompanyIds();
        $fallbackCompanyId = is_array($userCompanyIds) ? ($userCompanyIds[0] ?? null) : $userCompanyIds?->first();

        $companyId = $companyId
            ?? $archiveEntry?->company_id
            ?? $archiveRequest?->company_id
            ?? $fallbackCompanyId;

        $occurredAt = now();

        // Retrieve latest event in this company/archive to chain the SHA-256 hash
        $lastEvent = ArchiveEvent::where('company_id', $companyId)
            ->latest('id')
            ->first();

        $previousHash = $lastEvent?->event_hash ?? str_repeat('0', 64);

        $payloadToHash = implode('|', [
            $previousHash,
            $companyId ?? 0,
            $archiveEntry?->id ?? 0,
            $archiveRequest?->id ?? 0,
            $eventType,
            $actor?->id ?? 0,
            $occurredAt->toIso8601String(),
            $reason ?? '',
            json_encode($beforeState ?? []),
            json_encode($afterState ?? []),
            json_encode($metadata ?? []),
        ]);

        $eventHash = hash('sha256', $payloadToHash);

        $event = ArchiveEvent::create([
            'company_id' => $companyId,
            'archive_entry_id' => $archiveEntry?->id,
            'archive_request_id' => $archiveRequest?->id,
            'event_type' => $eventType,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'System',
            'actor_role' => $actor?->system_role ?? 'system',
            'occurred_at' => $occurredAt,
            'reason' => $reason,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'metadata' => $metadata,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent() ?? 'CLI/System',
            'previous_hash' => $previousHash,
            'event_hash' => $eventHash,
        ]);

        // Bridge to existing application AuditService
        $this->auditService->log(
            user: $actor,
            action: 'archive.' . $eventType,
            targetType: $archiveEntry ? 'archive_entry' : 'archive_request',
            targetId: (int) ($archiveEntry?->id ?? $archiveRequest?->id ?? 0),
            metadata: array_merge($metadata ?? [], [
                'archive_number' => $archiveEntry?->archive_number,
                'request_id' => $archiveRequest?->id,
                'reason' => $reason,
            ])
        );

        return $event;
    }
}

