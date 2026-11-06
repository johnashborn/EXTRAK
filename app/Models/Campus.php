<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One of the six BISU campuses.
 */
#[Fillable(['name', 'municipality'])]
class Campus extends Model
{
    public function budgets(): HasMany
    {
        return $this->hasMany(CampusBudget::class);
    }

    public function extensionRequests(): HasMany
    {
        return $this->hasMany(ExtensionRequest::class);
    }
}
