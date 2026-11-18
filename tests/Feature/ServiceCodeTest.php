<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Service;
use App\Services\ServiceCodeGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCodeTest extends TestCase
{
    use RefreshDatabase;

    private function campus(string $name): Campus
    {
        return Campus::create(['name' => $name, 'municipality' => $name]);
    }

    private function next(Campus $campus, int $year = 2026): string
    {
        return app(ServiceCodeGenerator::class)->next($campus, $year);
    }

    public function test_code_uses_prefix_year_and_sequence(): void
    {
        $balilihan = $this->campus('Balilihan');
        $calape = $this->campus('Calape');

        $first = $this->next($balilihan);
        Service::factory()->for($balilihan)->create(['code' => $first]);

        $this->assertSame('BAL-2026-001', $first);
        $this->assertSame('BAL-2026-002', $this->next($balilihan));
        $this->assertSame('CAL-2026-001', $this->next($calape));
    }

    public function test_soft_deleted_codes_are_not_reused(): void
    {
        $balilihan = $this->campus('Balilihan');
        $service = Service::factory()->for($balilihan)->create(['code' => $this->next($balilihan)]);
        $service->delete();

        $this->assertSame('BAL-2026-002', $this->next($balilihan));
    }

    public function test_codes_stay_unique_when_generated_back_to_back(): void
    {
        $balilihan = $this->campus('Balilihan');
        $codes = [];
        for ($i = 0; $i < 20; $i++) {
            $code = $this->next($balilihan);
            Service::factory()->for($balilihan)->create(['code' => $code]);
            $codes[] = $code;
        }

        $this->assertCount(20, array_unique($codes));

        $this->expectException(UniqueConstraintViolationException::class);
        Service::factory()->for($balilihan)->create(['code' => $codes[0]]);
    }

    public function test_format_comes_from_config(): void
    {
        config(['extrack.code.format' => '{YEAR}/{PREFIX}/{SEQ}']);

        $this->assertSame('2026/BAL/001', $this->next($this->campus('Balilihan')));
    }
}
