<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Bohol municipality, or Tagbilaran City.
 */
#[Fillable(['psgc_code', 'name', 'latitude', 'longitude'])]
class Municipality extends Model
{
    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }
}
