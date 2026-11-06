<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveLegalHold extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'company_id',
        'archive_entry_id',
        'case_reference',
        'title',
        'reason',
        'status',
        'applied_by',
        'applied_at',
        'released_by',
        'released_at',
        'release_reason',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'archive_entry_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

