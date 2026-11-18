<?php

namespace App\Models;

use App\Enums\DocumentKind;
use App\Enums\ServiceState;
use App\Models\Scopes\HideOtherCampusDrafts;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One completed extension service in the repository.
 */
#[ScopedBy([HideOtherCampusDrafts::class])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'state' => ServiceState::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'beneficiaries' => 'integer',
            'amount_spent' => 'decimal:2',
            'reader_suggestions' => 'array',
            'published_at' => 'datetime',
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

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function barangays(): BelongsToMany
    {
        return $this->belongsToMany(Barangay::class, 'service_barangay');
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(BeneficiarySector::class, 'service_sector');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function reportDocument(): HasOne
    {
        return $this->hasOne(Document::class)->where('kind', DocumentKind::AccomplishmentReport->value);
    }

    public function photos(): HasMany
    {
        return $this->documents()->where('kind', DocumentKind::Photo->value);
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
