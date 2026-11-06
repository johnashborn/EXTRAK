<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The university extension budget for a year, set by the President.
 */
#[Fillable(['year', 'amount'])]
class UniversityBudget extends Model
{
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'amount' => 'decimal:2',
        ];
    }
}
