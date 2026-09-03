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

    /**
     * IDs of all branches assigned to this user.
     */
    public function allBranchIds(): array
    {
        return $this->branches()->pluck('branches.id')->all();
    }

    /**
     * IDs of all companies assigned to this user.
     */
    public function allCompanyIds(): array
    {
        return $this->companies()->pluck('companies.id')->all();
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

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    public function signature(): HasOne
    {
        return $this->hasOne(Signature::class)->where('type', 'original');
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
        return $this->signatures()->where('type', 'original')->exists();
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
     * Hitung total approval yang menunggu tindakan pengguna (Head / Direktur / Admin).
     */
    public function pendingApprovalsCount(): int
    {
        if (!$this->isHead() && !$this->isDirector() && !$this->isAdmin()) {
            return 0;
        }

        if ($this->isAdmin() || $this->isDirector()) {
            $companyIds = $this->companies()->pluck('companies.id')->all();

            $versionsQuery = DocumentVersion::where('status', 'pending')
                ->whereNull('discarded_at');

            $rollbacksQuery = Document::whereNotNull('pending_rollback_version_id');
            $renamesQuery = Document::whereNotNull('pending_title')
                ->where('pending_title', '!=', '');

            if (!$this->isAdmin() && !empty($companyIds)) {
                $companyFilter = function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)
                      ->orWhereHas('branch', fn($bq) => $bq->whereIn('company_id', $companyIds));
                };
                $versionsQuery->whereHas('document', $companyFilter);
                $rollbacksQuery->where($companyFilter);
                $renamesQuery->where($companyFilter);
            }

            return $versionsQuery->count() + $rollbacksQuery->count() + $renamesQuery->count();
        }

        $divisionIds = $this->allDivisionIds();
        if (empty($divisionIds)) {
            return 0;
        }

        $versionsCount = DocumentVersion::where('status', 'pending')
            ->whereNull('discarded_at')
            ->whereHas('document', fn($q) => $q->whereIn('division_id', $divisionIds)->visibleTo($this))
            ->count();

        $rollbacksCount = Document::whereIn('division_id', $divisionIds)
            ->visibleTo($this)
            ->whereNotNull('pending_rollback_version_id')
            ->count();

        $renamesCount = Document::whereIn('division_id', $divisionIds)
            ->visibleTo($this)
            ->whereNotNull('pending_title')
            ->where('pending_title', '!=', '')
            ->count();

        return $versionsCount + $rollbacksCount + $renamesCount;
    }

    /**
     * Hitung total dokumen baru yang dibagikan kepada pengguna yang belum dibuka / dibaca.
     * Hanya muncul pada pengguna yang diberi/menerima akses, bukan pemilik.
     * Dihitung per dokumen (meskipun pengguna diberi akses bertahap/berganda seperti viewer lalu editor).
     */
    public function sharedDocumentsCount(): int
    {
        return $this->unreadNotifications()
            ->where('data->type', 'document_shared')
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;
                if (!empty($data['document_id'])) {
                    return (string) $data['document_id'];
                }
                if (!empty($data['url']) && preg_match('/\/documents\/([0-9a-f\-]{36}|[0-9]+)/', $data['url'], $matches)) {
                    return (string) $matches[1];
                }
                return $notification->id;
            })
            ->unique()
            ->count();
    }

    protected $with = ['signature'];
}
