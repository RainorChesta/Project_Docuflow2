<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitKerja extends Model
{
    use HasFactory;

    protected $table = 'unit_kerjas';

    protected $fillable = [
        'kode_unit_kerja',
        'nama_unit_kerja',
    ];

    public function setKodeUnitKerjaAttribute($value): void
    {
        $this->attributes['kode_unit_kerja'] = strtoupper(trim((string) $value));
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'cabang_id')->withDefault();
    }

    /**
     * Alias for cabang() to support English convention
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'cabang_id')->withDefault();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'unit_kerja_id');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function primaryUsers(): HasMany
    {
        return $this->hasMany(User::class, 'unit_kerja_id');
    }
}
