<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['extension_request_id', 'official_id', 'body', 'made_at', 'position'])]
#[WithoutTimestamps]
class RequestRemark extends Model
{
    protected function casts(): array
    {
        return [
            'made_at' => 'datetime',
        ];
    }

    public function official(): BelongsTo
    {
        return $this->belongsTo(Official::class);
    }
}
