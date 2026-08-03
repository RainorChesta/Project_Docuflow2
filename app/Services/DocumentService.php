<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function generateId(?Division $division, ?DocumentType $documentType): string
    {
        $centralCode = config('dokuflow.central_code', 'JBM');
        $typeCode = $documentType?->code ?? 'GEN';
        $divisionCode = $division?->code ?? 'GEN';
        $romanMonth = $this->toRomanMonth(Carbon::now()->month);
        $year = Carbon::now()->year;

        return DB::transaction(function () use ($centralCode, $typeCode, $divisionCode, $romanMonth, $year) {
            $lastDoc = Document::where('document_number', 'like', '%/' . $typeCode . '/' . $divisionCode . '/' . $centralCode . '/' . $romanMonth . '/' . $year)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $seq = 1;
            if ($lastDoc) {
                $firstSegment = explode('/', $lastDoc->document_number)[0];
                $seq = (int) $firstSegment + 1;
            }

            return sprintf('%03d/%s/%s/%s/%s/%d', $seq, $typeCode, $divisionCode, $centralCode, $romanMonth, $year);
        });
    }

    public function create(array $data, int $ownerId): Document
    {
        $division = $data['division_id'] ? Division::findOrFail($data['division_id']) : null;
        $documentType = DocumentType::findOrFail($data['document_type_id']);

        $data['document_number'] = $this->generateId($division, $documentType);
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
