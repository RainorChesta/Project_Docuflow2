<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Document;
use App\Models\DocumentAccessLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessLinkService
{
    /**
     * Create a share link for a document+role, unless an active link with the
     * same role already exists. Check-and-insert is atomic (DB unique index
     * on document_id+role) so concurrent requests cannot create duplicates.
     *
     * @throws BusinessLogicException if an active same-role link already exists
     */
    public function create(Document $document, string $role, ?string $expiresAt, User $createdBy): DocumentAccessLink
    {
        return DB::transaction(function () use ($document, $role, $expiresAt, $createdBy) {
            // Expired same-role links free up the role for reuse.
            DocumentAccessLink::where('document_id', $document->id)
                ->where('role', $role)
                ->where('expires_at', '<=', now())
                ->delete();

            $existing = DocumentAccessLink::query()
                ->activeForRole($document->id, $role)
                ->first();

            if ($existing) {
                throw new BusinessLogicException(
                    "A share link with the '{$role}' role already exists for this document."
                );
            }

            return $document->accessLinks()->create([
                'token' => Str::random(64),
                'role' => $role,
                'expires_at' => $expiresAt,
                'created_by' => $createdBy->id,
            ]);
        });
    }

    public function revoke(DocumentAccessLink $link): void
    {
        $link->delete();
    }
}
