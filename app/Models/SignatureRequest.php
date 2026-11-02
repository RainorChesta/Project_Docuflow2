<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'target_user_id',
        'requested_signature_id',
        'document_id',
        'status',
        'is_used',
        'page_number',
        'pos_x',
        'pos_y',
        'width',
        'height',
        'preset_position',
        'placements',
        'rejected_reason',
        'requested_at',
        'notified_at',
        'responded_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_used' => 'boolean',
            'page_number' => 'integer',
            'pos_x' => 'float',
            'pos_y' => 'float',
            'width' => 'float',
            'height' => 'float',
            'placements' => 'array',
            'requested_at' => 'datetime',
            'notified_at' => 'datetime',
            'responded_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function requestedSignature(): BelongsTo
    {
        return $this->belongsTo(Signature::class, 'requested_signature_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function approvalStep(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(DocumentApprovalStep::class, 'signature_request_id');
    }

    /**
     * Role weight for hierarchical step ordering (Staff = 10, Unit PIC = 20, Kadiv/Kacab = 30, Director = 40, Admin = 50).
     */
    public function getRoleWeightAttribute(): int
    {
        $user = $this->targetUser;
        if (!$user) {
            return 10;
        }

        if ($user->isDirector()) {
            return 40;
        }
        if ($user->isAdmin()) {
            return 50;
        }
        if ($user->isPicKlinik() || $user->isHead()) {
            return 30;
        }

        // Check if user is Unit Kerja PIC
        if (UnitKerja::where('pic_user_id', $user->id)->exists()) {
            return 20;
        }

        return 10; // Default Staff / Peer
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

    public function isUsed(): bool
    {
        return (bool) $this->is_used;
    }

    public function isAvailable(): bool
    {
        return $this->isApproved() && !$this->is_used;
    }

    public function isStamp(): bool
    {
        return $this->requestedSignature && $this->requestedSignature->type === 'company_stamp';
    }

    public function isSignature(): bool
    {
        return !$this->isStamp();
    }

    public function isNotified(): bool
    {
        return $this->notified_at !== null;
    }

    /**
     * Dispatch notification to target user when document editing is finished and saved.
     * Uses unified DocumentApprovalRequested when a pending version exists, avoiding duplicate notifications.
     */
    public function sendNotification(): void
    {
        $this->loadMissing(['document.versions', 'requester', 'requestedSignature.company', 'targetUser']);

        $targetUser = $this->targetUser;
        $document   = $this->document;
        $requester  = $this->requester;

        if ($targetUser && $document && $requester && $targetUser->id !== $requester->id) {
            $pendingVersion = $document->versions->firstWhere('status', 'pending')
                ?? $document->versions()->where('status', 'pending')->latest('id')->first();

            if ($pendingVersion) {
                $targetUser->notify(new \App\Notifications\DocumentApprovalRequested($document, $pendingVersion, $requester->name));
            } else {
                $targetUser->notify(new \App\Notifications\SignatureRequested($document, $requester->name, $this));
            }

            $this->update([
                'notified_at' => now(),
                'requested_at' => now(),
            ]);
        }
    }

    public function getTypeLabelAttribute(): string
    {
        if ($this->isStamp()) {
            $companyName = $this->requestedSignature->company?->name;
            return $companyName ? "Stempel ({$companyName})" : 'Stempel Perusahaan';
        }
        return 'Tanda Tangan Original';
    }

    /**
     * Retrieve all page placements for this signature request.
     * Fallbacks to single page placement for backwards compatibility.
     */
    public function getPlacements(): array
    {
        if (!empty($this->placements) && is_array($this->placements)) {
            return $this->placements;
        }

        if ($this->page_number !== null) {
            return [[
                'page' => (int) $this->page_number,
                'pos_x' => $this->pos_x !== null ? (float) $this->pos_x : null,
                'pos_y' => $this->pos_y !== null ? (float) $this->pos_y : null,
                'width' => (float) ($this->width ?? 24.0),
                'height' => (float) ($this->height ?? 24.0),
                'preset_position' => $this->preset_position ?? 'bottom-right',
            ]];
        }

        return [];
    }
}

