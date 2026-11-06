<?php

namespace App\Console\Commands;

use App\Models\ArchiveEntry;
use App\Services\ArchiveAuditService;
use Illuminate\Console\Command;

class MonitorArchiveRetentionCommand extends Command
{
    protected $signature = 'archives:monitor-retention';
    protected $description = 'Evaluate archive retention schedules and transition entries due for review or disposition';

    public function handle(ArchiveAuditService $auditService): int
    {
        $this->info('Evaluating archive retention schedules...');

        $now = now()->toDateString();

        // 1. Registered archives whose active retention period has ended -> 'under_review'
        $toReview = ArchiveEntry::where('status', ArchiveEntry::STATUS_REGISTERED)
            ->whereNotNull('retention_active_until')
            ->where('retention_active_until', '<=', $now)
            ->where('final_disposition_action', '!=', 'permanent')
            ->get();

        foreach ($toReview as $entry) {
            $entry->update(['status' => ArchiveEntry::STATUS_UNDER_REVIEW]);
            $this->line("Archive {$entry->archive_number} transitioned to under_review");
        }

        // 2. Archives whose total retention has expired and are NOT under legal hold -> 'eligible_for_disposition'
        $eligible = ArchiveEntry::whereIn('status', [ArchiveEntry::STATUS_REGISTERED, ArchiveEntry::STATUS_UNDER_REVIEW])
            ->whereNotNull('retention_due_at')
            ->where('retention_due_at', '<=', $now)
            ->where('is_under_legal_hold', false)
            ->where('final_disposition_action', '!=', 'permanent')
            ->get();

        foreach ($eligible as $entry) {
            $entry->update(['status' => ArchiveEntry::STATUS_ELIGIBLE_FOR_DISPOSITION]);
            $this->line("Archive {$entry->archive_number} is eligible_for_disposition");
        }

        $this->info("Processed: {$toReview->count()} to review, {$eligible->count()} eligible for disposition.");
        return self::SUCCESS;
    }
}

