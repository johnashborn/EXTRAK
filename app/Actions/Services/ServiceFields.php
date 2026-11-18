<?php

namespace App\Actions\Services;

/**
 * The form fields a record is described with, and where each one is kept.
 */
final class ServiceFields
{
    /** Columns on the services table. */
    public const COLUMNS = [
        'title', 'description', 'category_id', 'municipality_id', 'start_date', 'end_date',
        'beneficiaries', 'partners', 'amount_spent', 'narrative',
    ];

    /** Lists kept in pivot tables: field name => relation on Service. */
    public const LISTS = ['barangay_ids' => 'barangays', 'sector_ids' => 'sectors'];

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [...self::COLUMNS, ...array_keys(self::LISTS)];
    }

    /**
     * @param  array<int, int|string>|null  $ids
     * @return list<int>
     */
    public static function ids(?array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids ?? [])));
        sort($ids);

        return $ids;
    }
}
