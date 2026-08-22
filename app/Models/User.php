<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'division_id', 'system_role', 'is_active', 'profile_picture', 'nip', 'phone_number'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get avatar URL (storage or null).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->profile_picture ? asset('storage/' . $this->profile_picture) : null;
    }

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

    /**
     * All divisions the user belongs to (primary + additional via pivot).
     */
    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(Division::class)->withTimestamps();
    }

    /**
     * Companies assigned to this user.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }

    /**
     * Branches assigned to this user.
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    /**
     * IDs of every division the user is a member of.
     */
    public function allDivisionIds(): array
    {
        $ids = $this->divisions()->pluck('divisions.id')->all();

        if ($this->division_id) {
            $ids[] = $this->division_id;
        }

        return array_values(array_unique($ids));
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

    public function documentShares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function signature(): HasOne
    {
        return $this->hasOne(Signature::class);
    }

    public function requestedSignatures(): HasMany
    {
        return $this->hasMany(SignatureRequest::class, 'requester_id');
    }

    public function receivedSignatureRequests(): HasMany
    {
        return $this->hasMany(SignatureRequest::class, 'target_user_id');
    }

    public function hasSignature(): bool
    {
        return $this->signature !== null && file_exists($this->signature->absolute_path);
    }

    public function isAdmin(): bool
    {
        return $this->system_role === 'admin';
    }

    public function isDirector(): bool
    {
        return $this->system_role === 'direktur';
    }

    public function isHead(): bool
    {
        return $this->system_role === 'head';
    }

    /**
     * Hitung total approval yang menunggu tindakan pengguna (Head).
     */
    public function pendingApprovalsCount(): int
    {
        if (!$this->isHead()) {
            return 0;
        }

        $divisionIds = $this->allDivisionIds();
        if (empty($divisionIds)) {
            return 0;
        }

        $versionsCount = DocumentVersion::where('status', 'pending')
            ->whereNull('discarded_at')
            ->whereHas('document', fn($q) => $q->whereIn('division_id', $divisionIds))
            ->count();

        $rollbacksCount = Document::whereIn('division_id', $divisionIds)
            ->whereNotNull('pending_rollback_version_id')
            ->count();

        return $versionsCount + $rollbacksCount;
    }

    protected $with = ['signature'];
}
