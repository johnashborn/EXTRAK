<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Prototype only: the dashboards' shared data as one JSON document.
 */
#[Fillable(['key', 'data', 'version', 'updated_by'])]
class PrototypeState extends Model
{
    public const DEFAULT_KEY = 'default';

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'version' => 'integer',
        ];
    }

    public static function current(): ?self
    {
        return static::where('key', self::DEFAULT_KEY)->first();
    }
}
