<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A campus's share of the university extension budget for a year.
 */
#[Fillable(['campus_id', 'year', 'allocation'])]
class CampusBudget extends Model
{
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'allocation' => 'decimal:2',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
