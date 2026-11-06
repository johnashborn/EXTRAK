<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Similar work found by the overlap check, and how the Campus Director handled it.
 */
#[Fillable(['extension_request_id', 'score', 'match_code', 'match', 'terms', 'resolution', 'resolved_by'])]
#[WithoutTimestamps]
class OverlapFlag extends Model
{
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'match' => 'array',
            'terms' => 'array',
        ];
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'resolved_by');
    }
}
