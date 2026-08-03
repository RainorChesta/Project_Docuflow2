<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function generateId(?Division $division): string
    {
        // Non-division scopes (general/personal) use a generic prefix since
        // they are not tied to any division.
        $prefix = $division ? $division->code : 'GEN';
        $date = Carbon::now()->format('Ymd');

        return DB::transaction(function () use ($prefix, $date) {
            $lastDoc = Document::where('document_number', 'like', $prefix . '-' . $date . '-%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $seq = 1;
            if ($lastDoc) {
                // Sequence selalu di segmen pertama, jadi aman diparsing
                // meskipun kode tipe mengandung "-" hasil substitusi di atas
                $firstSegment = explode('-', $lastDoc->document_number)[2] ?? null;
                $seq = $firstSegment ? (int) $firstSegment + 1 : 1;
            }

            return sprintf('%s-%s-%03d', $prefix, $date, $seq);
        });
    }

    public function create(array $data, int $ownerId): Document
    {
        $division = $data['division_id'] ? Division::findOrFail($data['division_id']) : null;
        $documentType = DocumentType::findOrFail($data['document_type_id']);

        $data['document_number'] = $this->generateId($division);
        $data['visibility'] ??= Document::VISIBILITY_DIVISION;
        $data['owner_id'] = $ownerId;

        return DB::transaction(function () use ($data) {
            $doc = Document::create($data);

            $doc->versions()->create([
                'version_number' => 1,
                'content' => '',
                'author_id' => $data['owner_id'],
                'author_name' => \App\Models\User::find($data['owner_id'])->name,
                'status' => 'draft',
            ]);

            return $doc;
        });
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
