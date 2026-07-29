<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentAccessLink;
use App\Models\User;
use Illuminate\Support\Str;

class AccessLinkService
{
    public function create(Document $document, string $role, ?string $expiresAt, User $createdBy): DocumentAccessLink
    {
        return $document->accessLinks()->create([
            'token' => Str::random(64),
            'role' => $role,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy->id,
        ]);
    }

    public function revoke(DocumentAccessLink $link): void
    {
        $link->delete();
    }
}
