<?php

namespace App\Services;

use App\Jobs\SummarizeDocumentJob;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Division;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;

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
    public function generateId($formatChoice = null, $division = null, $documentType = null, $branch = null, $unitKerja = null): string
    {
        if ($formatChoice instanceof Division) {
            $unitKerja = $branch instanceof \App\Models\UnitKerja ? $branch : ($documentType instanceof \App\Models\UnitKerja ? $documentType : $unitKerja);
            $branch = $branch instanceof \App\Models\Branch ? $branch : ($documentType instanceof \App\Models\Branch ? $documentType : null);
            $documentType = $division instanceof \App\Models\DocumentType ? $division : ($documentType instanceof \App\Models\DocumentType ? $documentType : null);
            $division = $formatChoice;
            $formatChoice = !empty($unitKerja) ? 'lama' : 'baru';
        } elseif ($formatChoice instanceof DocumentType) {
            $unitKerja = $branch instanceof \App\Models\UnitKerja ? $branch : $unitKerja;
            $branch = $division instanceof \App\Models\Branch ? $division : null;
            $documentType = $formatChoice;
            $division = null;
            $formatChoice = !empty($unitKerja) ? 'lama' : 'baru';
        } elseif ($division instanceof DocumentType) {
            $unitKerja = $branch instanceof \App\Models\UnitKerja ? $branch : $unitKerja;
            $branch = $documentType instanceof \App\Models\Branch ? $documentType : null;
            $documentType = $division;
            $division = null;
        }

        $formatChoice = is_string($formatChoice) ? $formatChoice : (!empty($unitKerja) ? 'lama' : 'baru');

        return DB::transaction(function () use ($formatChoice, $division, $documentType, $branch, $unitKerja) {
            $year = now()->year;
            $branchId = $branch?->id;
            $typeId = $documentType?->id;

            if ($formatChoice === 'lama') {
                $query = Document::withTrashed()
                    ->where('document_type_id', $typeId)
                    ->whereYear('created_at', $year);

                if ($branch) {
                    $query->where('branch_id', $branchId);
                }

                if (strtoupper($documentType?->code ?? '') === 'SOP') {
                    if ($unitKerja) {
                        $query->where('unit_kerja_id', $unitKerja->id);
                    } elseif ($division) {
                        $query->where('division_id', $division->id);
                    }
                } else {
                    if ($division) {
                        $query->where('division_id', $division->id);
                    }
                }
            } else {
                $divisionId = $division?->id;
                $query = Document::withTrashed()
                    ->where('document_type_id', $typeId)
                    ->whereYear('created_at', $year);

                if ($divisionId) {
                    $query->where('division_id', $divisionId);
                }
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            }

            // Lock the highest sequence row to serialize increments safely
            $maxDoc = (clone $query)->lockForUpdate()->orderByDesc('id')->first();

            $seq = 1;
            if ($maxDoc && $maxDoc->document_number) {
                $parts = explode('/', $maxDoc->document_number);
                if (!empty($parts[0]) && is_numeric($parts[0])) {
                    $seq = (int)$parts[0] + 1;
                } else {
                    $seq = (clone $query)->count() + 1;
                }
            }

            $typeCode = $documentType ? $documentType->code : 'DOC';
            $branchCode = $branch ? $branch->effective_code : 'PST';
            $romanMonth = $this->toRoman(now()->month);

            $formattedNumber = $this->formatNumber(
                $formatChoice,
                $seq,
                $typeCode,
                $division,
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
                    $formatChoice,
                    $seq,
                    $typeCode,
                    $division,
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
    public function previewNumber($formatChoice = null, $division = null, $documentType = null, $branch = null, $unitKerja = null): string
    {
        if ($formatChoice instanceof Division) {
            $unitKerja = $branch instanceof \App\Models\UnitKerja ? $branch : ($documentType instanceof \App\Models\UnitKerja ? $documentType : $unitKerja);
            $branch = $branch instanceof \App\Models\Branch ? $branch : ($documentType instanceof \App\Models\Branch ? $documentType : null);
            $documentType = $division instanceof \App\Models\DocumentType ? $division : ($documentType instanceof \App\Models\DocumentType ? $documentType : null);
            $division = $formatChoice;
            $formatChoice = !empty($unitKerja) ? 'lama' : 'baru';
        } elseif ($formatChoice instanceof DocumentType) {
            $unitKerja = $branch instanceof \App\Models\UnitKerja ? $branch : $unitKerja;
            $branch = $division instanceof \App\Models\Branch ? $division : null;
            $documentType = $formatChoice;
            $division = null;
            $formatChoice = !empty($unitKerja) ? 'lama' : 'baru';
        } elseif ($division instanceof DocumentType) {
            $unitKerja = $branch instanceof \App\Models\UnitKerja ? $branch : $unitKerja;
            $branch = $documentType instanceof \App\Models\Branch ? $documentType : null;
            $documentType = $division;
            $division = null;
        }

        $formatChoice = is_string($formatChoice) ? $formatChoice : (!empty($unitKerja) ? 'lama' : 'baru');

        $year = now()->year;
        $branchId = $branch?->id;
        $typeId = $documentType?->id;

        if ($formatChoice === 'lama') {
            $query = Document::withTrashed()
                ->where('document_type_id', $typeId)
                ->whereYear('created_at', $year);

            if ($branch) {
                $query->where('branch_id', $branchId);
            }

            if (strtoupper($documentType?->code ?? '') === 'SOP') {
                if ($unitKerja) {
                    $query->where('unit_kerja_id', $unitKerja->id);
                } elseif ($division) {
                    $query->where('division_id', $division->id);
                }
            } else {
                if ($division) {
                    $query->where('division_id', $division->id);
                }
            }
        } else {
            $divisionId = $division?->id;
            $query = Document::withTrashed()
                ->where('document_type_id', $typeId)
                ->whereYear('created_at', $year);

            if ($divisionId) {
                $query->where('division_id', $divisionId);
            }
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        }

        $maxDoc = (clone $query)->orderByDesc('id')->first();

        $seq = 1;
        if ($maxDoc && $maxDoc->document_number) {
            $parts = explode('/', $maxDoc->document_number);
            if (!empty($parts[0]) && is_numeric($parts[0])) {
                $seq = (int)$parts[0] + 1;
            } else {
                $seq = (clone $query)->count() + 1;
            }
        }

        $typeCode = $documentType ? $documentType->code : 'DOC';
        $branchCode = $branch ? $branch->effective_code : 'PST';
        $romanMonth = $this->toRoman(now()->month);

        return $this->formatNumber(
            $formatChoice,
            $seq,
            $typeCode,
            $division,
            $branchCode,
            $romanMonth,
            $year,
            $unitKerja
        );
    }

    private function toRoman(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $map[$month] ?? 'I';
    }

    private function formatNumber(
        string $formatChoice,
        int $seq,
        string $typeCode,
        ?Division $division,
        string $branchCode,
        string $romanMonth,
        int $year,
        ?\App\Models\UnitKerja $unitKerja = null
    ): string {
        $typeCodeForNumber = str_replace('/', '-', $typeCode);

        if ($formatChoice === 'baru') {
            $divisionCode = $division ? $division->code : 'GEN';
            return sprintf(
                '%03d/%s/%s/%s/%s/%d',
                $seq,
                $typeCodeForNumber,
                $divisionCode,
                $branchCode,
                $romanMonth,
                $year
            );
        }

        // Format Lama
        if (strtoupper($typeCode) === 'SOP') {
            $unitKerjaCode = $unitKerja ? $unitKerja->kode_unit_kerja : '00';
            return sprintf(
                '%03d/%s-%s/%s/%s/%d',
                $seq,
                $typeCodeForNumber,
                $unitKerjaCode,
                $branchCode,
                $romanMonth,
                $year
            );
        }

        return sprintf(
            '%03d/%s/%s/%s/%d',
            $seq,
            $typeCodeForNumber,
            $branchCode,
            $romanMonth,
            $year
        );
    }

    public function create(array $data, int $ownerId): Document
    {
        if (empty($data['division_id']) && $ownerId) {
            $owner = User::find($ownerId);
            if ($owner) {
                $activeDivId = app(\App\Services\CompanyContextService::class)->getActiveDivisionId($owner)
                    ?? $owner->division_id
                    ?? ($owner->allDivisionIds()[0] ?? null);
                if ($activeDivId) {
                    $data['division_id'] = $activeDivId;
                }
            }
        }

        $division = !empty($data['division_id']) ? Division::find($data['division_id']) : null;
        $unitKerja = !empty($data['unit_kerja_id']) ? \App\Models\UnitKerja::find($data['unit_kerja_id']) : null;
        $documentType = DocumentType::findOrFail($data['document_type_id']);
        $branch = !empty($data['branch_id']) ? \App\Models\Branch::with('company')->find($data['branch_id']) : null;
        $formatChoice = $data['format_choice'] ?? (!empty($unitKerja) ? 'lama' : 'baru');

        if ($branch && empty($data['company_id'])) {
            $data['company_id'] = $branch->company_id;
        }

        if (empty($data['document_number'])) {
            if ($formatChoice === 'baru' || $formatChoice === 'lama') {
                $data['document_number'] = $this->generateId($formatChoice, $division, $documentType, $branch, $unitKerja);
            }
        }
        $data['visibility'] ??= Document::VISIBILITY_DIVISION;
        $data['owner_id'] = $ownerId;

        return DB::transaction(function () use ($data) {
            $doc = Document::create($data);

            $storedPath = $this->createBlankDocx($doc->id, 1);

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
     * Dokumen dari berkas fisik yang sudah diunggah (bukan ditulis di Jodit).
     * Nomor sudah divalidasi manual oleh controller (mengikuti format resmi),
     * jadi tidak di-generate di sini. Versi pertama langsung berstatus
     * "pending" — skip draft, langsung minta approval kepala divisi.
     */
    public function createFromUpload(array $data, int $ownerId, UploadedFile $file): Document
    {
        if (empty($data['division_id']) && $ownerId) {
            $owner = User::find($ownerId);
            if ($owner) {
                $activeDivId = app(\App\Services\CompanyContextService::class)->getActiveDivisionId($owner)
                    ?? $owner->division_id
                    ?? ($owner->allDivisionIds()[0] ?? null);
                if ($activeDivId) {
                    $data['division_id'] = $activeDivId;
                }
            }
        }

        return DB::transaction(function () use ($data, $ownerId, $file) {
            $data['visibility'] ??= Document::VISIBILITY_DIVISION;
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
                'status' => 'pending',
                'file_path' => $storedPath,
                'file_original_name' => $file->getClientOriginalName(),
                'file_mime' => $file->getClientMimeType(),
            ]);

            return $doc;
        });
    }

    /**
     * Buat dokumen baru dari template. File .docx template di-copy ke
     * storage dokumen baru, sehingga template asli tidak pernah berubah.
     * Versi pertama berstatus "draft" — user bisa langsung edit di OnlyOffice.
     */
    public function createFromTemplate(array $data, int $ownerId, \App\Models\DocumentTemplate $template): Document
    {
        if (empty($data['division_id']) && $ownerId) {
            $owner = User::find($ownerId);
            if ($owner) {
                $activeDivId = app(\App\Services\CompanyContextService::class)->getActiveDivisionId($owner)
                    ?? $owner->division_id
                    ?? ($owner->allDivisionIds()[0] ?? null);
                if ($activeDivId) {
                    $data['division_id'] = $activeDivId;
                }
            }
        }

        $division = !empty($data['division_id']) ? Division::find($data['division_id']) : null;
        $unitKerja = !empty($data['unit_kerja_id']) ? \App\Models\UnitKerja::find($data['unit_kerja_id']) : null;
        $documentType = DocumentType::findOrFail($data['document_type_id']);
        $branch = !empty($data['branch_id']) ? \App\Models\Branch::with('company')->find($data['branch_id']) : null;
        $formatChoice = $data['format_choice'] ?? (!empty($unitKerja) ? 'lama' : 'baru');

        if ($branch && empty($data['company_id'])) {
            $data['company_id'] = $branch->company_id;
        }

        if (empty($data['document_number'])) {
            if ($formatChoice === 'baru' || $formatChoice === 'lama') {
                $data['document_number'] = $this->generateId($formatChoice, $division, $documentType, $branch, $unitKerja);
            }
        }
        $data['visibility'] ??= Document::VISIBILITY_DIVISION;
        $data['owner_id'] = $ownerId;
        $data['template_id'] = $template->id;

        return DB::transaction(function () use ($data, $template) {
            $doc = Document::create($data);

            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
            $destDir = 'documents/' . $doc->id;
            $destPath = $destDir . '/v1.docx';

            // Copy template file to new document storage
            $disk->copy($template->file_path, $destPath);

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

            return $doc;
        });
    }

    /**
     * Kirim job ringkasan AI ke antrian. Job hanya membawa document id —
     * payload kecil, dan request web tidak menunggu Groq selesai.
     */
    public function dispatchSummary(Document $document, int $percentage = 30, string $model = 'auto', string $locale = 'id'): void
    {
        // Lepas kunci lama (kalau ada) supaya job baru tidak di-skip.
        Cache::lock('summarize:' . $document->id)->forceRelease();

        $document->update([
            'summary_status' => Document::SUMMARY_PROCESSING,
            'summary_started_at' => now(),
            'summary_error' => null,
        ]);

        SummarizeDocumentJob::dispatch($document->id, $percentage, $model, $locale);
    }

    private function toRomanMonth(int $month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $romans[$month];
    }
}
