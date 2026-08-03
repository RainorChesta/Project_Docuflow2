<?php

namespace App\Services;

use App\Models\Document;
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
                ->orderBy('document_number', 'desc')
                ->first();

            $seq = $lastDoc ? (int) substr($lastDoc->document_number, -3) + 1 : 1;

            return sprintf('%s-%s-%03d', $prefix, $date, $seq);
        });
    }

    public function create(array $data, int $ownerId): Document
    {
        $data['visibility'] ??= Document::VISIBILITY_DIVISION;

        $division = $data['division_id'] ? Division::findOrFail($data['division_id']) : null;
        $data['document_number'] = $this->generateId($division);
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
}
