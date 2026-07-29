<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $fillable = [
        'document_id', 'version_number', 'content', 'author_id',
        'author_name', 'status', 'reviewer_id', 'review_notes', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
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
