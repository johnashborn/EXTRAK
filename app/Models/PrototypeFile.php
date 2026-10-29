<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A photo or supporting document uploaded from the prototype dashboards.
 */
#[Fillable(['original_name', 'mime_type', 'size', 'path', 'uploaded_by'])]
class PrototypeFile extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /** What the dashboards need to show and link to the file. */
    public function toClientArray(): array
    {
        return [
            'id' => $this->id,
            // relative, so saved links keep working whichever address the app is opened from
            'url' => route('prototype.files.show', $this, false),
            'name' => $this->original_name,
            'type' => $this->mime_type,
            'size' => $this->size,
        ];
    }
}
