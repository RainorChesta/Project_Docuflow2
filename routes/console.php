<?php

use App\Console\Commands\DeleteExpiredVersions;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;

use App\Console\Commands\CheckDocumentExpiration;
use App\Console\Commands\PruneTrashedDocuments;

Schedule::command(DeleteExpiredVersions::class)->daily();
Schedule::command(CheckDocumentExpiration::class)->daily();
Schedule::command(PruneTrashedDocuments::class)->daily();

// Retain the original inspire command for reference
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dokuflow:clean', function () {
    $this->info("Starting DokuFlow cleanup...");

    // 1. Ensure columns exist on documents table
    if (!\Illuminate\Support\Facades\Schema::hasColumn('documents', 'pending_title')) {
        \Illuminate\Support\Facades\Schema::table('documents', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('pending_title')->nullable();
            $table->unsignedBigInteger('rename_requested_by_id')->nullable();
            $table->timestamp('rename_requested_at')->nullable();
            $table->text('rename_request_notes')->nullable();
        });
        $this->info("Created missing columns on documents table.");

        \Illuminate\Support\Facades\DB::table('migrations')->insertOrIgnore([
            'migration' => '2026_09_01_000002_add_rename_request_columns_to_documents_table',
            'batch' => 99,
        ]);
    }

    // 2. Reset pending rename columns in documents table
    $affected = \Illuminate\Support\Facades\DB::table('documents')->update([
        'pending_title' => null,
        'rename_requested_by_id' => null,
        'rename_requested_at' => null,
        'rename_request_notes' => null,
    ]);
    $this->info("Reset pending rename fields on {$affected} documents to NULL.");

    // 3. Delete any rename request notifications
    $deleted = \Illuminate\Support\Facades\DB::table('notifications')
        ->where('type', 'like', '%Rename%')
        ->orWhere('data', 'like', '%rename_%')
        ->delete();
    $this->info("Deleted {$deleted} rename notifications.");

    // 4. Delete temporary storage files (temp_doc_*)
    $tempDocs = glob(storage_path('app/temp_*'));
    foreach ($tempDocs as $f) {
        if (is_file($f)) {
            unlink($f);
            $this->line("Deleted temp storage file: " . basename($f));
        }
    }

    // 5. Delete export pdfs
    $exports = glob(storage_path('app/private/exports/*.pdf'));
    foreach ($exports as $f) {
        if (is_file($f)) {
            unlink($f);
            $this->line("Deleted export file: " . basename($f));
        }
    }

    // 6. Delete scratch files in project root
    $scratch = glob(base_path('{check_*,quick_*,clean_*,documents_schema*}.*'), GLOB_BRACE);
    foreach ($scratch as $sf) {
        if (is_file($sf)) {
            unlink($sf);
            $this->line("Deleted scratch file: " . basename($sf));
        }
    }

    // 7. Clear view and app cache
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    $this->info("Cleared view and app cache.");

    $remaining = \App\Models\Document::whereNotNull('pending_title')->count();
    $this->info("Cleanup finished! Documents with pending rename remaining: {$remaining}");
});
