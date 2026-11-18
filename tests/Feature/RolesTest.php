<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_abilities_follow_the_config(): void
    {
        $campus = Campus::factory()->create();
        $uploader = User::factory()->campusUploader($campus)->create();
        $director = User::factory()->campusDirector($campus)->create();

        $this->assertTrue($uploader->hasAbility('publish'));
        $this->assertFalse($director->hasAbility('publish'));

        config(['extrack.permissions.publish' => ['campus_uploader', 'campus_director']]);

        $this->assertTrue($director->hasAbility('publish'));
    }

    public function test_unknown_ability_is_denied(): void
    {
        $president = User::factory()->president()->create();

        $this->assertFalse($president->hasAbility('does_not_exist'));
        $this->assertFalse($president->hasAbility('permissions.publish'));
    }

    public function test_president_is_not_campus_bound(): void
    {
        $this->assertFalse(User::factory()->president()->create()->isCampusBound());
    }

    public function test_uploader_and_director_are_campus_bound(): void
    {
        $campus = Campus::factory()->create();

        $this->assertTrue(User::factory()->campusUploader($campus)->create()->isCampusBound());
        $this->assertTrue(User::factory()->campusDirector($campus)->create()->isCampusBound());
        $this->assertFalse(User::factory()->inactive()->create()->is_active);
    }

    public function test_role_labels_come_from_config(): void
    {
        $this->assertSame(config('extrack.role_labels.campus_uploader'), Role::CampusUploader->label());
        $this->assertSame(config('extrack.role_labels.president'), Role::President->label());

        config(['extrack.role_labels.campus_uploader' => 'Extension Focal Person']);
        $this->assertSame('Extension Focal Person', Role::CampusUploader->label());

        config(['extrack.role_labels' => []]);
        $this->assertSame('Campus Director', Role::CampusDirector->label());
    }
}
