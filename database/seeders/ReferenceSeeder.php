<?php

namespace Database\Seeders;

use App\Models\BeneficiarySector;
use App\Models\Campus;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * The fixed lists the app starts from: campuses, categories and beneficiary sectors.
 * Safe to run again; existing rows are kept.
 */
class ReferenceSeeder extends Seeder
{
    public const CAMPUSES = [
        'Main' => 'Tagbilaran',
        'Balilihan' => 'Balilihan',
        'Bilar' => 'Bilar',
        'Calape' => 'Calape',
        'Candijay' => 'Candijay',
        'Clarin' => 'Clarin',
    ];

    public const CATEGORIES = [
        'Agriculture & Fisheries', 'Livelihood', 'Health', 'Education', 'Environment', 'Technology & Digital', 'Others',
    ];

    public const SECTORS = [
        'Fisherfolk', 'Farmers', 'Women', 'Youth', 'Senior citizens', 'PWD', '4Ps households', 'Teachers', 'Health workers', 'MSME owners',
    ];

    public function run(): void
    {
        foreach (self::CAMPUSES as $name => $municipality) {
            Campus::firstOrCreate(['name' => $name], ['municipality' => $municipality]);
        }

        foreach (self::CATEGORIES as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        foreach (self::SECTORS as $i => $name) {
            BeneficiarySector::firstOrCreate(['name' => $name], ['position' => $i]);
        }
    }
}
