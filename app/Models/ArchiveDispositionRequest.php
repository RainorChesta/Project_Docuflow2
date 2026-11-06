<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveDispositionRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXECUTED = 'executed';
    public const STATUS_FAILED = 'failed';

    public const ACTION_DESTROY = 'destroy';
    public const ACTION_PERMANENT = 'permanent';
    public const ACTION_TRANSFER = 'transfer';
    public const ACTION_EXTEND = 'extend';

    protected $fillable = [
        'company_id',
        'archive_entry_id',
        'requested_by',
        'requested_at',
        'action_type',
        'justification',
        'extension_days',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'executed_by',
        'executed_at',
        'certificate_number',
        'certificate_snapshot',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'executed_at' => 'datetime',
        'extension_days' => 'integer',
        'certificate_snapshot' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'archive_entry_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isExecuted(): bool
    {
        return $this->status === self::STATUS_EXECUTED;
    }
}

