<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneTrashedDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:prune-trashed 
                            {--days=30 : Force delete documents soft-deleted older than specified days} 
                            {--all : Force delete all soft-deleted documents immediately}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force delete soft-deleted documents older than 30 days (or all soft-deleted documents)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $all = $this->option('all');
        $days = (int) $this->option('days');

        $query = Document::onlyTrashed();

        if (!$all) {
            $cutoffDate = now()->subDays($days);
            $query->where('deleted_at', '<=', $cutoffDate);
            $this->info("Scanning for soft-deleted documents older than {$days} days (deleted before {$cutoffDate->toDateTimeString()})...");
        } else {
            $this->info("Scanning all soft-deleted documents...");
        }

        $trashedDocuments = $query->get();
        $count = $trashedDocuments->count();

        if ($count === 0) {
            $this->info("No soft-deleted documents found to delete.");
            return Command::SUCCESS;
        }

        $this->output->progressStart($count);

        foreach ($trashedDocuments as $document) {
            foreach ($document->versions as $version) {
                if ($version->file_path) {
                    Storage::disk('local')->delete($version->file_path);
                }
            }
            $document->versions()->delete();
            $document->forceDelete();

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("Successfully force deleted {$count} soft-deleted document(s).");

        return Command::SUCCESS;
    }
}
