<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id', 'version_number', 'content', 'file_path',
        'file_original_name', 'file_mime', 'author_id',
        'author_name', 'status', 'reviewer_id', 'review_notes', 'reviewed_at',
        'discarded_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'discarded_at' => 'datetime',
        ];
    }

    public function isFileUpload(): bool
    {
        return !is_null($this->file_path);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDiscarded($query)
    {
        return $query->whereNotNull('discarded_at');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
