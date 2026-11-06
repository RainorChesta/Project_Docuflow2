<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveRetentionAssignment extends Model
{
    protected $fillable = [
        'archive_entry_id',
        'retention_policy_id',
        'policy_version',
        'trigger_event',
        'trigger_date',
        'active_until',
        'eligible_from',
        'final_action',
        'calculation_snapshot',
        'status',
        'assigned_by',
        'reason',
    ];

    protected $casts = [
        'policy_version' => 'integer',
        'trigger_date' => 'date',
        'active_until' => 'date',
        'eligible_from' => 'date',
        'calculation_snapshot' => 'array',
    ];

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'archive_entry_id');
    }

    public function retentionPolicy(): BelongsTo
    {
        return $this->belongsTo(ArchiveRetentionPolicy::class, 'retention_policy_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

