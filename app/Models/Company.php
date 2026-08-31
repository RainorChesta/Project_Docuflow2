<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Company $company) {
            Document::withTrashed()->where('company_id', $company->id)->update([
                'company_id' => null,
                'branch_id' => null,
            ]);
        });
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function pusatBranch()
    {
        return $this->hasOne(Branch::class)->where('is_pusat', true);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
