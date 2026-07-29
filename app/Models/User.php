<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'division_id', 'system_role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'owner_id');
    }

    public function authoredVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'author_id');
    }

    public function reviewedVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'reviewer_id');
    }

    public function createdLinks(): HasMany
    {
        return $this->hasMany(DocumentAccessLink::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->system_role === 'admin';
    }

    public function isHead(): bool
    {
        return $this->system_role === 'head';
    }
}
