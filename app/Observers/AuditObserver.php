<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\AuditService;

class AuditObserver
{
    public function __construct(protected AuditService $auditService) {}

    public function created(AuditLog $log): void
    {
        // Placeholder for future logic, e.g. pushing to external SIEM
    }
}
