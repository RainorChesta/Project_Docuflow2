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
    public function generateId(?Division $division, ?DocumentType $documentType, ?\App\Models\Branch $branch = null): string
    {
        return DB::transaction(function () use ($division, $documentType, $branch) {
            $query = Document::withTrashed()
                ->where('document_type_id', $documentType?->id)
                ->whereYear('created_at', now()->year);

            if ($branch) {
                $query->where('branch_id', $branch->id);
            }

            // SOP document numbers do not belong to a specific division scope
            if ($division && strtoupper($documentType?->code ?? '') !== 'SOP') {
                $query->where('division_id', $division->id);
            }

            $lastDoc = $query->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $seq = $this->nextSequenceFrom($lastDoc);

            return $this->formatNumber($division, $documentType, $seq, $branch);
        });
    }

    /**
     * Non-locking preview of the next number, purely indicative for the
     * create form.
     */
    public function previewNumber(?Division $division, ?DocumentType $documentType, ?\App\Models\Branch $branch = null): string
    {
        $query = Document::withTrashed()
            ->where('document_type_id', $documentType?->id)
            ->whereYear('created_at', now()->year);

        if ($branch) {
            $query->where('branch_id', $branch->id);
        }

        // SOP document numbers do not belong to a specific division scope
        if ($division && strtoupper($documentType?->code ?? '') !== 'SOP') {
            $query->where('division_id', $division->id);
        }

        $lastDoc = $query->orderByDesc('id')->first();

        $seq = $this->nextSequenceFrom($lastDoc);

        return $this->formatNumber($division, $documentType, $seq, $branch);
    }

    private function nextSequenceFrom(?Document $lastDoc): int
    {
        if (!$lastDoc || empty($lastDoc->document_number)) {
            return 1;
        }

        // Sequence selalu di segmen pertama, jadi aman diparsing meskipun
        // kode tipe mengandung "-" hasil substitusi di formatNumber().
        $firstSegment = explode('/', $lastDoc->document_number)[0];

        return (int) $firstSegment + 1;
    }

    private function formatNumber(?Division $division, ?DocumentType $documentType, int $seq, ?\App\Models\Branch $branch = null): string
    {
        $now = Carbon::now();
        $year = $now->year;
        $romanMonth = $this->toRomanMonth($now->month);
        
        $branchCode = $branch ? $branch->effective_code : config('dokuflow.central_code', 'JBM');

        // "/" di kode tipe diganti "-" khusus untuk nomor dokumen, supaya
        // jumlah segmen yang dipisah "/" tetap konsisten.
        $typeCode = $documentType?->code ?? 'GEN';
        $typeCodeForNumber = str_replace('/', '-', $typeCode);

        // Khusus tipe SOP, nomor dokumen tidak menggunakan kode divisi.
        // Format: {seq}/SOP/{branch}/{month}/{year} (contoh: 001/SOP/JBM/VIII/2026)
        if (strtoupper($typeCode) === 'SOP') {
            return sprintf(
                '%03d/%s/%s/%s/%d',
                $seq,
                $typeCodeForNumber,
                $branchCode,
                $romanMonth,
                $year
            );
        }

        // Dokumen selain SOP: format menyertakan kode divisi.
        // Non-division scopes (general/personal) pakai kode generik karena
        // tidak terikat divisi manapun.
        // Format: {seq}/{type}/{division}/{branch}/{month}/{year} (contoh: 001/S.ED/IT/JBM/VIII/2026)
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

    public function create(array $data, int $ownerId): Document
    {
        $division = !empty($data['division_id']) ? Division::find($data['division_id']) : null;
        $documentType = DocumentType::findOrFail($data['document_type_id']);
        $branch = !empty($data['branch_id']) ? \App\Models\Branch::with('company')->find($data['branch_id']) : null;

        if ($branch && empty($data['company_id'])) {
            $data['company_id'] = $branch->company_id;
        }

        $data['document_number'] = $this->generateId($division, $documentType, $branch);
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
        $division = !empty($data['division_id']) ? Division::find($data['division_id']) : null;
        $documentType = DocumentType::findOrFail($data['document_type_id']);
        $branch = !empty($data['branch_id']) ? \App\Models\Branch::with('company')->find($data['branch_id']) : null;

        if ($branch && empty($data['company_id'])) {
            $data['company_id'] = $branch->company_id;
        }

        $data['document_number'] = $this->generateId($division, $documentType, $branch);
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
