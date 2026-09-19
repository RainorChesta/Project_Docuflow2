<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    public const CATEGORY_NASKAH_DINAS = 'naskah_dinas';
    public const CATEGORY_AKREDITASI = 'akreditasi';

    protected $fillable = ['code', 'name', 'category'];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function isNaskahDinas(): bool
    {
        return ($this->category ?? self::CATEGORY_NASKAH_DINAS) === self::CATEGORY_NASKAH_DINAS;
    }

    public function isAkreditasi(): bool
    {
        return $this->category === self::CATEGORY_AKREDITASI;
    }
}
