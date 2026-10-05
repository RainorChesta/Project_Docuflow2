<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Notifications\DocumentExpiredNotification;
use App\Notifications\DocumentExpiringWarningNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-document-expiration')]
#[Description('Check documents with explicit expiration dates and notify document owners')]
class CheckDocumentExpiration extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting document expiration check...');

        $expiredCount = 0;
        $warning7Count = 0;
        $warning1Count = 0;
        $warningDefaultCount = 0;

        // Query released documents that are not trashed and have an explicit expiration date
        Document::withoutTrashed()
            ->whereNotNull('expiration_date')
            ->with(['owner', 'currentVersion', 'versions'])
            ->chunk(50, function ($documents) use (&$expiredCount, &$warning7Count, &$warning1Count, &$warningDefaultCount) {
                foreach ($documents as $document) {
                    $statusBefore = $document->expiration_notif_status;
                    $document->checkExpirationNotification();
                    
                    if ($document->expiration_notif_status !== $statusBefore) {
                        if ($document->expiration_notif_status === 'expired') $expiredCount++;
                        elseif ($document->expiration_notif_status === '1day') $warning1Count++;
                        elseif ($document->expiration_notif_status === '7days') $warning7Count++;
                        else $warningDefaultCount++;
                    }
                }
            });

        $this->info("Expiration check finished.");
        $this->table(
            ['Status', 'Jumlah Dokumen'],
            [
                ['Expired (Telah Kadaluwarsa)', $expiredCount],
                ['Urgent Warning (H-1 / Hari-H)', $warning1Count],
                ['Warning H-7', $warning7Count],
                ['Pengingat Periode Default/Khusus', $warningDefaultCount],
            ]
        );

        return Command::SUCCESS;
    }
}
