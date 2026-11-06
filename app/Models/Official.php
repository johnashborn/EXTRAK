<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person in the approval workflow: an Extension or Campus Director, an assistant, or the President.
 * The dashboards refer to them by key (ed, cd, pres, Main-cd and so on).
 */
#[Fillable(['key', 'name', 'title', 'role', 'campus_id', 'assistant_of'])]
class Official extends Model
{
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /** The director this assistant works for. */
    public function principal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'assistant_of');
    }
}
