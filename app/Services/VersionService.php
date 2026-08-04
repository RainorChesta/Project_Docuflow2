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
        return DB::transaction(function () use ($document, $content, $author) {
            // Pending version already exists → update it in place, keep the same version number.
            $pending = $document->versions()->pending()
                ->whereNull('discarded_at')
                ->orderBy('version_number', 'desc')
                ->first();

            if ($pending) {
                // Draft lama jadi usang saat konten dikirim ke approval — buang.
                // (Draft = kerjaan belum dikirim; save = resmi dikirim ke approval.)
                $document->versions()->where('status', 'draft')->delete();

                $pending->update([
                    'content' => \Purifier::clean($content),
                    'author_id' => $author->id,
                    'author_name' => $author->name,
                ]);

                return $pending;
            }

            // Tidak ada pending, tapi ada draft (mis. v1 hasil create) → promosikan draft
            // jadi pending dengan version_number yang sama, bukan bikin versi baru.
            $draft = $document->versions()->where('status', 'draft')
                ->orderBy('version_number', 'desc')
                ->first();

            if ($draft) {
                $draft->update([
                    'content' => \Purifier::clean($content),
                    'author_id' => $author->id,
                    'author_name' => $author->name,
                    'status' => 'pending',
                ]);

                return $draft;
            }

            $versionNumber = ($document->versions()->max('version_number') ?? 0) + 1;

            $version = $document->versions()->create([
                'version_number' => $versionNumber,
                'content' => \Purifier::clean($content),
                'author_id' => $author->id,
                'author_name' => $author->name,
                'status' => 'pending',
            ]);

            return $version;
        });
    }

    /**
     * Discard the newest pending version. The previous pending version
     * (if any) becomes pending again.
     */
    public function discardPending(Document $document): ?DocumentVersion
    {
        $pending = $document->versions()->pending()
            ->whereNull('discarded_at')
            ->orderBy('version_number', 'desc')
            ->first();

        if ($pending) {
            $pending->update(['status' => 'discarded', 'discarded_at' => now()]);
        }

        return $pending;
    }

    public function saveDraft(Document $document, string $content, User $author): DocumentVersion
    {
        // Draft terbaru = v1 yang dibuat saat create. Update kontennya, tetap draft.
        $version = $document->versions()->latest('version_number')->first();

        if ($version && $version->status === 'draft') {
            $version->update([
                'content' => \Purifier::clean($content),
                'author_name' => $author->name,
            ]);

            return $version;
        }

        // Fallback: buat versi draft baru
        $versionNumber = ($document->versions()->max('version_number') ?? 0) + 1;

        return $document->versions()->create([
            'version_number' => $versionNumber,
            'content' => \Purifier::clean($content),
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'draft',
        ]);
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
