<?php

namespace Tests\Feature;

use App\Enums\ServiceState;
use App\Models\Campus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_drafts_are_seen_only_by_their_campus_uploaders(): void
    {
        $balilihan = Campus::create(['name' => 'Balilihan', 'municipality' => 'Balilihan']);
        $calape = Campus::create(['name' => 'Calape', 'municipality' => 'Calape']);

        $draft = Service::factory()->for($balilihan)->create(['state' => ServiceState::Draft]);
        $published = Service::factory()->for($balilihan)->published()->create();

        $viewers = [
            'own uploader' => [User::factory()->campusUploader($balilihan)->create(), true],
            'other uploader' => [User::factory()->campusUploader($calape)->create(), false],
            'own director' => [User::factory()->campusDirector($balilihan)->create(), false],
            'president' => [User::factory()->president()->create(), false],
        ];

        foreach ($viewers as $label => [$user, $seesDraft]) {
            $this->actingAs($user);
            $this->assertSame($seesDraft, Service::whereKey($draft->id)->exists(), $label.' / draft');
            $this->assertTrue(Service::whereKey($published->id)->exists(), $label.' / published');
        }
    }

    public function test_nothing_is_filtered_when_nobody_is_signed_in(): void
    {
        $campus = Campus::create(['name' => 'Balilihan', 'municipality' => 'Balilihan']);
        Service::factory()->for($campus)->create();

        $this->assertSame(1, Service::count());
    }
}
