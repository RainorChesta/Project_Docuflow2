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
        'expiration_reminder_days', 'expiration_notified_at',
        'approver_id', 'approver_role',
        'director_notified_at', 'director_read_at', 'director_acknowledged_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_expired' => 'boolean',
            'is_expiration_notified' => 'boolean',
            'rollback_requested_at' => 'datetime',
            'rename_requested_at' => 'datetime',
            'summary_started_at' => 'datetime',
            'summary_completed_at' => 'datetime',
            'expiration_date' => 'date',
            'expiration_reminder_days' => 'integer',
            'expiration_notified_at' => 'datetime',
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

        static::saved(function (Document $document) {
            if ($document->hasExpiration() && $document->isReleased()) {
                $document->checkExpirationNotification();
            }
        });
    }

    /**
     * Get effective reminder days threshold for this document (falls back to global setting, default 30 days).
     */
    public function getEffectiveReminderDaysAttribute(): int
    {
        return $this->expiration_reminder_days ?? (int) \App\Models\Setting::get('document_expiration_reminder_days', 30);
    }

    /**
     * Determine if this document has an explicitly configured expiration date.
     */
    public function hasExpiration(): bool
    {
        return !is_null($this->expiration_date);
    }

    /**
     * Determine if this document is expired (only applicable if expiration_date is set and document is released).
     */
    public function isExpired(): bool
    {
        if (!$this->hasExpiration() || !$this->isReleased()) {
            return false;
        }

        if ($this->is_expired) {
            return true;
        }

        return now()->startOfDay()->greaterThan($this->expiration_date->startOfDay());
    }

    /**
     * Determine if this document is expiring soon within the given days threshold (or its effective reminder days).
     */
    public function isExpiringSoon(?int $days = null): bool
    {
        if (!$this->hasExpiration() || !$this->isReleased() || $this->isExpired()) {
            return false;
        }

        $threshold = $days ?? $this->effective_reminder_days;
        $remainingDays = $this->daysUntilExpiration();

        return $remainingDays !== null && $remainingDays >= 0 && $remainingDays <= $threshold;
    }

    /**
     * Get remaining days until expiration (null if no expiration configured or not released).
     */
    public function daysUntilExpiration(): ?int
    {
        if (!$this->hasExpiration() || !$this->isReleased()) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiration_date->startOfDay(), false);
    }

    /**
     * Check and immediately dispatch expiration notification to owner if within thresholds.
     */
    public function checkExpirationNotification(): void
    {
        if (!$this->hasExpiration() || !$this->isReleased()) {
            return;
        }

        $days = $this->daysUntilExpiration();
        if ($days === null) {
            return;
        }

        $owner = $this->owner;
        if (!$owner) {
            return;
        }

        // Fetch authoritative current notification status from database to prevent race conditions & duplicate dispatches
        $dbStatus = static::where('id', $this->id)->value('expiration_notif_status');
        $currentStatus = $dbStatus ?? $this->expiration_notif_status;

        $threshold = $this->effective_reminder_days;

        if ($days < 0) {
            if (!$this->is_expired || $currentStatus !== 'expired') {
                $this->updateQuietly([
                    'is_expired' => true,
                    'is_expiration_notified' => true,
                    'expiration_notif_status' => 'expired',
                    'expiration_notified_at' => now(),
                ]);
                $this->is_expired = true;
                $this->is_expiration_notified = true;
                $this->expiration_notif_status = 'expired';
                $this->expiration_notified_at = now();

                $owner->notify(new \App\Notifications\DocumentExpiredNotification($this));
            }
        } elseif ($days <= 1) {
            if ($currentStatus !== '1day' && $currentStatus !== 'expired') {
                $this->updateQuietly([
                    'expiration_notif_status' => '1day',
                    'is_expiration_notified' => true,
                    'expiration_notified_at' => now(),
                ]);
                $this->is_expiration_notified = true;
                $this->expiration_notif_status = '1day';
                $this->expiration_notified_at = now();

                $owner->notify(new \App\Notifications\DocumentExpiringWarningNotification($this, $days, '1day'));
            }
        } elseif ($days <= 7) {
            if (!in_array($currentStatus, ['7days', '1day', 'expired'], true)) {
                $this->updateQuietly([
                    'expiration_notif_status' => '7days',
                    'is_expiration_notified' => true,
                    'expiration_notified_at' => now(),
                ]);
                $this->is_expiration_notified = true;
                $this->expiration_notif_status = '7days';
                $this->expiration_notified_at = now();

                $owner->notify(new \App\Notifications\DocumentExpiringWarningNotification($this, $days, '7days'));
            }
        } elseif ($days <= $threshold) {
            $statusKey = $threshold . 'days';
            if ($currentStatus !== $statusKey && !in_array($currentStatus, ['7days', '1day', 'expired'], true)) {
                $this->updateQuietly([
                    'expiration_notif_status' => $statusKey,
                    'is_expiration_notified' => true,
                    'expiration_notified_at' => now(),
                ]);
                $this->is_expiration_notified = true;
                $this->expiration_notif_status = $statusKey;
                $this->expiration_notified_at = now();

                $owner->notify(new \App\Notifications\DocumentExpiringWarningNotification($this, $days, $statusKey));
            }
        }
    }

    /**
     * Get the effective activation / approval timestamp in WIB (Asia/Jakarta).
     */
    public function getActivatedAtAttribute(): \Carbon\Carbon
    {
        $date = $this->currentVersion?->reviewed_at 
            ?? $this->currentVersion?->created_at 
            ?? $this->created_at 
            ?? now();

        return $date->copy()->timezone('Asia/Jakarta');
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
     * Determine if the document is currently locked for editing because it has an active pending version in the approval pipeline.
     */
    public function isLockedForEditing(): bool
    {
        if ($this->relationLoaded('versions')) {
            return $this->versions
                ->filter(fn($v) => !$v->discarded_at)
                ->contains('status', 'pending');
        }

        return $this->versions()
            ->where('status', 'pending')
            ->whereNull('discarded_at')
            ->exists();
    }

    /**
     * Determine if the document currently has an active non-discarded draft version.
     */
    public function hasDraft(): bool
    {
        if ($this->relationLoaded('versions')) {
            return $this->versions
                ->filter(fn($v) => !$v->discarded_at)
                ->contains('status', 'draft');
        }

        return $this->versions()
            ->where('status', 'draft')
            ->whereNull('discarded_at')
            ->exists();
    }

    /**
     * Determine if the document currently has an active non-discarded pending version.
     */
    public function hasPending(): bool
    {
        if ($this->relationLoaded('versions')) {
            return $this->versions
                ->filter(fn($v) => !$v->discarded_at)
                ->contains('status', 'pending');
        }

        return $this->versions()
            ->where('status', 'pending')
            ->whereNull('discarded_at')
            ->exists();
    }

    /**
     * Determine if the document currently has an active non-discarded rejected version.
     */
    public function hasRejected(): bool
    {
        if ($this->relationLoaded('versions')) {
            return $this->versions
                ->filter(fn($v) => !$v->discarded_at)
                ->contains('status', 'rejected');
        }

        return $this->versions()
            ->where('status', 'rejected')
            ->whereNull('discarded_at')
            ->exists();
    }

    /**
     * Determine if the document has been approved and released (active v1 or finalized version).
     */
    public function isReleased(): bool
    {
        return !is_null($this->current_version_id)
            || ($this->currentVersion && $this->currentVersion->status === 'active')
            || $this->versions()->where('status', 'active')->exists();
    }

    /**
     * Get the newest pending version undergoing review.
     */
    public function pendingVersion(): ?DocumentVersion
    {
        if ($this->relationLoaded('versions')) {
            return $this->versions
                ->filter(fn($v) => !$v->discarded_at && $v->status === 'pending')
                ->sortByDesc('id')
                ->first();
        }

        return $this->versions()
            ->where('status', 'pending')
            ->whereNull('discarded_at')
            ->latest('id')
            ->first();
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

        if ($user->isPicKlinik()) {
            $directBranchIds = $user->allBranchIds();
            $directCompanyIds = $user->allCompanyIds();

            return $query->whereIn('visibility', [self::VISIBILITY_UNIT_KERJA, 'division'])
                ->where(function ($q) use ($directBranchIds, $directCompanyIds) {
                    if (!empty($directBranchIds)) {
                        $q->whereIn('documents.branch_id', $directBranchIds);
                    } elseif (!empty($directCompanyIds)) {
                        $q->whereIn('documents.company_id', $directCompanyIds);
                    }
                })
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

        if ($user->isPicKlinik()) {
            return $query->where(function (Builder $q) use ($user, $directBranchIds, $companyIds) {
                // Shared directly with user
                $q->whereHas('shares', fn(Builder $s) => $s->where('user_id', $user->id));

                // Assigned branches
                if (!empty($directBranchIds)) {
                    $q->orWhereIn('documents.branch_id', $directBranchIds);
                } elseif (!empty($companyIds)) {
                    $q->orWhereIn('documents.company_id', $companyIds);
                }

                // Distributed to their branch or company
                $q->orWhereHas('distributions', fn(Builder $dist) => $dist->where(function ($dq) use ($directBranchIds, $companyIds) {
                    if (!empty($directBranchIds)) {
                        $dq->whereIn('target_branch_id', $directBranchIds);
                    } elseif (!empty($companyIds)) {
                        $dq->orWhereHas('targetBranch', fn($tb) => $tb->whereIn('company_id', $companyIds));
                    }
                }));
            });
        }

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

    /**
     * Check if the document has a corporate letterhead / kop surat attached.
     */
    public function hasKop(): bool
    {
        if (!is_null($this->corporate_soft_file_id)) {
            return true;
        }

        if ($this->template_id && $this->template?->corporate_soft_file_id) {
            return true;
        }

        $version = $this->displayVersion();
        if ($version && $version->file_path) {
            $disk = \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'));
            if ($disk->exists($version->file_path)) {
                $fileBytes = $disk->get($version->file_path);
                if (app(\App\Services\OnlyOfficeService::class)->docxHasExplicitHeaderAndFooter($fileBytes)) {
                    return true;
                }
            }
        }

        $content = $version?->content;
        if ($content && (
            str_contains($content, 'kop-surat') ||
            str_contains($content, 'kop_header') ||
            str_contains($content, 'class="kop') ||
            str_contains($content, "class='kop") ||
            str_contains($content, 'id="kop') ||
            str_contains($content, "id='kop")
        )) {
            return true;
        }

        return false;
    }
}
