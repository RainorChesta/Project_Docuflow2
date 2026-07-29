<?php

namespace App\Console\Commands;

use App\Models\DocumentVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteExpiredVersions extends Command
{
    protected $signature = 'versions:purge {--days= : Override retention days}';
    protected $description = 'Delete historical document versions older than retention period';

    public function handle(): int
    {
        $days = $this->option('days') ?? config('app.version_retention_days', 365);

        $retentionDate = now()->subDays((int) $days);

        $deleted = DocumentVersion::where('status', '!=', 'active')
            ->where('created_at', '<', $retentionDate)
            ->delete();

        DB::table('audit_logs')->insert([
            'user_id' => null,
            'action' => 'system.versions_purged',
            'target_type' => 'document_version',
            'target_id' => 0,
            'metadata' => json_encode(['retention_days' => $days, 'deleted_count' => $deleted]),
            'created_at' => now(),
        ]);

        $this->info("Purged {$deleted} expired document versions.");

        return Command::SUCCESS;
    }
}
