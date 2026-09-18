<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentApprovalStep extends Model
{
    use HasFactory;

    protected $table = 'document_approval_steps';

    protected $fillable = [
        'document_id',
        'version_id',
        'signature_request_id',
        'step_order',
        'step_type',
        'step_name',
        'assigned_user_id',
        'assigned_role',
        'status',
        'action_by_id',
        'action_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'action_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'version_id');
    }

    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(SignatureRequest::class, 'signature_request_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function actionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', 'waiting');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('assigned_user_id', $user->id);
            if ($user->isAdmin()) {
                $q->orWhere('assigned_role', 'admin');
            }
            if ($user->isDirector()) {
                $q->orWhere('assigned_role', 'direktur');
            }
        });
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isBypassed(): bool
    {
        return $this->status === 'bypassed';
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }
}
