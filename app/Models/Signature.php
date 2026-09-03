<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'file_path',
        'type',
        'company_id',
        'created_via',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getAbsolutePathAttribute(): string
    {
        return Storage::disk('public')->path($this->file_path);
    }

    public function getBase64Attribute(): ?string
    {
        $path = $this->absolute_path;
        if (!file_exists($path)) {
            return null;
        }

        $data = file_get_contents($path);
        $type = pathinfo($path, PATHINFO_EXTENSION);
        return 'data:image/' . ($type ?: 'png') . ';base64,' . base64_encode($data);
    }
}
