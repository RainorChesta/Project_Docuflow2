<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'document_number', 'format_choice', 'numbering_scheme', 'title', 'summary', 'summary_status', 'summary_error',
        'summary_started_at', 'summary_completed_at', 'visibility', 'unit_kerja_id', 'company_id', 'branch_id', 'owner_id',
        'document_type_id', 'template_id', 'corporate_soft_file_id', 'is_public', 'current_version_id',
        'pending_rollback_version_id', 'rollback_requested_by_id', 'rollback_requested_at',
        'pending_title', 'rename_requested_by_id', 'rename_requested_at', 'rename_request_notes',
        'paper_size', 'paper_margin',
        'general_access', 'link_role', 'share_token',
        'expiration_date', 'is_expired', 'is_expiration_notified', 'expiration_notif_status',
        'approver_id', 'approver_role',
        'director_notified_at', 'director_read_at', 'director_acknowledged_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_expired' => 'boolean',
            'rollback_requested_at' => 'datetime',
            'rename_requested_at' => 'datetime',
            'summary_started_at' => 'datetime',
            'summary_completed_at' => 'datetime',
            'expiration_date' => 'date',
            'paper_margin' => 'array',
            'director_notified_at' => 'datetime',
            'director_read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            if (empty($document->share_token)) {
                $document->share_token = \Illuminate\Support\Str::random(32);
            }
            if (empty($document->general_access)) {
                $document->general_access = \App\Services\DocumentShareService::GENERAL_ACCESS_RESTRICTED;
            }
        });
    }

    /**
     * Get the effective expiration date.
     * Uses manual expiration_date if set, otherwise calculates from created_at + default_retention_years.
     */
    protected function expiresAt(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function () {
                if ($this->expiration_date) {
                    return $this->expiration_date;
                }
                
                $retentionYears = (int) Setting::get('document_retention_years', config('app.document_retention_years', 2));
                return $this->created_at ? $this->created_at->copy()->addYears($retentionYears) : null;
            }
        );
    }

    public const SUMMARY_PENDING = 'pending';
    public const SUMMARY_PROCESSING = 'processing';
    public const SUMMARY_COMPLETED = 'completed';
    public const SUMMARY_FAILED = 'failed';

    public function isSummaryCompleted(): bool
    {
        return $this->summary_status === self::SUMMARY_COMPLETED;
    }

    public const VISIBILITY_GENERAL = 'general';
    public const VISIBILITY_UNIT_KERJA = 'unit_kerja';
    public const VISIBILITY_DIVISION = 'unit_kerja'; // Backward-compatible alias
    public const VISIBILITY_PERSONAL = 'personal';

    public function isGeneral(): bool
    {
        return $this->visibility === self::VISIBILITY_GENERAL;
    }

    public function isUnitKerja(): bool
    {
        return $this->visibility === self::VISIBILITY_UNIT_KERJA || $this->visibility === 'division';
    }

    public function isDivision(): bool
    {
        return $this->isUnitKerja();
    }

    public function isPersonal(): bool
    {
        return $this->visibility === self::VISIBILITY_PERSONAL;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function corporateSoftFile(): BelongsTo
    {
        return $this->belongsTo(CorporateSoftFile::class, 'corporate_soft_file_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approverUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(DocumentApprovalLog::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function pendingRollbackVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'pending_rollback_version_id');
    }

    public function rollbackRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rollback_requested_by_id');
    }

    public function hasPendingRollback(): bool
    {
        return !is_null($this->pending_rollback_version_id);
    }

    public function renameRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rename_requested_by_id');
    }

    public function hasPendingRename(): bool
    {
        return !empty(trim($this->pending_title ?? ''));
    }

    /**
     * Version to display: newest non-discarded pending (edit terbaru yang
     * belum di-approve), else approved current version, else latest draft.
     */
    public function displayVersion(): ?DocumentVersion
    {
        $versions = $this->versions
            ->filter(fn($v) => !$v->discarded_at)
            ->sortByDesc('version_number');

        $pending = $versions->first(fn($v) => $v->status === 'pending');
        if ($pending) {
            return $pending;
        }

        if ($this->currentVersion) {
            return $this->currentVersion;
        }

        return $versions->first(fn($v) => $v->status === 'draft') ?? $versions->first();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function accessLinks(): HasMany
    {
        return $this->hasMany(DocumentAccessLink::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function unitKerjaShares(): HasMany
    {
        return $this->hasMany(DocumentUnitKerjaShare::class);
    }

    public function signatureRequests(): HasMany
    {
        return $this->hasMany(SignatureRequest::class);
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(DocumentApprovalStep::class);
    }

    public function directorAcknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_acknowledged_by_id');
    }

    /**
     * Get the active pending approval step for this document's latest pending version.
     */
    public function currentApprovalStep(): ?DocumentApprovalStep
    {
        $version = $this->displayVersion();
        if (!$version) {
            return null;
        }

        return DocumentApprovalStep::where('version_id', $version->id)
            ->where('status', 'pending')
            ->first();
    }

    /**
     * Check if this document contains a signature request assigned to a Director.
     */
    public function hasDirectorSignature(): bool
    {
        return $this->signatureRequests()
            ->whereHas('targetUser', fn($q) => $q->where('system_role', 'direktur'))
            ->exists();
    }

    /**
     * Check if director has acknowledged reading this document.
     */
    public function isDirectorRead(): bool
    {
        return !is_null($this->director_read_at);
    }

    /**
     * Check if director was notified about this released document.
     */
    public function isDirectorNotified(): bool
    {
        return !is_null($this->director_notified_at);
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
     * General (public) documents — visible to every authenticated user within context scope.
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_GENERAL)
            ->whereHas('versions', fn($q) => $q->where('status', 'active'));
    }

    /**
     * Work Unit-scoped documents the given user may see (Dokumen Unit Kerja tab).
     */
    public function scopeUnitKerja(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query->whereIn('visibility', [self::VISIBILITY_UNIT_KERJA, 'division'])
                ->whereHas('versions', fn($q) => $q->where('status', 'active'));
        }

        $unitKerjaIds = $user->allUnitKerjaIds();

        if (empty($unitKerjaIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('visibility', [self::VISIBILITY_UNIT_KERJA, 'division'])
            ->whereIn('unit_kerja_id', $unitKerjaIds)
            ->whereHas('versions', fn($q) => $q->where('status', 'active'));
    }

    /**
     * Backward compatibility alias for scopeUnitKerja.
     */
    public function scopeDivision(Builder $query, User $user): Builder
    {
        return $this->scopeUnitKerja($query, $user);
    }

    /**
     * Documents the given user is allowed to see (row-level visibility).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        $directCompanyIds = $user->allCompanyIds();
        $directBranchIds = $user->allBranchIds();
        $unitKerjaIds = $user->allUnitKerjaIds();

        // Include company IDs inferred from assigned branches
        $branchCompanyIds = !empty($directBranchIds)
            ? Branch::whereIn('id', $directBranchIds)->pluck('company_id')->filter()->all()
            : [];
        $companyIds = array_values(array_unique(array_merge($directCompanyIds, $branchCompanyIds)));

        // Include branch IDs belonging to assigned companies
        $allCompanyBranchIds = !empty($companyIds)
            ? Branch::whereIn('company_id', $companyIds)->pluck('id')->filter()->all()
            : [];
        $branchIds = array_values(array_unique(array_merge($directBranchIds, $allCompanyBranchIds)));

        if ($user->isDirector()) {
            if (!empty($branchIds) || !empty($companyIds)) {
                $query->where(function ($q) use ($branchIds, $companyIds) {
                    if (!empty($branchIds)) {
                        $q->whereIn('branch_id', $branchIds);
                    }
                    if (!empty($companyIds)) {
                        $q->orWhereIn('company_id', $companyIds)
                          ->orWhereHas('branch', fn($b) => $b->whereIn('company_id', $companyIds));
                    }
                    $q->orWhereHas('distributions', fn(Builder $dist) => $dist->where(function ($dq) use ($branchIds, $companyIds) {
                        if (!empty($branchIds)) {
                            $dq->whereIn('target_branch_id', $branchIds);
                        }
                        if (!empty($companyIds)) {
                            $dq->orWhereHas('targetBranch', fn($tb) => $tb->whereIn('company_id', $companyIds));
                        }
                    }));
                });
            }
            return $query;
        }

        return $query->where(function (Builder $q) use ($user, $unitKerjaIds, $branchIds, $companyIds) {
            // Explicitly shared with user or unit kerja
            $q->whereHas('shares', fn(Builder $s) => $s->where('user_id', $user->id));

            if (!empty($unitKerjaIds)) {
                $q->orWhereHas('unitKerjaShares', fn(Builder $us) => $us->whereIn('unit_kerja_id', $unitKerjaIds));
            }

            // Cross-branch distributed documents to user's branches or companies
            $q->orWhereHas('distributions', fn(Builder $dist) => $dist->where(function ($dq) use ($branchIds, $companyIds) {
                if (!empty($branchIds)) {
                    $dq->whereIn('target_branch_id', $branchIds);
                }
                if (!empty($companyIds)) {
                    $dq->orWhereHas('targetBranch', fn($tb) => $tb->whereIn('company_id', $companyIds));
                }
            }));

            // Documents within the user's accessible branch/company scope
            $q->orWhere(function (Builder $inScope) use ($user, $unitKerjaIds, $branchIds, $companyIds) {
                if (!empty($branchIds) || !empty($companyIds)) {
                    $inScope->where(function (Builder $b) use ($branchIds, $companyIds) {
                        if (!empty($branchIds)) {
                            $b->whereIn('branch_id', $branchIds);
                        }
                        if (!empty($companyIds)) {
                            $b->orWhereIn('company_id', $companyIds)
                              ->orWhereHas('branch', fn($br) => $br->whereIn('company_id', $companyIds));
                        }
                    });
                }

                $inScope->where(function (Builder $sub) use ($user, $unitKerjaIds) {
                    $sub->where('visibility', self::VISIBILITY_GENERAL)
                        ->orWhere('owner_id', $user->id)
                        ->orWhere(function (Builder $d) use ($unitKerjaIds) {
                            $d->whereIn('visibility', [self::VISIBILITY_UNIT_KERJA, 'division'])
                              ->whereIn('unit_kerja_id', $unitKerjaIds);
                        });
                });
            });
        });
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(DocumentDistribution::class);
    }

    /**
     * Documents owned by the given user (My Documents tab).
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('owner_id', $user->id);
    }

    /**
     * Check if document uses new numbering format.
     */
    public function isNewFormat(): bool
    {
        return ($this->format_choice ?? 'baru') === 'baru';
    }

    /**
     * Check if document uses old numbering format.
     */
    public function isOldFormat(): bool
    {
        return $this->format_choice === 'lama';
    }

    /**
     * Scope query to filter by format choice (baru / lama).
     */
    public function scopeFormatChoice(Builder $query, string $format): Builder
    {
        return $query->where('format_choice', $format);
    }
}
