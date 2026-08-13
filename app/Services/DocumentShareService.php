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

        if ($best === null && $document->general_access === self::GENERAL_ACCESS_ANYONE_WITH_LINK) {
            return $document->link_role;
        }

        return $best;
    }

    public function addUserShare(Document $document, User $user, string $role, User $invitedBy): DocumentShare
    {
        return DocumentShare::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $user->id],
            ['role' => $role, 'invited_by' => $invitedBy->id],
        );
    }

    public function updateUserShareRole(DocumentShare $share, string $newRole): void
    {
        $share->update(['role' => $newRole]);
    }

    public function removeUserShare(DocumentShare $share): void
    {
        $share->delete();
    }

    public function addDivisionShare(Document $document, Division $division, string $role, User $invitedBy): DocumentDivisionShare
    {
        return DocumentDivisionShare::updateOrCreate(
            ['document_id' => $document->id, 'division_id' => $division->id],
            ['role' => $role, 'invited_by' => $invitedBy->id],
        );
    }

    public function updateDivisionShareRole(DocumentDivisionShare $share, string $newRole): void
    {
        $share->update(['role' => $newRole]);
    }

    public function removeDivisionShare(DocumentDivisionShare $share): void
    {
        $share->delete();
    }

    public function updateGeneralAccess(Document $document, string $access): void
    {
        // Link-based access always defaults to Viewer; per-user roles are set
        // individually in the "Orang dengan akses" list, never via the link.
        $document->update([
            'general_access' => $access,
            'link_role' => $access === self::GENERAL_ACCESS_ANYONE_WITH_LINK ? 'viewer' : null,
        ]);
    }

    public function regenerateShareToken(Document $document): string
    {
        $token = Str::random(32);
        $document->update(['share_token' => $token]);

        return $token;
    }
}