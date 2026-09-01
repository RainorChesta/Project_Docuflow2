<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    protected $fillable = ['code', 'name'];

    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim((string) $value));
    }

    public static function formatCapitalName(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        // If the string is entirely uppercase (e.g., "MARKETING"), convert to lowercase first
        if ($value === mb_strtoupper($value) && mb_strtolower($value) !== mb_strtoupper($value)) {
            $value = mb_strtolower($value);
        }

        return ucwords($value);
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = static::formatCapitalName($value);
    }

    public function getNameAttribute($value): string
    {
        return static::formatCapitalName($value);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function documentShares(): HasMany
    {
        return $this->hasMany(DocumentDivisionShare::class);
    }
}
