<?php

namespace Tests\Feature;

use App\Models\BeneficiarySector;
use App\Models\Campus;
use App\Models\Category;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanSlateTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_still_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_landing_links_to_login(): void
    {
        $this->get('/')->assertSee('href="'.url('/login').'"', false);
    }

    public function test_old_routes_are_gone(): void
    {
        $this->get('/prototype')->assertNotFound();
        $this->get('/admin')->assertNotFound();
    }

    public function test_reference_seeder_fills_the_lists(): void
    {
        $this->seed(ReferenceSeeder::class);

        $this->assertSame(6, Campus::count());
        $this->assertSame(7, Category::count());
        $this->assertSame(10, BeneficiarySector::count());
    }
}
