<?php

namespace App\Models;

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
}
