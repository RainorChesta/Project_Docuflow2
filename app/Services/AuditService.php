<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditService
{
    public function log(?User $user, string $action, string $targetType, int $targetId, ?array $metadata = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
