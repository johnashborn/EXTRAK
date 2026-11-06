<?php

namespace Database\Seeders;

use App\Models\BeneficiarySector;
use App\Models\Campus;
use App\Models\Category;
use App\Models\Official;
use Illuminate\Database\Seeder;

/**
 * The fixed lists the dashboards start from: campuses, the people in the workflow,
 * categories and beneficiary sectors. Safe to run again; existing rows are kept.
 */
class ExtensionReferenceSeeder extends Seeder
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
        $campus = Campus::pluck('id', 'name');

        // Balilihan Campus has the working dashboards; the other campuses' directors appear in history only
        $people = [
            ['ed', 'Sir Marc Olaguir', 'Extension Director', 'extension_director', 'Balilihan', null],
            ['asst', 'Miss Jaquelyn L. Omapas', "Extension Director's Assistant", 'extension_assistant', 'Balilihan', 'ed'],
            ['cd', 'Mrs. Marietta Macalolot', 'Campus Director', 'campus_director', 'Balilihan', null],
            ['cdasst', 'Lorna Abella', "Campus Director's Assistant", 'campus_assistant', 'Balilihan', 'cd'],
            ['pres', 'Mr. Anthony M. Penaso', 'University President', 'president', null, null],
        ];
        foreach (array_keys(self::CAMPUSES) as $name) {
            if ($name === 'Balilihan') {
                continue;
            }
            $people[] = [$name.'-ed', 'Extension Director of '.$name.' Campus', 'Extension Director', 'extension_director', $name, null];
            $people[] = [$name.'-cd', 'Campus Director of '.$name.' Campus', 'Campus Director', 'campus_director', $name, null];
        }
        foreach ($people as [$key, $name, $title, $role, $campusName]) {
            Official::firstOrCreate(['key' => $key], [
                'name' => $name, 'title' => $title, 'role' => $role, 'campus_id' => $campusName ? $campus[$campusName] : null,
            ]);
        }
        foreach ($people as [$key, , , , , $principal]) {
            if ($principal) {
                Official::where('key', $key)->update(['assistant_of' => Official::where('key', $principal)->value('id')]);
            }
        }

        foreach (self::CATEGORIES as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
        foreach (self::SECTORS as $i => $name) {
            BeneficiarySector::firstOrCreate(['name' => $name], ['position' => $i]);
        }
    }
}
