<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveRequest extends Model
{
    public const STATE_DRAFT = 'draft';
    public const STATE_PENDING_APPROVAL = 'pending_approval';
    public const STATE_APPROVED = 'approved';
    public const STATE_REJECTED = 'rejected';
    public const STATE_RETURNED = 'returned_for_correction';
    public const STATE_PREPARING_SNAPSHOT = 'preparing_snapshot';
    public const STATE_READY_TO_REGISTER = 'ready_to_register';
    public const STATE_REGISTERED = 'registered';
    public const STATE_FAILED = 'registration_failed';
    public const STATE_CANCELED = 'canceled';

    protected $fillable = [
        'company_id',
        'branch_id',
        'unit_kerja_id',
        'document_id',
        'requested_version_id',
        'predecessor_entry_id',
        'requested_by',
        'classification_id',
        'retention_policy_id',
        'archive_title',
        'description',
        'archive_reason',
        'record_date',
        'effective_date',
        'sensitivity_level',
        'tags',
        'storage_location',
        'state',
        'requires_approval',
        'approver_id',
        'approver_role',
        'approved_by',
        'approved_at',
        'decision_notes',
        'idempotency_key',
        'error_message',
        'registered_archive_entry_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'storage_location' => 'array',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'record_date' => 'date',
        'effective_date' => 'date',
    ];

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
        return $this->belongsTo(UnitKerja::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function requestedVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'requested_version_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(ArchiveClassification::class, 'classification_id');
    }

    public function retentionPolicy(): BelongsTo
    {
        return $this->belongsTo(ArchiveRetentionPolicy::class, 'retention_policy_id');
    }

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'registered_archive_entry_id');
    }

    public function predecessorEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'predecessor_entry_id');
    }

    public function isDraft(): bool
    {
        return $this->state === self::STATE_DRAFT;
    }

    public function isPendingApproval(): bool
    {
        return $this->state === self::STATE_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->state === self::STATE_APPROVED;
    }

    public function isRegistered(): bool
    {
        return $this->state === self::STATE_REGISTERED;
    }

    public function isEditable(): bool
    {
        return in_array($this->state, [self::STATE_DRAFT, self::STATE_RETURNED], true);
    }
}

