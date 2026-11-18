<?php

namespace App\Services\ReportReading;

/**
 * What a reader suggested, keyed by form field name (for example ['title' => '...']).
 */
final class ReportFields
{
    /**
     * @param  array<string, mixed>  $fields
     */
    public function __construct(public readonly array $fields = [])
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->fields;
    }
}
