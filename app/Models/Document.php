<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'document_number', 'title', 'division_id', 'document_type_id', 'owner_id',
        'is_public', 'current_version_id',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function accessLinks(): HasMany
    {
        return $this->hasMany(DocumentAccessLink::class);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('versions', fn($q) => $q->where('status', 'active'));
    }

    public function scopePending($query)
    {
        return $query->whereHas('versions', fn($q) => $q->where('status', 'pending'));
    }
}
