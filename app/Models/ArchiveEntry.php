<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ArchiveEntry extends Model
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_ELIGIBLE_FOR_DISPOSITION = 'eligible_for_disposition';
    public const STATUS_DISPOSITION_PENDING = 'disposition_pending';
    public const STATUS_DISPOSED = 'disposed';
    public const STATUS_PERMANENT = 'permanent';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'uuid',
        'company_id',
        'branch_id',
        'unit_kerja_id',
        'document_id',
        'document_version_id',
        'request_id',
        'predecessor_entry_id',
        'archive_number',
        'normalized_number',
        'scheme_id',
        'scheme_version',
        'sequence_number',
        'registration_period',
        'registered_at',
        'registered_by',
        'approved_by',
        'classification_id',
        'classification_code_snapshot',
        'classification_name_snapshot',
        'company_code_snapshot',
        'company_name_snapshot',
        'branch_code_snapshot',
        'branch_name_snapshot',
        'unit_code_snapshot',
        'unit_name_snapshot',
        'title',
        'description',
        'original_document_number',
        'version_number_snapshot',
        'owner_id',
        'owner_name_snapshot',
        'sensitivity_level',
        'archive_reason',
        'record_date',
        'effective_date',
        'tags',
        'storage_location',
        'status',
        'retention_policy_id',
        'retention_trigger_date',
        'retention_active_until',
        'retention_due_at',
        'final_disposition_action',
        'is_under_legal_hold',
        'metadata_snapshot',
        'qr_token',
        'disposed_at',
        'disposed_by',
        'disposition_certificate_number',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'sequence_number' => 'integer',
        'scheme_version' => 'integer',
        'version_number_snapshot' => 'integer',
        'registered_at' => 'datetime',
        'record_date' => 'date',
        'effective_date' => 'date',
        'retention_trigger_date' => 'date',
        'retention_active_until' => 'date',
        'retention_due_at' => 'date',
        'is_under_legal_hold' => 'boolean',
        'tags' => 'array',
        'storage_location' => 'array',
        'metadata_snapshot' => 'array',
        'disposed_at' => 'datetime',
        'voided_at' => 'datetime',
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

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ArchiveRequest::class, 'request_id');
    }

    public function predecessorEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'predecessor_entry_id');
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(ArchiveNumberingScheme::class, 'scheme_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(ArchiveClassification::class, 'classification_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function retentionPolicy(): BelongsTo
    {
        return $this->belongsTo(ArchiveRetentionPolicy::class, 'retention_policy_id');
    }

    public function disposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(ArchiveSnapshot::class, 'archive_entry_id');
    }

    public function retentionAssignments(): HasMany
    {
        return $this->hasMany(ArchiveRetentionAssignment::class, 'archive_entry_id')->latest();
    }

    public function legalHolds(): HasMany
    {
        return $this->hasMany(ArchiveLegalHold::class, 'archive_entry_id');
    }

    public function activeLegalHolds(): HasMany
    {
        return $this->hasMany(ArchiveLegalHold::class, 'archive_entry_id')->where('status', 'active');
    }

    public function dispositionRequests(): HasMany
    {
        return $this->hasMany(ArchiveDispositionRequest::class, 'archive_entry_id')->latest();
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(ArchiveRelationship::class, 'from_entry_id');
    }

    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(ArchiveRelationship::class, 'to_entry_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ArchiveEvent::class, 'archive_entry_id')->orderBy('occurred_at');
    }

    public function isDisposed(): bool
    {
        return $this->status === self::STATUS_DISPOSED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function isPermanent(): bool
    {
        return $this->status === self::STATUS_PERMANENT || $this->final_disposition_action === 'permanent';
    }

    public function isUnderLegalHold(): bool
    {
        return (bool) $this->is_under_legal_hold;
    }

    public function isEligibleForDisposition(): bool
    {
        if ($this->isDisposed() || $this->isVoided() || $this->isPermanent() || $this->is_under_legal_hold) {
            return false;
        }

        return $this->retention_due_at !== null && $this->retention_due_at->isPast();
    }
}

