<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitKerja extends Model
{
    use HasFactory;

    protected $table = 'unit_kerjas';

    protected $fillable = [
        'kode_unit_kerja',
        'nama_unit_kerja',
        'pic_user_id',
    ];

    public function getNameAttribute(): string
    {
        return $this->nama_unit_kerja ?? '';
    }

    public function getCodeAttribute(): string
    {
        return $this->kode_unit_kerja ?? '';
    }

    public function picUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function setKodeUnitKerjaAttribute($value): void
    {
        $this->attributes['kode_unit_kerja'] = strtoupper(trim((string) $value));
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'unit_kerja_id');
    }

    public function documentShares(): HasMany
    {
        return $this->hasMany(DocumentUnitKerjaShare::class, 'unit_kerja_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_kerja_user')->withPivot('branch_id')->withTimestamps();
    }

    public function primaryUsers(): HasMany
    {
        return $this->hasMany(User::class, 'unit_kerja_id');
    }
}
