<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'is_pusat',
        'code',
    ];

    protected function casts(): array
    {
        return [
            'is_pusat' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Effective branch code for document numbering.
     * If branch is "Pusat", it inherits the company's code.
     */
    public function getEffectiveCodeAttribute(): string
    {
        if ($this->is_pusat || empty($this->code)) {
            return $this->company?->code ?? 'PST';
        }

        return $this->code;
    }
}
