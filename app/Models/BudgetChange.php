<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A change the President made to the university total (no campus) or to a campus budget.
 */
#[Fillable(['year', 'campus_id', 'kind', 'from_amount', 'to_amount', 'reason', 'changed_by', 'changed_at', 'position'])]
#[WithoutTimestamps]
class BudgetChange extends Model
{
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'from_amount' => 'decimal:2',
            'to_amount' => 'decimal:2',
            'changed_at' => 'datetime',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'changed_by');
    }
}
