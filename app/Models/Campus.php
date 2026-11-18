<?php

namespace App\Models;

use Database\Factories\CampusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One of the six BISU campuses.
 */
#[Fillable(['name', 'municipality'])]
class Campus extends Model
{
    /** @use HasFactory<CampusFactory> */
    use HasFactory;
}
