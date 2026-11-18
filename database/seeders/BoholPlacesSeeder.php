<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Municipality;
use Illuminate\Database\Seeder;

/**
 * Bohol's 48 cities/municipalities and 1,109 barangays.
 *
 * Source: Philippine Standard Geographic Code (PSGC), Philippine Statistics Authority, via the
 * PSGC API mirror https://psgc.gitlab.io/api (province 071200000), fetched 2026-11-18.
 * "City of Tagbilaran" is stored as "Tagbilaran City".
 * Municipality centres: OpenStreetMap Nominatim, fetched 2026-11-18; ten towns keep the
 * coordinates used by the defense prototype. Barangay coordinates are left blank.
 * Safe to run again.
 */
class BoholPlacesSeeder extends Seeder
{
    public function run(): void
    {
        $ids = [];

        foreach ($this->rows('bohol_municipalities.csv') as $row) {
            $municipality = Municipality::updateOrCreate(
                ['psgc_code' => $row['psgc_code']],
                ['name' => $row['name'], 'latitude' => $row['latitude'], 'longitude' => $row['longitude']],
            );
            $ids[$row['psgc_code']] = $municipality->id;
        }

        foreach ($this->rows('bohol_barangays.csv') as $row) {
            Barangay::updateOrCreate(
                ['psgc_code' => $row['psgc_code']],
                [
                    'municipality_id' => $ids[$row['municipality_psgc_code']],
                    'name' => $row['name'],
                    'latitude' => $row['latitude'] === '' ? null : $row['latitude'],
                    'longitude' => $row['longitude'] === '' ? null : $row['longitude'],
                ],
            );
        }
    }

    /**
     * @return \Generator<int, array<string, string>>
     */
    private function rows(string $file): \Generator
    {
        $handle = fopen(database_path('data/'.$file), 'r');
        $header = fgetcsv($handle, escape: '');

        while (($line = fgetcsv($handle, escape: '')) !== false) {
            yield array_combine($header, $line);
        }

        fclose($handle);
    }
}
