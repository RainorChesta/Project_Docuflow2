<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveClassification extends Model
{
    protected $fillable = [
        'company_id',
        'parent_id',
        'default_retention_policy_id',
        'code',
        'name',
        'description',
        'confidentiality_level',
        'requires_approval',
        'is_active',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function defaultRetentionPolicy(): BelongsTo
    {
        return $this->belongsTo(ArchiveRetentionPolicy::class, 'default_retention_policy_id');
    }

    public function archiveEntries(): HasMany
    {
        return $this->hasMany(ArchiveEntry::class, 'classification_id');
    }

    public function getFullLabelAttribute(): string
    {
        return "{$this->code} — {$this->name}";
    }
}

