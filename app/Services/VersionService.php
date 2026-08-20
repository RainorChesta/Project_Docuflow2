<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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
                    'content' => \Purifier::clean($content, 'youtube'),
                    'file_path' => null,
                    'file_original_name' => null,
                    'file_mime' => null,
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
                    'content' => \Purifier::clean($content, 'youtube'),
                    'author_id' => $author->id,
                    'author_name' => $author->name,
                    'status' => 'pending',
                ]);

                return $draft;
            }

            $versionNumber = ($document->versions()->max('version_number') ?? 0) + 1;

            $version = $document->versions()->create([
                'version_number' => $versionNumber,
                'content' => \Purifier::clean($content, 'youtube'),
                'author_id' => $author->id,
                'author_name' => $author->name,
                'status' => 'pending',
            ]);

            return $version;
        });
    }

    /**
     * Sama seperti savePending(), tapi sumbernya berkas unggahan (bukan
     * konten editor). Dipakai untuk fitur "unggah versi terbaru" pada
     * dokumen yang isinya memang berasal dari berkas.
     */
    public function savePendingFile(Document $document, UploadedFile $file, User $author): DocumentVersion
    {
        return DB::transaction(function () use ($document, $file, $author) {
            $pending = $document->versions()->pending()
                ->whereNull('discarded_at')
                ->orderBy('version_number', 'desc')
                ->first();

            if ($pending) {
                $document->versions()->where('status', 'draft')->delete();

                // Versi pending belum pernah jadi target rollback (rollback
                // hanya untuk versi non-pending), jadi berkas lamanya aman dihapus.
                if ($pending->file_path) {
                    Storage::disk('local')->delete($pending->file_path);
                }

                $stored = $this->storeVersionFile($document->id, $pending->version_number, $file);

                $pending->update([
                    'content' => '',
                    'file_path' => $stored['path'],
                    'file_original_name' => $stored['name'],
                    'file_mime' => $stored['mime'],
                    'author_id' => $author->id,
                    'author_name' => $author->name,
                ]);

                return $pending;
            }

            $draft = $document->versions()->where('status', 'draft')
                ->orderBy('version_number', 'desc')
                ->first();

            $versionNumber = $draft
                ? $draft->version_number
                : ($document->versions()->max('version_number') ?? 0) + 1;

            $stored = $this->storeVersionFile($document->id, $versionNumber, $file);

            if ($draft) {
                $draft->update([
                    'content' => '',
                    'file_path' => $stored['path'],
                    'file_original_name' => $stored['name'],
                    'file_mime' => $stored['mime'],
                    'author_id' => $author->id,
                    'author_name' => $author->name,
                    'status' => 'pending',
                ]);

                return $draft;
            }

            return $document->versions()->create([
                'version_number' => $versionNumber,
                'content' => '',
                'file_path' => $stored['path'],
                'file_original_name' => $stored['name'],
                'file_mime' => $stored['mime'],
                'author_id' => $author->id,
                'author_name' => $author->name,
                'status' => 'pending',
            ]);
        });
    }

    private function storeVersionFile(int $documentId, int $versionNumber, UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs(
            'documents/' . $documentId,
            'v' . $versionNumber . '.' . $extension,
            'local'
        );

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
        ];
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
            if ($pending->file_path) {
                Storage::disk('local')->delete($pending->file_path);
            }
            $pending->delete();
        }
        
        // Hapus juga semua versi yang sebelumnya sudah ditandai discarded (jika ada)
        // agar perhitungan max('version_number') kembali normal.
        $discardedVersions = $document->versions()->where('status', 'discarded')->get();
        foreach ($discardedVersions as $dv) {
            if ($dv->file_path) {
                Storage::disk('local')->delete($dv->file_path);
            }
            $dv->delete();
        }

        return $pending;
    }

    public function saveDraft(Document $document, string $content, User $author): DocumentVersion
    {
        // Draft terbaru = v1 yang dibuat saat create. Update kontennya, tetap draft.
        $version = $document->versions()->latest('version_number')->first();

        if ($version && $version->status === 'draft') {
            $version->update([
                'content' => \Purifier::clean($content, 'youtube'),
                'author_name' => $author->name,
            ]);

            return $version;
        }

        // Fallback: buat versi draft baru
        $versionNumber = ($document->versions()->max('version_number') ?? 0) + 1;

        return $document->versions()->create([
            'version_number' => $versionNumber,
            'content' => \Purifier::clean($content, 'youtube'),
            'author_id' => $author->id,
            'author_name' => $author->name,
            'status' => 'draft',
        ]);
    }

    public function approve(DocumentVersion $version, User $reviewer, ?string $notes = null): void
    {
        DB::transaction(function () use ($version, $reviewer, $notes) {
            // Versi aktif sebelumnya jadi inactive — cuma satu active per dokumen.
            $version->document->versions()
                ->where('status', 'active')
                ->where('id', '!=', $version->id)
                ->update(['status' => 'inactive']);

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

    /**
     * Ajukan permintaan rollback ke versi tertentu. Tidak menghapus atau
     * mengubah versi apa pun di sini — hanya menyimpan pointer permintaan
     * yang menunggu persetujuan kepala divisi. Eksekusi sesungguhnya
     * (hapus versi setelahnya + aktifkan versi target) terjadi di
     * approveRollbackRequest().
     */
    public function requestRollback(Document $document, DocumentVersion $targetVersion, User $author): void
    {
        if ($targetVersion->id === $document->current_version_id) {
            throw new RuntimeException('Versi ini sudah menjadi versi aktif saat ini.');
        }

        if ($document->hasPendingRollback()) {
            throw new RuntimeException('Sudah ada permintaan rollback lain yang masih menunggu approval.');
        }

        $hasPendingContent = $document->versions()
            ->where('status', 'pending')
            ->whereNull('discarded_at')
            ->exists();

        if ($hasPendingContent) {
            throw new RuntimeException('Selesaikan dulu perubahan yang masih menunggu approval sebelum mengajukan rollback.');
        }

        $document->update([
            'pending_rollback_version_id' => $targetVersion->id,
            'rollback_requested_by_id' => $author->id,
            'rollback_requested_at' => now(),
        ]);
    }

    /**
     * Setujui permintaan rollback: semua versi SETELAH versi target
     * (nomor versi lebih besar) dihapus permanen — beserta berkas
     * fisiknya kalau ada. Versi target sendiri diaktifkan kembali apa
     * adanya, tanpa membuat versi baru.
     */
    public function approveRollbackRequest(Document $document, User $reviewer): DocumentVersion
    {
        return DB::transaction(function () use ($document, $reviewer) {
            $targetVersion = $document->pendingRollbackVersion;

            if (!$targetVersion) {
                throw new RuntimeException('Tidak ada permintaan rollback yang menunggu.');
            }

            $this->purgeVersionsAfter($document, $targetVersion);

            $document->versions()
                ->where('status', 'active')
                ->where('id', '!=', $targetVersion->id)
                ->update(['status' => 'inactive']);

            $targetVersion->update([
                'status' => 'active',
                'discarded_at' => null,
            ]);

            $document->update([
                'current_version_id' => $targetVersion->id,
                'pending_rollback_version_id' => null,
                'rollback_requested_by_id' => null,
                'rollback_requested_at' => null,
            ]);

            return $targetVersion->fresh();
        });
    }

    /**
     * Tolak permintaan rollback: tidak ada versi yang diubah atau dihapus,
     * hanya membatalkan permintaannya.
     */
    public function rejectRollbackRequest(Document $document): void
    {
        if (!$document->hasPendingRollback()) {
            throw new RuntimeException('Tidak ada permintaan rollback yang menunggu.');
        }

        $document->update([
            'pending_rollback_version_id' => null,
            'rollback_requested_by_id' => null,
            'rollback_requested_at' => null,
        ]);
    }

    /**
     * Hapus permanen semua versi dengan version_number > target, beserta
     * berkas fisiknya (kalau ada). Kalau versi aktif saat ini termasuk yang
     * dihapus, pointer current_version_id dilepas dulu supaya tidak
     * menunjuk ke record yang sudah tidak ada.
     */
    private function purgeVersionsAfter(Document $document, DocumentVersion $sourceVersion): void
    {
        $toDelete = $document->versions()
            ->where('version_number', '>', $sourceVersion->version_number)
            ->get();

        if ($toDelete->isEmpty()) {
            return;
        }

        if ($document->current_version_id && $toDelete->contains('id', $document->current_version_id)) {
            $document->update(['current_version_id' => null]);
        }

        foreach ($toDelete as $version) {
            if ($version->file_path) {
                Storage::disk('local')->delete($version->file_path);
            }
        }

        $document->versions()
            ->where('version_number', '>', $sourceVersion->version_number)
            ->delete();
    }
}
