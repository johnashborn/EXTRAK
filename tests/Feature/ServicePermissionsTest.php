<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\ServiceState;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ServiceFixtures;
use Tests\TestCase;

/**
 * The section 5 table. Expected results are worked out from config('extrack.permissions') and the
 * own-campus rule, so the test follows the config when the mentors change a permission.
 */
class ServicePermissionsTest extends TestCase
{
    use RefreshDatabase;
    use ServiceFixtures;

    private const ACTIONS = ['create', 'create_and_publish', 'edit_draft', 'edit_published', 'publish', 'discard'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->withoutVite();
        $this->setUpReferenceData();
    }

    /**
     * [role, action, does the user belong to the record's campus (Balilihan)?]
     * Named rows are the denials the brief calls out; under the default config they are 403.
     *
     * @return array<string, array{string, string, bool}>
     */
    public static function matrix(): array
    {
        $rows = [];

        foreach (self::ACTIONS as $action) {
            $rows["president / $action"] = ['president', $action, false];
            foreach (['campus_uploader', 'campus_director'] as $role) {
                $rows["$role / $action / own campus"] = [$role, $action, true];
                $rows["$role / $action / other campus"] = [$role, $action, false];
            }
        }

        return $rows;
    }

    #[DataProvider('matrix')]
    public function test_section_5_table(string $role, string $action, bool $ownCampus): void
    {
        $user = $this->userFor($role, $ownCampus);

        $this->attempt($user, $action)->assertStatus($this->expectedStatus($user, $action));
    }

    public function test_named_denials_are_forbidden_under_the_default_config(): void
    {
        $director = $this->userFor('campus_director', true);
        $calapeUploader = $this->userFor('campus_uploader', false);
        $president = $this->userFor('president', false);

        // Each case is only asserted when config denies it, so changing a permission never breaks this test.
        $cases = [
            'director publishes' => [$director, 'create_and_publish'],
            'Calape uploader edits a Balilihan record' => [$calapeUploader, 'edit_published'],
            'director creates' => [$director, 'create'],
            'president creates' => [$president, 'create'],
        ];

        foreach ($cases as $label => [$user, $action]) {
            if ($this->expectedStatus($user, $action) === 403) {
                $this->attempt($user, $action)->assertForbidden();
            }
        }

        $this->assertSame(403, $this->expectedStatus($director, 'create_and_publish'), 'Section 5: the Campus Director does not publish');
    }

    public function test_permissions_follow_config(): void
    {
        config(['extrack.permissions.create' => ['campus_uploader', 'campus_director']]);
        $this->attempt($this->userFor('campus_director', true), 'create')->assertOk();

        config(['extrack.permissions.publish' => []]);
        $this->attempt($this->userFor('campus_uploader', true), 'create_and_publish')->assertForbidden();
        $this->attempt($this->userFor('campus_uploader', true), 'publish')->assertForbidden();
    }

    public function test_record_page_follows_visibility(): void
    {
        $draft = $this->completeService($this->balilihan);
        $published = $this->completeService($this->calape, ServiceState::Published, ['code' => 'CAL-2026-001']);

        $this->actingAs($this->userFor('campus_uploader', true))->get(route('services.show', $draft))->assertOk();
        $this->actingAs($this->userFor('campus_uploader', false))->get(route('services.show', $draft))->assertNotFound();
        $this->actingAs($this->userFor('campus_director', true))->get(route('services.show', $draft))->assertNotFound();

        foreach (['president', 'campus_uploader', 'campus_director'] as $role) {
            $this->actingAs($this->userFor($role, true))->get(route('services.show', $published))
                ->assertStatus(in_array($role, config('extrack.permissions.view_published'), true) ? 200 : 403);
        }
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get(route('services.create'))->assertRedirect('/login');
        $this->post(route('services.store'))->assertRedirect('/login');
    }

    private function userFor(string $role, bool $ownCampus): User
    {
        $campus = $ownCampus ? $this->balilihan : $this->calape;

        return match (Role::from($role)) {
            Role::President => User::factory()->president()->create(),
            Role::CampusUploader => User::factory()->campusUploader($campus)->create(),
            Role::CampusDirector => User::factory()->campusDirector($campus)->create(),
        };
    }

    private function can(User $user, string $ability): bool
    {
        return in_array($user->role->value, (array) config("extrack.permissions.$ability"), true);
    }

    private function expectedStatus(User $user, string $action): int
    {
        $hasCampus = $user->campus_id !== null;
        $own = $user->campus_id === $this->balilihan->id;
        // Drafts are hidden (404) from anyone who can't see their campus's drafts.
        $seesDraft = $this->can($user, 'view_drafts') && $own;
        $allowed = fn (bool $rule) => $rule ? 200 : 403;

        return match ($action) {
            'create' => $allowed($hasCampus && $this->can($user, 'create')),
            'create_and_publish' => $allowed($hasCampus && $this->can($user, 'create') && $this->can($user, 'publish')),
            'edit_draft' => $seesDraft ? $allowed($this->can($user, 'edit_draft')) : 404,
            'edit_published' => $allowed($own && $this->can($user, 'edit_published')),
            'publish' => $seesDraft ? $allowed($this->can($user, 'publish')) : 404,
            'discard' => $seesDraft ? $allowed($this->can($user, 'discard_draft')) : 404,
        };
    }

    private function attempt(User $user, string $action): TestResponse
    {
        $this->actingAs($user);
        $keepReport = ['report_file' => null, 'report_copy_type' => null];

        return match ($action) {
            'create' => $this->postJson(route('services.store'), $this->fullPayload(['intent' => 'draft'])),
            'create_and_publish' => $this->postJson(route('services.store'), $this->fullPayload(['intent' => 'publish'])),
            'edit_draft' => $this->putJson(
                route('services.update', $this->draft()),
                $this->fullPayload(['intent' => 'draft', 'title' => 'Renamed'] + $keepReport),
            ),
            'edit_published' => $this->putJson(
                route('services.update', $this->completeService($this->balilihan, ServiceState::Published, ['code' => 'BAL-2025-001'])),
                $this->fullPayload(['intent' => 'publish', 'title' => 'Renamed'] + $keepReport),
            ),
            'publish' => $this->postJson(route('services.publish', $this->draft())),
            'discard' => $this->deleteJson(route('services.discard', $this->draft())),
        };
    }

    /** A complete Balilihan draft with its report. */
    private function draft(): Service
    {
        return $this->completeService($this->balilihan);
    }
}
