<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A supporting document of a request, or a photo of an accomplishment report.
 * Uploaded files point to their stored file; sample data keeps a generated picture in url.
 */
#[Fillable([
    'attachable_type', 'attachable_id', 'ref', 'kind', 'file_id', 'name', 'mime_type', 'size', 'url',
    'is_sample', 'uploaded_by', 'uploaded_at', 'position',
])]
#[WithoutTimestamps]
class Attachment extends Model
{
    public const PHOTO = 'photo';

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_sample' => 'boolean',
            'uploaded_at' => 'datetime',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(PrototypeFile::class, 'file_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'uploaded_by');
    }
}
