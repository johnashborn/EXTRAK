<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One of the six BISU campuses.
 */
#[Fillable(['name', 'municipality'])]
class Campus extends Model
{
}
