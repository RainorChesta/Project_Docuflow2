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

#[Fillable(['name', 'email', 'password', 'unit_kerja_id', 'system_role', 'is_active', 'profile_picture', 'nip', 'phone_number'])]
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

    public function setNipAttribute($value): void
    {
        $this->attributes['nip'] = filled($value) ? trim($value) : null;
    }

    public function setPhoneNumberAttribute($value): void
    {
        $this->attributes['phone_number'] = filled($value) ? trim($value) : null;
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    /**
     * All unit kerjas the user belongs to (primary + additional via pivot).
     */
    public function unitKerjas(): BelongsToMany
    {
        return $this->belongsToMany(UnitKerja::class, 'unit_kerja_user')->withPivot('branch_id')->withTimestamps();
    }

    /**
     * IDs of unit kerjas assigned to the user, optionally scoped to a branch.
     */
    public function allUnitKerjaIds(?int $branchId = null): array
    {
        if ($branchId !== null) {
            $ids = $this->unitKerjas()
                ->where(function ($q) use ($branchId) {
                    $q->wherePivot('branch_id', $branchId)
                      ->orWhereNull('unit_kerja_user.branch_id');
                })
                ->pluck('unit_kerjas.id')
                ->all();

            if (!empty($ids)) {
                return array_values(array_unique($ids));
            }
        }

        $ids = $this->unitKerjas()->pluck('unit_kerjas.id')->all();

        if ($this->unit_kerja_id) {
            $ids[] = $this->unit_kerja_id;
        }

        return array_values(array_unique($ids));
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
     * Map of [branch_id => [unit_kerja_id, ...]] for branches.
     */
    public function getBranchUnitKerjasMap(): array
    {
        $map = [];
        foreach ($this->unitKerjas as $uk) {
            $bId = $uk->pivot->branch_id ?? null;
            if ($bId) {
                $map[$bId][] = (string) $uk->id;
            }
        }
        return $map;
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

    public function hasSignature(?string $type = null): bool
    {
        if ($type !== null) {
            return $this->signatures()->where('type', $type)->exists();
        }

        return $this->signatures()->exists();
    }

    public function isAdmin(): bool
    {
        return $this->system_role === 'admin';
    }

    public function isDirector(): bool
    {
        return $this->system_role === 'direktur';
    }

    public function isPicKlinik(): bool
    {
        return $this->system_role === 'pic_klinik';
    }

    public function isHead(): bool
    {
        return $this->system_role === 'head';
    }

    public function isStaff(): bool
    {
        return $this->system_role === 'staff' || $this->system_role === 'user' || (!$this->isAdmin() && !$this->isDirector() && !$this->isPicKlinik() && !$this->isHead());
    }

    /**
     * Check if user can access corporate soft files feature.
     */
    public function canAccessCorporateSoftFiles(?CorporateSoftFile $file = null): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($file !== null) {
            return $file->isActive() && $file->isRoleAllowed($this->system_role);
        }

        return true;
    }

    /**
     * Check if user account is verified by administrator.
     * An account is verified when:
     * - User is admin (global access)
     * - User is direktur or pic_klinik and assigned to at least one company and branch
     * - User is head/staff and assigned to at least one unit kerja, company, and branch
     */
    public function isVerified(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $hasCompany = $this->relationLoaded('companies')
            ? $this->companies->isNotEmpty()
            : $this->companies()->exists();

        $hasBranch = $this->relationLoaded('branches')
            ? $this->branches->isNotEmpty()
            : $this->branches()->exists();

        if ($this->isDirector() || $this->isPicKlinik()) {
            return $hasCompany && $hasBranch;
        }

        $hasUnitKerja = !empty($this->unit_kerja_id) || (
            $this->relationLoaded('unitKerjas')
                ? $this->unitKerjas->isNotEmpty()
                : $this->unitKerjas()->exists()
        );

        return $hasUnitKerja && $hasCompany && $hasBranch;
    }

    /**
     * Check if user account is pending verification by administrator.
     */
    public function isPendingVerification(): bool
    {
        return !$this->isVerified();
    }

    /**
     * Terapkan filter konteks perusahaan dan cabang pada query dokumen.
     */
    protected function applyContextFilterToDocumentQuery($query, ?int $companyId = null, ?int $branchId = null, bool $scopedToContext = true): void
    {
        if ($branchId !== null) {
            $query->where(function ($sq) use ($branchId, $companyId) {
                $sq->where('documents.branch_id', $branchId);
                if ($companyId !== null) {
                    $sq->orWhere(function ($fallback) use ($companyId) {
                        $fallback->whereNull('documents.branch_id')
                                 ->where('documents.company_id', $companyId);
                    });
                }
            });
        } elseif ($companyId !== null) {
            $query->where(function ($sq) use ($companyId) {
                $sq->where('documents.company_id', $companyId)
                   ->orWhereHas('branch', fn($bq) => $bq->where('company_id', $companyId));
            });
        } elseif ($scopedToContext && ($this->isHead() || $this->isPicKlinik())) {
            $contextService = app(\App\Services\CompanyContextService::class);
            $activeCompanyId = $contextService->getActiveCompanyId($this);
            $activeBranchId = $contextService->getActiveBranchId($this);

            if ($activeBranchId) {
                $query->where(function ($sq) use ($activeBranchId, $activeCompanyId) {
                    $sq->where('documents.branch_id', $activeBranchId)
                       ->orWhere(function ($fallback) use ($activeCompanyId) {
                           $fallback->whereNull('documents.branch_id');
                           if ($activeCompanyId) {
                               $fallback->where('documents.company_id', $activeCompanyId);
                           }
                       });
                });
            } elseif ($activeCompanyId) {
                $query->where(function ($sq) use ($activeCompanyId) {
                    $sq->where('documents.company_id', $activeCompanyId)
                       ->orWhereHas('branch', fn($bq) => $bq->where('company_id', $activeCompanyId));
                });
            }
        }
    }

    /**
     * Hitung total persetujuan versi dokumen yang menunggu tindakan pengguna.
     */
    public function pendingVersionApprovalsCount(?int $companyId = null, ?int $branchId = null, bool $scopedToContext = true): int
    {
        $query = DocumentVersion::where('status', 'pending')
            ->whereNull('discarded_at')
            ->where(function ($vq) use ($companyId, $branchId, $scopedToContext) {
                // 1. Directly assigned multi-tier approval step (for ANY user: Staff, Colleague, Doctor, Head, etc.)
                $vq->whereHas('approvalSteps', function ($sq) {
                    $sq->where('assigned_user_id', $this->id)
                       ->where('status', 'pending');
                });

                // 2. Role-based fallback for leadership roles
                if ($this->isAdmin() || $this->isDirector()) {
                    $companyIds = $this->companies()->pluck('companies.id')->all();
                    $vq->orWhereHas('document', function ($dq) use ($companyIds, $companyId, $branchId) {
                        $dq->where(function ($q) {
                            $q->where('approver_role', $this->system_role)
                              ->orWhereNull('approver_role');
                        });
                        if ($companyId !== null) {
                            if ($branchId !== null) {
                                $dq->where('branch_id', $branchId);
                            } else {
                                $dq->where('company_id', $companyId)
                                   ->orWhereHas('branch', fn($bq) => $bq->where('company_id', $companyId));
                            }
                        } elseif (!$this->isAdmin() && !empty($companyIds)) {
                            $dq->whereIn('company_id', $companyIds)
                               ->orWhereHas('branch', fn($bq) => $bq->whereIn('company_id', $companyIds));
                        }
                    });
                } elseif ($this->isPicKlinik()) {
                    $branchIds = $this->branches()->pluck('branches.id')->all();
                    if (!empty($branchIds)) {
                        $vq->orWhereHas('document', function ($dq) use ($branchIds, $companyId, $branchId, $scopedToContext) {
                            if ($branchId !== null) {
                                $dq->where('branch_id', $branchId);
                            } else {
                                $dq->whereIn('branch_id', $branchIds);
                            }
                            $dq->where(function ($sub) use ($branchIds) {
                                $sub->where('approver_id', $this->id)
                                    ->orWhere(function ($fallback) use ($branchIds) {
                                        $fallback->whereNull('approver_id')
                                                 ->whereIn('branch_id', $branchIds)
                                                 ->where(function ($r) {
                                                     $r->whereIn('approver_role', ['pic_klinik', 'head'])
                                                       ->orWhereNull('approver_role');
                                                 });
                                    });
                            });
                            $dq->visibleTo($this);
                            $this->applyContextFilterToDocumentQuery($dq, $companyId, $branchId, $scopedToContext);
                        });
                    }
                } elseif ($this->isHead()) {
                    $unitKerjaIds = $this->allUnitKerjaIds($branchId);
                    if (!empty($unitKerjaIds)) {
                        $vq->orWhereHas('document', function ($dq) use ($unitKerjaIds, $companyId, $branchId, $scopedToContext) {
                            $dq->where(function ($sub) use ($unitKerjaIds) {
                                $sub->where('approver_id', $this->id)
                                    ->orWhere(function ($fallback) use ($unitKerjaIds) {
                                        $fallback->whereNull('approver_id')
                                                 ->whereIn('unit_kerja_id', $unitKerjaIds)
                                                 ->where(function ($r) {
                                                     $r->where('approver_role', 'head')
                                                       ->orWhereNull('approver_role');
                                                 });
                                    });
                            });
                            $dq->visibleTo($this);
                            $this->applyContextFilterToDocumentQuery($dq, $companyId, $branchId, $scopedToContext);
                        });
                    }
                }
            });

        return $query->count();
    }

    /**
     * Hitung total persetujuan perubahan nama/judul dokumen yang menunggu tindakan pengguna.
     */
    public function pendingRenameApprovalsCount(?int $companyId = null, ?int $branchId = null, bool $scopedToContext = true): int
    {
        if (!$this->isHead() && !$this->isPicKlinik() && !$this->isDirector() && !$this->isAdmin()) {
            return 0;
        }

        if ($this->isAdmin() || $this->isDirector()) {
            $companyIds = $this->companies()->pluck('companies.id')->all();

            $roleFilter = function ($q) {
                $q->where('approver_role', $this->system_role)
                  ->orWhereNull('approver_role');
            };

            $renamesQuery = Document::whereNotNull('pending_title')
                ->where('pending_title', '!=', '')
                ->where($roleFilter);

            if ($companyId !== null) {
                if ($branchId !== null) {
                    $renamesQuery->where('branch_id', $branchId);
                } else {
                    $renamesQuery->where(function ($q) use ($companyId) {
                        $q->where('company_id', $companyId)
                          ->orWhereHas('branch', fn($bq) => $bq->where('company_id', $companyId));
                    });
                }
            } elseif (!$this->isAdmin() && !empty($companyIds)) {
                $companyFilter = function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)
                      ->orWhereHas('branch', fn($bq) => $bq->whereIn('company_id', $companyIds));
                };
                $renamesQuery->where($companyFilter);
            }

            return $renamesQuery->count();
        }

        if ($this->isPicKlinik()) {
            $branchIds = $this->branches()->pluck('branches.id')->all();
            if (empty($branchIds)) {
                return 0;
            }

            $roleFilter = function ($q) {
                $q->whereIn('approver_role', ['pic_klinik', 'head'])
                  ->orWhereNull('approver_role');
            };

            $renamesQuery = Document::whereNotNull('pending_title')
                ->where('pending_title', '!=', '')
                ->where(function ($q) use ($branchIds, $branchId) {
                    if ($branchId !== null) {
                        $q->where('branch_id', $branchId);
                    } else {
                        $q->whereIn('branch_id', $branchIds);
                    }
                })
                ->visibleTo($this)
                ->where($roleFilter);

            $this->applyContextFilterToDocumentQuery($renamesQuery, $companyId, $branchId, $scopedToContext);

            return $renamesQuery->count();
        }

        $unitKerjaIds = $this->allUnitKerjaIds($branchId);
        if (empty($unitKerjaIds)) {
            return 0;
        }

        $roleFilter = function ($q) {
            $q->where('approver_role', 'head')
              ->orWhereNull('approver_role');
        };

        $renamesQuery = Document::whereIn('unit_kerja_id', $unitKerjaIds)
            ->visibleTo($this)
            ->whereNotNull('pending_title')
            ->where('pending_title', '!=', '')
            ->where($roleFilter);

        $this->applyContextFilterToDocumentQuery($renamesQuery, $companyId, $branchId, $scopedToContext);

        return $renamesQuery->count();
    }

    /**
     * Hitung total persetujuan rollback dokumen yang menunggu tindakan pengguna.
     */
    public function pendingRollbackApprovalsCount(?int $companyId = null, ?int $branchId = null, bool $scopedToContext = true): int
    {
        if (!$this->isHead() && !$this->isPicKlinik() && !$this->isDirector() && !$this->isAdmin()) {
            return 0;
        }

        if ($this->isAdmin() || $this->isDirector()) {
            $companyIds = $this->companies()->pluck('companies.id')->all();

            $roleFilter = function ($q) {
                $q->where('approver_role', $this->system_role)
                  ->orWhereNull('approver_role');
            };

            $rollbacksQuery = Document::whereNotNull('pending_rollback_version_id')
                ->where($roleFilter);

            if ($companyId !== null) {
                if ($branchId !== null) {
                    $rollbacksQuery->where('branch_id', $branchId);
                } else {
                    $rollbacksQuery->where(function ($q) use ($companyId) {
                        $q->where('company_id', $companyId)
                          ->orWhereHas('branch', fn($bq) => $bq->where('company_id', $companyId));
                    });
                }
            } elseif (!$this->isAdmin() && !empty($companyIds)) {
                $companyFilter = function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)
                      ->orWhereHas('branch', fn($bq) => $bq->whereIn('company_id', $companyIds));
                };
                $rollbacksQuery->where($companyFilter);
            }

            return $rollbacksQuery->count();
        }

        if ($this->isPicKlinik()) {
            $branchIds = $this->branches()->pluck('branches.id')->all();
            if (empty($branchIds)) {
                return 0;
            }

            $roleFilter = function ($q) {
                $q->whereIn('approver_role', ['pic_klinik', 'head'])
                  ->orWhereNull('approver_role');
            };

            $rollbacksQuery = Document::whereNotNull('pending_rollback_version_id')
                ->where(function ($q) use ($branchIds, $branchId) {
                    if ($branchId !== null) {
                        $q->where('branch_id', $branchId);
                    } else {
                        $q->whereIn('branch_id', $branchIds);
                    }
                })
                ->visibleTo($this)
                ->where($roleFilter);

            $this->applyContextFilterToDocumentQuery($rollbacksQuery, $companyId, $branchId, $scopedToContext);

            return $rollbacksQuery->count();
        }

        $unitKerjaIds = $this->allUnitKerjaIds($branchId);
        if (empty($unitKerjaIds)) {
            return 0;
        }

        $roleFilter = function ($q) {
            $q->where('approver_role', 'head')
              ->orWhereNull('approver_role');
        };

        $rollbacksQuery = Document::whereIn('unit_kerja_id', $unitKerjaIds)
            ->visibleTo($this)
            ->whereNotNull('pending_rollback_version_id')
            ->where($roleFilter);

        $this->applyContextFilterToDocumentQuery($rollbacksQuery, $companyId, $branchId, $scopedToContext);

        return $rollbacksQuery->count();
    }

    /**
     * Hitung total approval yang menunggu tindakan pengguna (Head / Direktur / Admin).
     */
    public function pendingApprovalsCount(?int $companyId = null, ?int $branchId = null, bool $scopedToContext = true): int
    {
        return $this->pendingVersionApprovalsCount($companyId, $branchId, $scopedToContext)
            + $this->pendingRenameApprovalsCount($companyId, $branchId, $scopedToContext)
            + $this->pendingRollbackApprovalsCount($companyId, $branchId, $scopedToContext);
    }

    /**
     * Hitung total approval yang menunggu per perusahaan untuk pengguna (Head / Direktur / Admin).
     * Mengembalikan array asosiatif [company_id => total_count].
     */
    public function pendingApprovalsCountByCompany(): array
    {
        if (!$this->isHead() && !$this->isPicKlinik() && !$this->isDirector() && !$this->isAdmin()) {
            return [];
        }

        $accessibleCompanies = app(\App\Services\CompanyContextService::class)->getAvailableCompanies($this);
        if ($accessibleCompanies->isEmpty()) {
            return [];
        }

        $result = [];
        foreach ($accessibleCompanies as $company) {
            $count = $this->pendingApprovalsCount((int) $company->id, null, false);
            if ($count > 0) {
                $result[$company->id] = $count;
            }
        }

        return $result;
    }

    /**
     * Hitung total dokumen baru yang dibagikan kepada pengguna yang belum dibuka / dibaca.
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
