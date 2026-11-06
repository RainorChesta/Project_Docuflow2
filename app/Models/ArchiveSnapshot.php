<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveSnapshot extends Model
{
    public const INTEGRITY_VALID = 'valid';
    public const INTEGRITY_MISMATCH = 'mismatch';
    public const INTEGRITY_MISSING = 'missing';
    public const INTEGRITY_DISPOSED = 'disposed';

    protected $fillable = [
        'archive_entry_id',
        'archive_request_id',
        'storage_disk',
        'object_key',
        'original_filename',
        'mime_type',
        'file_size',
        'content_hash_sha256',
        'manifest_hash_sha256',
        'manifest_payload',
        'is_html_snapshot',
        'html_content',
        'copied_at',
        'validated_at',
        'last_verified_at',
        'integrity_status',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'manifest_payload' => 'array',
        'is_html_snapshot' => 'boolean',
        'copied_at' => 'datetime',
        'validated_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public function archiveEntry(): BelongsTo
    {
        return $this->belongsTo(ArchiveEntry::class, 'archive_entry_id');
    }

    public function archiveRequest(): BelongsTo
    {
        return $this->belongsTo(ArchiveRequest::class, 'archive_request_id');
    }

    public function isValid(): bool
    {
        return $this->integrity_status === self::INTEGRITY_VALID;
    }
}

