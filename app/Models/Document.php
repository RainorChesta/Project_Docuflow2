<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'document_number', 'title', 'visibility', 'division_id', 'owner_id',
        'document_type_id', 'is_public', 'current_version_id',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public const VISIBILITY_GENERAL = 'general';
    public const VISIBILITY_DIVISION = 'division';
    public const VISIBILITY_PERSONAL = 'personal';

    public function isGeneral(): bool
    {
        return $this->visibility === self::VISIBILITY_GENERAL;
    }

    public function isDivision(): bool
    {
        return $this->visibility === self::VISIBILITY_DIVISION;
    }

    public function isPersonal(): bool
    {
        return $this->visibility === self::VISIBILITY_PERSONAL;
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function accessLinks(): HasMany
    {
        return $this->hasMany(DocumentAccessLink::class);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('versions', fn($q) => $q->where('status', 'active'));
    }

    public function scopePending($query)
    {
        return $query->whereHas('versions', fn($q) => $q->where('status', 'pending'));
    }

    /**
     * General (public) documents — visible to every authenticated user.
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_GENERAL);
    }

    /**
     * Division-scoped documents the given user may see (Dokumen Divisi tab).
     */
    public function scopeDivision(Builder $query, User $user): Builder
    {
        $divisionIds = $user->allDivisionIds();

        if (empty($divisionIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('visibility', self::VISIBILITY_DIVISION)
            ->whereIn('division_id', $divisionIds);
    }

    /**
     * Documents the given user is allowed to see (row-level visibility).
     * Admin sees everything. Regular users see: general docs, own docs
     * (any scope), and division docs of any division they belong to.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $divisionIds = $user->allDivisionIds();

        return $query->where(function (Builder $q) use ($user, $divisionIds) {
            $q->where('visibility', self::VISIBILITY_GENERAL)
                ->orWhere('owner_id', $user->id)
                ->orWhere(function (Builder $sub) use ($divisionIds) {
                    $sub->where('visibility', self::VISIBILITY_DIVISION)
                        ->whereIn('division_id', $divisionIds);
                });
        });
    }

    /**
     * Documents owned by the given user (My Documents tab).
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('owner_id', $user->id);
    }
}
