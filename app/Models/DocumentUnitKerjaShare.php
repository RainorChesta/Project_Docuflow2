<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentUnitKerjaShare extends Model
{
    protected $table = 'document_unit_kerja_shares';

    protected $fillable = ['document_id', 'unit_kerja_id', 'role', 'invited_by'];

    protected function casts(): array
    {
        return [
            'invited_by' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
