<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * An extension service request, from draft through approval to its accomplishment report.
 */
#[Fillable([
    'ref', 'code', 'campus_id', 'requested_by', 'encoded_by', 'title', 'description', 'category_id', 'municipality',
    'start_date', 'end_date', 'proposed_budget', 'expected_beneficiaries', 'partners', 'status', 'position',
])]
class ExtensionRequest extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'proposed_budget' => 'decimal:2',
            'expected_beneficiaries' => 'integer',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'requested_by');
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(Official::class, 'encoded_by');
    }

    public function barangays(): BelongsToMany
    {
        return $this->belongsToMany(Barangay::class, 'request_barangay')->withPivot('position')->orderByPivot('position');
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(BeneficiarySector::class, 'request_sector')->withPivot('position')->orderByPivot('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RequestEvent::class)->orderBy('position');
    }

    public function remarks(): HasMany
    {
        return $this->hasMany(RequestRemark::class)->orderBy('position');
    }

    public function overlap(): HasOne
    {
        return $this->hasOne(OverlapFlag::class);
    }

    public function report(): HasOne
    {
        return $this->hasOne(AccomplishmentReport::class);
    }

    /** Supporting documents: barangay letter, proposal, budget breakdown, MOA. */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('position');
    }
}
