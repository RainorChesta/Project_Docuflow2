<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveNumberCounter extends Model
{
    protected $fillable = [
        'scheme_id',
        'company_id',
        'scope_key',
        'period_key',
        'last_value',
    ];

    protected $casts = [
        'last_value' => 'integer',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(ArchiveNumberingScheme::class, 'scheme_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

