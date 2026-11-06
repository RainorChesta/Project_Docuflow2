<?php

namespace App\Console\Commands;

use App\Models\ArchiveEntry;
use App\Services\ArchiveSnapshotService;
use Illuminate\Console\Command;

class VerifyArchiveIntegrityCommand extends Command
{
    protected $signature = 'archives:verify-integrity {--limit=100 : Maximum number of archives to verify}';
    protected $description = 'Cryptographically verify SHA-256 integrity of immutable archive snapshots';

    public function handle(ArchiveSnapshotService $snapshotService): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Verifying archive snapshot integrity (limit: {$limit})...");

        $entries = ArchiveEntry::where('status', '!=', ArchiveEntry::STATUS_DISPOSED)
            ->where('status', '!=', ArchiveEntry::STATUS_VOIDED)
            ->with('snapshot')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $passed = 0;
        $failed = 0;

        foreach ($entries as $entry) {
            $result = $snapshotService->verifyIntegrity($entry);
            if ($result['passed']) {
                $passed++;
            } else {
                $failed++;
                $this->error("FAIL: Archive {$entry->archive_number} - {$result['message']}");
            }
        }

        $this->info("Verification completed: {$passed} valid, {$failed} failed.");
        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

