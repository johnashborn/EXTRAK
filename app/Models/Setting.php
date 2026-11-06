<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * A single named value, such as the current year or the next request number.
 */
#[Fillable(['key', 'value'])]
#[Table(key: 'key', keyType: 'string', incrementing: false)]
class Setting extends Model
{
    public static function valueOf(string $key, mixed $default = null): mixed
    {
        return static::find($key)?->value ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
    }
}
