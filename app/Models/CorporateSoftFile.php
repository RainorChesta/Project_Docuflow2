<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CorporateSoftFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_original_name',
        'file_mime',
        'file_size',
        'status',
        'allowed_roles',
        'is_all_companies',
        'is_all_branches',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'allowed_roles' => 'array',
            'is_all_companies' => 'boolean',
            'is_all_branches' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_corporate_soft_file')->withTimestamps();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_corporate_soft_file')->withTimestamps();
    }

    /* ------------------------------------------------------------------ */
    /*  Scopes                                                             */
    /* ------------------------------------------------------------------ */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope query to corporate soft files accessible by a specific user & company/branch context.
     */
    public function scopeAccessibleBy(Builder $query, User $user, ?int $companyId = null, ?int $branchId = null): Builder
    {
        if ($user->isAdmin()) {
            return $query->where('status', 'active');
        }

        // Active files only
        $query->where('status', 'active');

        // Role check: either null (all roles) or includes user's system_role (handling staff/user alias)
        $query->where(function ($rq) use ($user) {
            $userRoles = [$user->system_role];
            if ($user->system_role === 'user') {
                $userRoles[] = 'staff';
            } elseif ($user->system_role === 'staff') {
                $userRoles[] = 'user';
            }

            $rq->whereNull('allowed_roles');
            foreach ($userRoles as $role) {
                $rq->orWhereJsonContains('allowed_roles', $role);
            }
        });

        // Company filter
        $query->where(function ($cq) use ($companyId) {
            $cq->where('is_all_companies', true);
            if ($companyId) {
                $cq->orWhereHas('companies', fn($q) => $q->where('companies.id', $companyId));
            }
        });

        // Branch filter
        $query->where(function ($bq) use ($branchId) {
            $bq->where('is_all_branches', true);
            if ($branchId) {
                $bq->orWhereHas('branches', fn($q) => $q->where('branches.id', $branchId));
            }
        });

        return $query;
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isRoleAllowed(?string $role): bool
    {
        if (empty($this->allowed_roles)) {
            return true;
        }

        $rolesToCheck = [$role];
        if ($role === 'user') {
            $rolesToCheck[] = 'staff';
        } elseif ($role === 'staff') {
            $rolesToCheck[] = 'user';
        }

        return count(array_intersect($rolesToCheck, $this->allowed_roles)) > 0;
    }

    public function isImage(): bool
    {
        $ext = strtolower(pathinfo($this->file_original_name ?: $this->file_path, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) || str_starts_with($this->file_mime ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        $ext = strtolower(pathinfo($this->file_original_name ?: $this->file_path, PATHINFO_EXTENSION));
        return $ext === 'pdf' || str_contains(strtolower($this->file_mime ?? ''), 'pdf');
    }

    public function getFileTypeAttribute(): string
    {
        $ext = strtolower(pathinfo($this->file_original_name ?: $this->file_path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            return strtoupper($ext);
        }
        if ($ext === 'pdf') {
            return 'PDF';
        }
        return 'DOCX';
    }
}
