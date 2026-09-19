<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentUnitKerjaShare;
use App\Models\UnitKerja;
use App\Models\User;
use App\Notifications\DocumentAccessRevoked;
use App\Notifications\DocumentSharedWithUnitKerja;
use App\Notifications\DocumentSharedWithUser;
use Illuminate\Support\Str;

class DocumentShareService
{
    public const ROLE_WEIGHTS = [
        'owner' => 3,
        'editor' => 2,
        'viewer' => 1,
    ];

    public const GENERAL_ACCESS_RESTRICTED = 'restricted';
    public const GENERAL_ACCESS_ANYONE_WITH_LINK = 'anyone_with_link';

    /**
     * Highest-weighted role the user has on the document, or null if none.
     * Owner (weight 3) always wins; personal share beats unit kerja share;
     * link_role only counts when general_access is 'anyone_with_link'.
     */
    public function resolveEffectiveRole(Document $document, User $user): ?string
    {
        if ($user->id === $document->owner_id) {
            return 'owner';
        }

        $best = null;
        $bestWeight = 0;

        $personal = DocumentShare::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->value('role');

        if ($personal !== null) {
            $best = $personal;
            $bestWeight = self::ROLE_WEIGHTS[$personal];
        }

        $unitKerjaIds = $user->allUnitKerjaIds();

        if (!empty($unitKerjaIds)) {
            $unitKerjaRole = DocumentUnitKerjaShare::where('document_id', $document->id)
                ->whereIn('unit_kerja_id', $unitKerjaIds)
                ->pluck('role')
                ->map(fn($role) => self::ROLE_WEIGHTS[$role] ?? 0)
                ->max();

            if ($unitKerjaRole !== null && $unitKerjaRole > $bestWeight) {
                $best = array_search($unitKerjaRole, self::ROLE_WEIGHTS, true);
                $bestWeight = $unitKerjaRole;
            }
        }

        if ($document->general_access === self::GENERAL_ACCESS_ANYONE_WITH_LINK && $document->link_role !== null) {
            $linkRole = $document->link_role;
            $linkWeight = self::ROLE_WEIGHTS[$linkRole] ?? 1;
            if ($linkWeight > $bestWeight) {
                $best = $linkRole;
                $bestWeight = $linkWeight;
            }
        }

        return $best;
    }

    public function addUserShare(Document $document, User $user, string $role, User $invitedBy): DocumentShare
    {
        $share = DocumentShare::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $user->id],
            ['role' => $role, 'invited_by' => $invitedBy->id],
        );

        if ($user->id !== $invitedBy->id) {
            $user->notify(new DocumentSharedWithUser($document, $role, $invitedBy->name));
        }

        return $share;
    }

    public function updateUserShareRole(DocumentShare $share, string $newRole): void
    {
        $share->update(['role' => $newRole]);
    }

    public function removeUserShare(DocumentShare $share, ?User $revokedBy = null): void
    {
        $documentId = $share->document_id;
        $userId = $share->user_id;
        $document = $share->document ?? Document::withTrashed()->find($documentId);

        $share->delete();

        if ($userId && $documentId) {
            $user = User::find($userId);
            $user?->unreadNotifications()
                ->where('data->type', 'document_shared')
                ->where('data->document_id', $documentId)
                ->delete();

            if ($revokedBy && $user && $user->id !== $revokedBy->id && $document) {
                $user->notify(new DocumentAccessRevoked($document, $revokedBy->name));
            }
        }
    }

    public function addUnitKerjaShare(Document $document, UnitKerja $unitKerja, string $role, User $invitedBy): DocumentUnitKerjaShare
    {
        $share = DocumentUnitKerjaShare::updateOrCreate(
            ['document_id' => $document->id, 'unit_kerja_id' => $unitKerja->id],
            ['role' => $role, 'invited_by' => $invitedBy->id],
        );

        $unitUsers = User::where(function ($q) use ($unitKerja) {
                $q->where('unit_kerja_id', $unitKerja->id)
                  ->orWhereHas('unitKerjas', fn($uq) => $uq->where('unit_kerjas.id', $unitKerja->id));
            })
            ->where('is_active', true)
            ->where('id', '!=', $invitedBy->id)
            ->get();

        foreach ($unitUsers as $member) {
            $member->notify(new DocumentSharedWithUnitKerja(
                $document,
                $unitKerja->nama_unit_kerja,
                $role,
                $invitedBy->name
            ));
        }

        return $share;
    }

    public function updateUnitKerjaShareRole(DocumentUnitKerjaShare $share, string $newRole): void
    {
        $share->update(['role' => $newRole]);
    }

    public function removeUnitKerjaShare(DocumentUnitKerjaShare $share, ?User $revokedBy = null): void
    {
        $documentId = $share->document_id;
        $unitKerjaId = $share->unit_kerja_id;
        $document = $share->document ?? Document::withTrashed()->find($documentId);
        $unitKerjaName = $share->unitKerja?->nama_unit_kerja;

        $share->delete();

        if ($unitKerjaId && $documentId) {
            $unitUsers = User::where(function ($q) use ($unitKerjaId) {
                    $q->where('unit_kerja_id', $unitKerjaId)
                      ->orWhereHas('unitKerjas', fn($uq) => $uq->where('unit_kerjas.id', $unitKerjaId));
                })
                ->where('is_active', true)
                ->get();

            foreach ($unitUsers as $member) {
                $member->unreadNotifications()
                    ->where('data->type', 'document_shared')
                    ->where('data->document_id', $documentId)
                    ->delete();

                if ($revokedBy && $member->id !== $revokedBy->id && $document) {
                    $member->notify(new DocumentAccessRevoked($document, $revokedBy->name, $unitKerjaName));
                }
            }
        }
    }

    public function updateGeneralAccess(Document $document, string $access, ?string $linkRole = null): void
    {
        $role = $access === self::GENERAL_ACCESS_ANYONE_WITH_LINK ? ($linkRole ?? $document->link_role ?? 'viewer') : null;
        $document->update([
            'general_access' => $access,
            'link_role' => $role,
        ]);
    }

    public function regenerateShareToken(Document $document): string
    {
        $token = Str::random(32);
        $document->update(['share_token' => $token]);

        return $token;
    }
}