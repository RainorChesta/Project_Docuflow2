<?php

namespace App\Services;

use App\Jobs\SummarizeDocumentJob;
use App\Models\Branch;
use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class DocumentService
{
    /**
     * Create a minimal blank DOCX file in storage.
     */
    public function createBlankDocx(int $documentId, int $versionNumber = 1): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'pageSizeW' => 11906, // A4 in twips
            'pageSizeH' => 16838,
            'marginTop' => 1440,  // 1 inch
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);
        $section->addText(' ', ['name' => 'Arial', 'size' => 11]);

        $relativeDir = 'documents/' . $documentId;
        $fileName = 'v' . $versionNumber . '.docx';
        $relativeFilePath = $relativeDir . '/' . $fileName;

        $tempPath = tempnam(sys_get_temp_dir(), 'docx_');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        Storage::disk(config('onlyoffice.storage_disk', 'local'))
            ->put($relativeFilePath, file_get_contents($tempPath));

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        return $relativeFilePath;
    }

    /**
     * Generate the final, authoritative document number. Locks the row
     * range to avoid duplicate sequences under concurrent submissions.
     */
    public function generateId(
        $arg1 = null,
        $arg2 = null,
        $arg3 = null,
        $arg4 = null,
        $arg5 = null
    ): string {
        [$documentType, $branch, $unitKerja] = $this->resolveArguments($arg1, $arg2, $arg3, $arg4, $arg5);

        return DB::transaction(function () use ($documentType, $branch, $unitKerja) {
            $year = now()->year;
            $branchId = $branch?->id;
            $typeId = $documentType?->id;

            $query = Document::withTrashed()
                ->where('document_type_id', $typeId)
                ->whereYear('created_at', $year);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Lock the highest sequence row to serialize increments safely
            $maxDoc = (clone $query)->lockForUpdate()->orderByDesc('id')->first();

            $seq = 1;
            if ($maxDoc && $maxDoc->document_number) {
                $parts = explode('/', $maxDoc->document_number);
                if (!empty($parts[0]) && is_numeric($parts[0])) {
                    $seq = (int) $parts[0] + 1;
                } else {
                    $seq = (clone $query)->count() + 1;
                }
            }

            $typeCode = $documentType ? $documentType->code : 'DOC';
            $branchCode = $branch ? $branch->effective_code : 'PST';
            $romanMonth = $this->toRoman(now()->month);

            $formattedNumber = $this->formatNumber(
                $seq,
                $documentType,
                $branchCode,
                $romanMonth,
                $year,
                $unitKerja
            );

            // Safety check against collisions
            $attempt = 0;
            while (Document::withTrashed()->where('document_number', $formattedNumber)->exists()) {
                $attempt++;
                $seq++;
                $formattedNumber = $this->formatNumber(
                    $seq,
                    $documentType,
                    $branchCode,
                    $romanMonth,
                    $year,
                    $unitKerja
                );
                if ($attempt > 100) {
                    break;
                }
            }

            return $formattedNumber;
        });
    }

    /**
     * Non-locking preview of the next number, purely indicative for the
     * create form.
     */
    public function previewNumber(
        $arg1 = null,
        $arg2 = null,
        $arg3 = null,
        $arg4 = null,
        $arg5 = null
    ): string {
        [$documentType, $branch, $unitKerja] = $this->resolveArguments($arg1, $arg2, $arg3, $arg4, $arg5);

        $year = now()->year;
        $branchId = $branch?->id;
        $typeId = $documentType?->id;

        $query = Document::withTrashed()
            ->where('document_type_id', $typeId)
            ->whereYear('created_at', $year);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $maxDoc = (clone $query)->orderByDesc('id')->first();

        $seq = 1;
        if ($maxDoc && $maxDoc->document_number) {
            $parts = explode('/', $maxDoc->document_number);
            if (!empty($parts[0]) && is_numeric($parts[0])) {
                $seq = (int) $parts[0] + 1;
            } else {
                $seq = (clone $query)->count() + 1;
            }
        }

        $typeCode = $documentType ? $documentType->code : 'DOC';
        $branchCode = $branch ? $branch->effective_code : 'PST';
        $romanMonth = $this->toRoman(now()->month);

        return $this->formatNumber(
            $seq,
            $documentType,
            $branchCode,
            $romanMonth,
            $year,
            $unitKerja
        );
    }

    /**
     * Helper to resolve polymorph/flexible arguments for generateId and previewNumber.
     *
     * @return array{0: ?DocumentType, 1: ?Branch, 2: ?UnitKerja}
     */
    private function resolveArguments(...$args): array
    {
        $documentType = null;
        $branch = null;
        $unitKerja = null;

        foreach ($args as $arg) {
            if ($arg instanceof DocumentType) {
                $documentType = $arg;
            } elseif ($arg instanceof Branch) {
                $branch = $arg;
            } elseif ($arg instanceof UnitKerja) {
                $unitKerja = $arg;
            }
        }

        return [$documentType, $branch, $unitKerja];
    }

    private function toRoman(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$month] ?? 'I';
    }

    /**
     * Format document number according to official rules:
     *
     * 1. Official Correspondence (Naskah Dinas):
     *    sequenceNumber/documentTypeCode/branchCode/month/year
     *
     * 2. Accreditation Documents (Dokumen Akreditasi):
     *    sequenceNumber/documentTypeCode-workUnitCode/branchCode/month/year
     */
    public function formatNumber(
        int $seq,
        ?DocumentType $documentType,
        string $branchCode,
        string $romanMonth,
        int $year,
        ?UnitKerja $unitKerja = null
    ): string {
        $typeCode = $documentType ? $documentType->code : 'DOC';
        $isAkreditasi = $documentType ? $documentType->isAkreditasi() : ($unitKerja !== null);

        if ($isAkreditasi) {
            $unitKerjaCode = $unitKerja ? $unitKerja->kode_unit_kerja : '00';
            return sprintf(
                '%03d/%s-%s/%s/%s/%d',
                $seq,
                $typeCode,
                $unitKerjaCode,
                $branchCode,
                $romanMonth,
                $year
            );
        }

        // Naskah Dinas format (does not include unit kerja code)
        return sprintf(
            '%03d/%s/%s/%s/%d',
            $seq,
            $typeCode,
            $branchCode,
            $romanMonth,
            $year
        );
    }

    public function create(array $data, int $ownerId): Document
    {
        $unitKerja = !empty($data['unit_kerja_id']) ? UnitKerja::find($data['unit_kerja_id']) : null;
        $documentType = DocumentType::findOrFail($data['document_type_id']);
        $branch = !empty($data['branch_id']) ? Branch::with('company')->find($data['branch_id']) : null;

        if ($branch && empty($data['company_id'])) {
            $data['company_id'] = $branch->company_id;
        }

        if (empty($data['document_number'])) {
            $data['document_number'] = $this->generateId($documentType, $branch, $unitKerja);
        }

        $data['visibility'] ??= Document::VISIBILITY_UNIT_KERJA;
        $data['owner_id'] = $ownerId;
        $data['paper_size'] ??= 'A4';

        if (!empty($data['corporate_soft_file_id'])) {
            $data['format_choice'] = 'F4';
            $data['paper_size'] = 'F4';
        }

        return DB::transaction(function () use ($data) {
            $doc = Document::create($data);

            $storedPath = $this->createBlankDocx($doc->id, 1);

            if (!empty($data['corporate_soft_file_id'])) {
                $softFile = \App\Models\CorporateSoftFile::find($data['corporate_soft_file_id']);
                if ($softFile) {
                    $this->initializeDocxWithSoftFile($storedPath, $softFile);
                }
            }

            $doc->versions()->create([
                'version_number' => 1,
                'content' => '',
                'file_path' => $storedPath,
                'file_original_name' => $doc->title . '.docx',
                'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'author_id' => $data['owner_id'],
                'author_name' => User::find($data['owner_id'])->name,
                'status' => 'draft',
            ]);

            return $doc;
        });
    }

    /**
     * Initialize a newly created document DOCX with corporate soft file content (DOCX, PDF, or Image).
     */
    public function initializeDocxWithSoftFile(string $targetRelativePath, \App\Models\CorporateSoftFile $corporateSoftFile): void
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        if (!$disk->exists($corporateSoftFile->file_path)) {
            return;
        }

        $onlyOfficeService = app(OnlyOfficeService::class);
        $existingDocx = $disk->exists($targetRelativePath) ? $disk->get($targetRelativePath) : '';
        $mergedDocx = $onlyOfficeService->applyCorporateSoftFileToDocx($existingDocx, $corporateSoftFile);
        $disk->put($targetRelativePath, $mergedDocx);
    }

    /**
     * Dokumen dari berkas fisik yang sudah diunggah.
     */
    public function createFromUpload(array $data, int $ownerId, UploadedFile $file): Document
    {
        return DB::transaction(function () use ($data, $ownerId, $file) {
            $data['visibility'] ??= Document::VISIBILITY_UNIT_KERJA;
            $data['owner_id'] = $ownerId;

            $doc = Document::create($data);

            $extension = $file->getClientOriginalExtension();
            $storedPath = $file->storeAs(
                'documents/' . $doc->id,
                'v1.' . $extension,
                'local'
            );

            $doc->versions()->create([
                'version_number' => 1,
                'content' => '',
                'author_id' => $ownerId,
                'author_name' => User::find($ownerId)->name,
                'status' => 'draft',
                'file_path' => $storedPath,
                'file_original_name' => $file->getClientOriginalName(),
                'file_mime' => $file->getClientMimeType(),
            ]);

            // Clear any cached key for the new document so ONLYOFFICE loads cleanly
            app(OnlyOfficeService::class)->rotateDocumentKey($doc);

            return $doc;
        });
    }

    /**
     * Save updated template binary content received from ONLYOFFICE or manual edit.
     */
    public function saveTemplateDocx(
        DocumentTemplate $template,
        string $docxBinaryContent,
        ?User $author = null
    ): DocumentTemplate {
        return DB::transaction(function () use ($template, $docxBinaryContent, $author) {
            $diskName = config('onlyoffice.storage_disk', 'local');
            $disk = Storage::disk($diskName);

            $storedPath = $template->file_path;
            if (empty($storedPath)) {
                $storedPath = 'templates/' . $template->id . '_' . time() . '.docx';
            }

            $disk->put($storedPath, $docxBinaryContent);

            $template->update([
                'file_path' => $storedPath,
                'file_original_name' => $template->file_original_name ?? ($template->title . '.docx'),
                'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'updated_at' => now(),
            ]);

            $template->touch();

            // Rotate template keys in cache so subsequent sessions load updated content
            app(OnlyOfficeService::class)->rotateTemplateKey($template);

            return $template->fresh();
        });
    }

    /**
     * Buat dokumen baru dari template. File .docx template di-copy ke
     * storage dokumen baru, sehingga template asli tidak pernah berubah.
     */
    public function createFromTemplate(array $data, int $ownerId, DocumentTemplate $template): Document
    {
        $template = $template->fresh() ?? $template;

        $unitKerja = !empty($data['unit_kerja_id']) ? UnitKerja::find($data['unit_kerja_id']) : null;
        $documentType = DocumentType::findOrFail($data['document_type_id']);
        $branch = !empty($data['branch_id']) ? Branch::with('company')->find($data['branch_id']) : null;

        if ($branch && empty($data['company_id'])) {
            $data['company_id'] = $branch->company_id;
        }

        if (empty($data['document_number'])) {
            $data['document_number'] = $this->generateId($documentType, $branch, $unitKerja);
        }

        $data['visibility'] ??= Document::VISIBILITY_UNIT_KERJA;
        $data['owner_id'] = $ownerId;
        $data['template_id'] = $template->id;
        $data['paper_size'] ??= 'A4';

        return DB::transaction(function () use ($data, $template) {
            $doc = Document::create($data);

            $diskName = config('onlyoffice.storage_disk', 'local');
            $disk = Storage::disk($diskName);
            $destDir = 'documents/' . $doc->id;
            $destPath = $destDir . '/v1.docx';

            $templatePath = $template->file_path;
            $sourceDisk = $disk;

            if ($templatePath && !$sourceDisk->exists($templatePath)) {
                if (Storage::disk('local')->exists($templatePath)) {
                    $sourceDisk = Storage::disk('local');
                } elseif (Storage::disk('public')->exists($templatePath)) {
                    $sourceDisk = Storage::disk('public');
                }
            }

            if ($templatePath && $sourceDisk->exists($templatePath)) {
                $templateBytes = $sourceDisk->get($templatePath);
                $disk->put($destPath, $templateBytes);
            } else {
                // Fallback: create a blank docx if template file missing
                $phpWord = new PhpWord();
                $section = $phpWord->addSection([
                    'pageSizeW' => 11906,
                    'pageSizeH' => 16838,
                    'marginTop' => 1440,
                    'marginBottom' => 1440,
                    'marginLeft' => 1440,
                    'marginRight' => 1440,
                ]);
                $section->addText(' ', ['name' => 'Arial', 'size' => 11]);
                $tempPath = tempnam(sys_get_temp_dir(), 'docx_');
                $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save($tempPath);
                $disk->put($destPath, file_get_contents($tempPath));
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
            }

            $doc->versions()->create([
                'version_number' => 1,
                'content' => '',
                'file_path' => $destPath,
                'file_original_name' => $doc->title . '.docx',
                'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'author_id' => $data['owner_id'],
                'author_name' => User::find($data['owner_id'])->name,
                'status' => 'draft',
            ]);

            // Clear any cached key for the new document so ONLYOFFICE loads cleanly
            app(OnlyOfficeService::class)->rotateDocumentKey($doc);

            return $doc;
        });
    }

    /**
     * Kirim job ringkasan AI ke antrian.
     */
    public function dispatchSummary(Document $document, int $percentage = 30, string $model = 'auto', string $locale = 'id'): void
    {
        Cache::lock('summarize:' . $document->id)->forceRelease();

        $document->update([
            'summary_status' => Document::SUMMARY_PROCESSING,
            'summary_started_at' => now(),
            'summary_error' => null,
        ]);

        SummarizeDocumentJob::dispatch($document->id, $percentage, $model, $locale);
    }
}
