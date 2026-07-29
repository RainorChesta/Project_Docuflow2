<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VersionService
{
    public function savePending(Document $document, string $content, User $author): DocumentVersion
    {
        $versionNumber = ($document->versions()->max('version_number') ?? 0) + 1;

        $version = $document->versions()->create([
            'version_number' => $versionNumber,
            'content' => \Purifier::clean($content),
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'pending',
        ]);

        return $version;
    }

    public function approve(DocumentVersion $version, User $reviewer, ?string $notes = null): void
    {
        DB::transaction(function () use ($version, $reviewer, $notes) {
            $version->update([
                'status' => 'active',
                'reviewer_id' => $reviewer->id,
                'review_notes' => $notes,
                'reviewed_at' => now(),
            ]);

            $version->document->update([
                'current_version_id' => $version->id,
            ]);
        });
    }

    public function reject(DocumentVersion $version, User $reviewer, ?string $notes = null): void
    {
        $version->update([
            'status' => 'rejected',
            'reviewer_id' => $reviewer->id,
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    public function rollback(Document $document, DocumentVersion $sourceVersion, User $author): DocumentVersion
    {
        return $this->savePending($document, $sourceVersion->content, $author);
    }
}
