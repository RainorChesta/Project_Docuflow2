<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveRelationship extends Model
{
    public const TYPE_SUPERSEDES = 'supersedes';
    public const TYPE_REACTIVATED_FROM = 'reactivated_from';
    public const TYPE_REACTIVATED_TO_DOCUMENT = 'reactivated_to_document';
    public const TYPE_RELATED = 'related';

    protected $fillable = [
        'from_entry_id',
        'to_entry_id',
        'related_document_id',
        'relation_type',
        'created_by',
        'notes',
    ];

    public function fromEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'from_entry_id');
    }

    public function toEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'to_entry_id');
    }

    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'related_document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

