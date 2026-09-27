<?php

namespace App\Console\Commands;

use App\Models\CorporateSoftFile;
use App\Models\DocumentTemplate;
use App\Services\OnlyOfficeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ConvertCorporateSoftFilesToF4Command extends Command
{
    protected $signature = 'softfiles:convert-f4 {--force : Konversi ulang semua berkas tanpa memeriksa}';
    protected $description = 'Konversi otomatis seluruh file Corporate Soft File dan Template yang ada ke standar ukuran F4 (21 x 33 cm)';

    public function handle(OnlyOfficeService $onlyOfficeService): int
    {
        $this->info("==================================================");
        $this->info("   DocuFlow - Batch Convert Soft Files to F4      ");
        $this->info("==================================================");

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        // 1. Corporate Soft Files
        $softFiles = CorporateSoftFile::all();
        $this->line("Memeriksa <comment>{$softFiles->count()}</comment> Corporate Soft Files...");

        $convertedCount = 0;
        foreach ($softFiles as $sf) {
            if (!$sf->file_path || !$disk->exists($sf->file_path)) {
                $this->warn("  - [SKIP] File #{$sf->id} ({$sf->title}) tidak ditemukan di storage: {$sf->file_path}");
                continue;
            }

            $rawContent = $disk->get($sf->file_path);
            $ext = strtolower(pathinfo($sf->file_original_name ?: $sf->file_path, PATHINFO_EXTENSION));

            $f4Content = $onlyOfficeService->convertFileToF4($rawContent, $ext);

            if ($f4Content !== $rawContent) {
                $disk->put($sf->file_path, $f4Content);
                $sf->file_size = strlen($f4Content);
                $sf->save();
                $this->info("  - [CONVERTED] Soft File #{$sf->id} ({$sf->title}) berhasil dikonversi ke F4.");
                $convertedCount++;
            } else {
                $this->line("  - [OK] Soft File #{$sf->id} ({$sf->title}) sudah dalam ukuran F4.");
            }
        }

        $this->newLine();

        // 2. Document Templates
        $templates = DocumentTemplate::all();
        $this->line("Memeriksa <comment>{$templates->count()}</comment> Document Templates...");

        $tmplConvertedCount = 0;
        foreach ($templates as $tmpl) {
            if (!$tmpl->file_path || !$disk->exists($tmpl->file_path)) {
                continue;
            }

            $rawContent = $disk->get($tmpl->file_path);
            $ext = strtolower(pathinfo($tmpl->file_original_name ?: $tmpl->file_path, PATHINFO_EXTENSION));

            $f4Content = $onlyOfficeService->convertFileToF4($rawContent, $ext);

            if ($f4Content !== $rawContent) {
                $disk->put($tmpl->file_path, $f4Content);
                $this->info("  - [CONVERTED] Template #{$tmpl->id} ({$tmpl->title}) berhasil dikonversi ke F4.");
                $tmplConvertedCount++;
            }
        }

        $this->newLine();
        $this->info("Selesai! {$convertedCount} Soft File dan {$tmplConvertedCount} Template berhasil dikonversi ke format F4.");

        return Command::SUCCESS;
    }
}
