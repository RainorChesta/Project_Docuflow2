<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    public function generateId(Division $division): string
    {
        $date = Carbon::now()->format('Ymd');

        return DB::transaction(function () use ($division, $date) {
            $lastDoc = Document::where('division_id', $division->id)
                ->where('document_number', 'like', $division->code . '-' . $date . '-%')
                ->lockForUpdate()
                ->orderBy('document_number', 'desc')
                ->first();

            $seq = $lastDoc ? (int) substr($lastDoc->document_number, -3) + 1 : 1;

            return sprintf('%s-%s-%03d', $division->code, $date, $seq);
        });
    }

    public function create(array $data, int $ownerId): Document
    {
        $division = Division::findOrFail($data['division_id']);
        $data['document_number'] = $this->generateId($division);
        $data['owner_id'] = $ownerId;

        return DB::transaction(function () use ($data) {
            $doc = Document::create($data);

            $doc->versions()->create([
                'version_number' => 1,
                'content' => '',
                'author_id' => $data['owner_id'],
                'author_name' => \App\Models\User::find($data['owner_id'])->name,
                'status' => 'pending',
            ]);

            return $doc;
        });
    }
}
