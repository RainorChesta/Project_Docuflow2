<?php

namespace App\Services;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentDivisionShare;
use App\Models\DocumentShare;
use App\Models\User;
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
     * Owner (weight 3) always wins; personal share beats division share;
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

        $divisionIds = $user->allDivisionIds();

        if (!empty($divisionIds)) {
            $divisionRole = DocumentDivisionShare::where('document_id', $document->id)
                ->whereIn('division_id', $divisionIds)
                ->pluck('role')
                ->map(fn($role) => self::ROLE_WEIGHTS[$role])
                ->max();

            if ($divisionRole !== null && $divisionRole > $bestWeight) {
                $best = array_search($divisionRole, self::ROLE_WEIGHTS, true);
                $bestWeight = $divisionRole;
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
            $user->notify(new \App\Notifications\DocumentSharedWithUser($document, $role, $invitedBy->name));
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
                $user->notify(new \App\Notifications\DocumentAccessRevoked($document, $revokedBy->name));
            }
        }
    }

    public function addDivisionShare(Document $document, Division $division, string $role, User $invitedBy): DocumentDivisionShare
    {
        $share = DocumentDivisionShare::updateOrCreate(
            ['document_id' => $document->id, 'division_id' => $division->id],
            ['role' => $role, 'invited_by' => $invitedBy->id],
        );

        $divisionUsers = User::where('division_id', $division->id)
            ->where('is_active', true)
            ->where('id', '!=', $invitedBy->id)
            ->get();

        foreach ($divisionUsers as $member) {
            $member->notify(new \App\Notifications\DocumentSharedWithDivision(
                $document,
                $division->name,
                $role,
                $invitedBy->name
            ));
        }

        return $share;
    }

    public function updateDivisionShareRole(DocumentDivisionShare $share, string $newRole): void
    {
        $share->update(['role' => $newRole]);
    }

    public function removeDivisionShare(DocumentDivisionShare $share, ?User $revokedBy = null): void
    {
        $documentId = $share->document_id;
        $divisionId = $share->division_id;
        $document = $share->document ?? Document::withTrashed()->find($documentId);
        $divisionName = $share->division?->name;

        $share->delete();

        if ($divisionId && $documentId) {
            $divisionUsers = User::where('division_id', $divisionId)
                ->where('is_active', true)
                ->get();

            foreach ($divisionUsers as $member) {
                $member->unreadNotifications()
                    ->where('data->type', 'document_shared')
                    ->where('data->document_id', $documentId)
                    ->delete();

                if ($revokedBy && $member->id !== $revokedBy->id && $document) {
                    $member->notify(new \App\Notifications\DocumentAccessRevoked($document, $revokedBy->name, $divisionName));
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