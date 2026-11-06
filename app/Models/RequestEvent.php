<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One status change in a request's history, with the remark left on it.
 */
#[Fillable(['extension_request_id', 'official_id', 'from_status', 'to_status', 'remark', 'happened_at', 'position'])]
#[WithoutTimestamps]
class RequestEvent extends Model
{
    protected function casts(): array
    {
        return [
            'happened_at' => 'datetime',
        ];
    }

    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class);
    }
}
