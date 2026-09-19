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
        'pic_klinik_id',
    ];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = mb_strtoupper(trim((string) $value));
    }

    public function getNameAttribute($value): string
    {
        return mb_strtoupper((string) $value);
    }

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

    public function picKlinik(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_klinik_id');
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
     * Get all active PIC users belonging to unit kerjas in this branch.
     */
    public function availablePics()
    {
        return User::whereIn('id', function ($query) {
            $query->select('pic_user_id')
                ->from('unit_kerjas')
                ->whereNotNull('pic_user_id');
        })->where('is_active', true)->get();
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
