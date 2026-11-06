<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * What actually happened when a service ended: dates, money spent, people reached and photos.
 */
#[Fillable(['extension_request_id', 'actual_start', 'actual_end', 'actual_budget', 'actual_beneficiaries', 'narrative', 'encoded_by'])]
class AccomplishmentReport extends Model
{
    protected function casts(): array
    {
        return [
            'actual_start' => 'date',
            'actual_end' => 'date',
            'actual_budget' => 'decimal:2',
            'actual_beneficiaries' => 'integer',
        ];
    }

    public function extensionRequest(): BelongsTo
    {
        return $this->belongsTo(ExtensionRequest::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('position');
    }
}
