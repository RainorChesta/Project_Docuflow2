<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveNumberingScheme extends Model
{
    protected $fillable = [
        'company_id',
        'version',
        'name',
        'prefix',
        'template',
        'separator',
        'sequence_scope',
        'reset_period',
        'padding',
        'start_value',
        'timezone',
        'is_active',
        'active_from',
        'active_until',
        'created_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'padding' => 'integer',
        'start_value' => 'integer',
        'is_active' => 'boolean',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function counters(): HasMany
    {
        return $this->hasMany(ArchiveNumberCounter::class, 'scheme_id');
    }

    public function archiveEntries(): HasMany
    {
        return $this->hasMany(ArchiveEntry::class, 'scheme_id');
    }
}

