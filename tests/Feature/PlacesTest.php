<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\Municipality;
use Database\Seeders\BoholPlacesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_bohol_list_is_complete(): void
    {
        $this->seed(BoholPlacesSeeder::class);

        $this->assertSame(48, Municipality::count());
        $this->assertSame(1109, Barangay::count());
        $this->assertTrue(Municipality::where('name', 'Tagbilaran City')->exists());
    }

    public function test_coordinates_are_inside_bohol(): void
    {
        $this->seed(BoholPlacesSeeder::class);

        $this->assertSame(0, Municipality::where(fn ($q) => $q
            ->whereNotBetween('latitude', [9.5, 10.25])
            ->orWhereNotBetween('longitude', [123.65, 124.65]))->count());
        $this->assertSame(0, Barangay::whereNotNull('latitude')->where(fn ($q) => $q
            ->whereNotBetween('latitude', [9.5, 10.25])
            ->orWhereNotBetween('longitude', [123.65, 124.65]))->count());
    }

    public function test_seeding_twice_does_not_duplicate(): void
    {
        $this->seed(BoholPlacesSeeder::class);
        $this->seed(BoholPlacesSeeder::class);

        $this->assertSame(48, Municipality::count());
        $this->assertSame(1109, Barangay::count());
    }
}
