<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveEvent extends Model
{
    public $timestamps = false;

    public const EVENT_REQUEST_CREATED = 'request.created';
    public const EVENT_REQUEST_SUBMITTED = 'request.submitted';
    public const EVENT_REQUEST_APPROVED = 'request.approved';
    public const EVENT_REQUEST_REJECTED = 'request.rejected';
    public const EVENT_REQUEST_RETURNED = 'request.returned';
    public const EVENT_REQUEST_CANCELED = 'request.canceled';
    public const EVENT_REQUEST_DELETED = 'request.deleted';
    public const EVENT_SNAPSHOT_CAPTURED = 'snapshot.captured';
    public const EVENT_ARCHIVE_REGISTERED = 'archive.registered';
    public const EVENT_INTEGRITY_VERIFIED = 'integrity.verified';
    public const EVENT_METADATA_CORRECTED = 'metadata.corrected';
    public const EVENT_LEGAL_HOLD_APPLIED = 'hold.applied';
    public const EVENT_LEGAL_HOLD_RELEASED = 'hold.released';
    public const EVENT_RETENTION_EXTENDED = 'retention.extended';
    public const EVENT_DISPOSITION_REQUESTED = 'disposition.requested';
    public const EVENT_DISPOSITION_APPROVED = 'disposition.approved';
    public const EVENT_DISPOSITION_EXECUTED = 'disposition.executed';
    public const EVENT_WORKING_COPY_REACTIVATED = 'working_copy.reactivated';
    public const EVENT_ARCHIVE_VOIDED = 'archive.voided';

    protected $fillable = [
        'company_id',
        'archive_entry_id',
        'archive_request_id',
        'event_type',
        'actor_id',
        'actor_name',
        'actor_role',
        'occurred_at',
        'reason',
        'before_state',
        'after_state',
        'metadata',
        'ip_address',
        'user_agent',
        'previous_hash',
        'event_hash',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'before_state' => 'array',
        'after_state' => 'array',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'archive_entry_id');
    }

    public function archiveRequest(): BelongsTo
    {
        return $this->belongsTo(ArchiveRequest::class, 'archive_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
