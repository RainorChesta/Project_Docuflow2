<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccessLink extends Model
{
    protected $fillable = ['document_id', 'token', 'role', 'expires_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Active links only: not expired (non-expiring or still valid).
     * Revoked links are hard-deleted, so absence of the row is "revoked".
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Is there an active link for the same resource+role?
     */
    public function scopeActiveForRole(Builder $query, int $documentId, string $role): Builder
    {
        return $query->where('document_id', $documentId)->where('role', $role)->active();
    }
}
