<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveVoidRecord extends Model
{
    protected $fillable = [
        'company_id',
        'archive_entry_id',
        'archive_number',
        'normalized_number',
        'void_reason',
        'voided_by',
        'voided_at',
        'metadata_snapshot',
    ];

    protected $casts = [
        'voided_at' => 'datetime',
        'metadata_snapshot' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'archive_entry_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}

