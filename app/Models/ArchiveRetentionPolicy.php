<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveRetentionPolicy extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'version',
        'description',
        'trigger_event',
        'active_retention_years',
        'inactive_retention_years',
        'total_retention_days',
        'final_disposition',
        'requires_approval',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'active_retention_years' => 'integer',
        'inactive_retention_years' => 'integer',
        'total_retention_days' => 'integer',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classifications(): HasMany
    {
        return $this->hasMany(ArchiveClassification::class, 'default_retention_policy_id');
    }

    public function archiveEntries(): HasMany
    {
        return $this->hasMany(ArchiveEntry::class, 'retention_policy_id');
    }

    public function getSummaryLabelAttribute(): string
    {
        if ($this->final_disposition === 'permanent') {
            return __('Permanent Archive (:active yr active)', ['active' => $this->active_retention_years]);
        }

        $totalYears = $this->active_retention_years + $this->inactive_retention_years;
        return sprintf(
            '%d %s (%d %s + %d %s) → %s',
            $totalYears,
            __('Years'),
            $this->active_retention_years,
            __('Active'),
            $this->inactive_retention_years,
            __('Inactive'),
            ucfirst($this->final_disposition)
        );
    }
}

