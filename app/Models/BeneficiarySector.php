<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A group a service is for, such as Farmers or Women. Custom ones are added by the extension office.
 */
#[Fillable(['name', 'is_custom', 'position'])]
class BeneficiarySector extends Model
{
    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
        ];
    }
}
