<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A notification shown on one of the dashboards (recipient ed, cd or pres).
 */
#[Fillable(['ref', 'recipient', 'kind', 'text', 'extension_request_id', 'request_ref', 'is_read', 'sent_at', 'position'])]
#[WithoutTimestamps]
class DashboardNotification extends Model
{
    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function extensionRequest(): BelongsTo
    {
        return $this->belongsTo(ExtensionRequest::class);
    }
}
