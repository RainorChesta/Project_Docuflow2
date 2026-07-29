<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($document->is_public) return true;
        if ($user->isAdmin()) return true;
        return $user->division_id === $document->division_id;
    }

    public function create(User $user): bool
    {
        return $user->division_id !== null && $user->is_active;
    }

    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->owner_id || $user->isAdmin();
    }

    public function approve(User $user, Document $document): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isHead() && $user->division_id === $document->division_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->isAdmin();
    }
}
