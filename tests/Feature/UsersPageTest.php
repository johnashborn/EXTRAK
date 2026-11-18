<?php

namespace Tests\Feature;

use App\Actions\Users\SetUserActive;
use App\Actions\Users\UpdateUser;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UsersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_president_manages_users(): void
    {
        $campus = Campus::factory()->create();

        $this->actingAs(User::factory()->campusUploader($campus)->create())->get('/users')->assertForbidden();
        $this->actingAs(User::factory()->campusDirector($campus)->create())->get('/users')->assertForbidden();
        $this->actingAs(User::factory()->president()->create(['name' => 'Boss']))->get('/users')->assertOk()->assertSee('Boss');
    }

    public function test_president_cannot_deactivate_self(): void
    {
        $president = User::factory()->president()->create();

        $this->actingAs($president)->patch("/users/{$president->id}/active", ['is_active' => 0])
            ->assertForbidden();

        $this->assertTrue($president->fresh()->is_active);
    }

    public function test_president_cannot_change_own_role(): void
    {
        $campus = Campus::factory()->create();
        $president = User::factory()->president()->create(['name' => 'Boss']);
        User::factory()->president()->create(); // a second manager, so only the self rule applies

        $this->actingAs($president)->put("/users/{$president->id}", [
            'name' => 'Boss', 'role' => 'campus_director', 'campus_id' => $campus->id,
        ])->assertSessionHasErrors(['role' => "You can't change your own role or campus."]);
        $this->assertSame(Role::President, $president->fresh()->role);

        // name may still change
        $this->put("/users/{$president->id}", ['name' => 'Renamed Boss', 'role' => 'president'])
            ->assertSessionHasNoErrors()->assertRedirect('/users');
        $this->assertSame('Renamed Boss', $president->fresh()->name);

        // the edit form locks role and campus for yourself but still submits them
        $this->get("/users/{$president->id}/edit")->assertOk()
            ->assertSee('type="hidden" name="role" value="president"', false);
        $this->get('/users/create')->assertOk();
    }

    public function test_last_user_manager_cannot_be_demoted_or_deactivated(): void
    {
        $campus = Campus::factory()->create();
        $a = User::factory()->president()->create();
        $b = User::factory()->president()->create();

        // Two Presidents: A can demote B.
        $this->actingAs($a)->put("/users/{$b->id}", [
            'name' => $b->name, 'role' => 'campus_director', 'campus_id' => $campus->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame(Role::CampusDirector, $b->fresh()->role);

        // A is now the only manager. A non-manager reaching the actions directly is still refused.
        $outsider = User::factory()->campusUploader($campus)->create();
        $message = 'At least one active user must be able to manage users.';

        try {
            app(UpdateUser::class)->handle($outsider, $a, ['name' => $a->name, 'role' => 'campus_director', 'campus_id' => $campus->id]);
            $this->fail('Demotion of the last manager was allowed.');
        } catch (ValidationException $e) {
            $this->assertSame($message, $e->errors()['role'][0]);
        }

        try {
            app(SetUserActive::class)->handle($outsider, $a, false);
            $this->fail('Deactivation of the last manager was allowed.');
        } catch (ValidationException $e) {
            $this->assertSame($message, $e->errors()['is_active'][0]);
        }

        // Over HTTP the sole manager cannot demote themselves either.
        $this->actingAs($a)->put("/users/{$a->id}", [
            'name' => $a->name, 'role' => 'campus_director', 'campus_id' => $campus->id,
        ])->assertSessionHasErrors(['role' => $message]);
        $this->assertSame(Role::President, $a->fresh()->role);
        $this->assertTrue($a->fresh()->is_active);
    }

    public function test_invite_shows_url_and_activity_is_logged(): void
    {
        $president = User::factory()->president()->create();
        $campus = Campus::factory()->create();

        $this->actingAs($president)->post('/users', [
            'name' => 'Dir', 'email' => 'dir@example.com', 'role' => 'campus_director', 'campus_id' => $campus->id,
        ])->assertRedirect('/users')->assertSessionHas('invite_url');

        $this->get('/users')->assertSee('/invites/', false)->assertSee('Copy');
        $this->assertDatabaseHas('activity_log', ['action' => 'user_invited']);
    }

    public function test_update_and_toggle_active_are_logged(): void
    {
        $president = User::factory()->president()->create();
        $campus = Campus::factory()->create();
        $other = User::factory()->campusUploader($campus)->create();

        $this->actingAs($president)->put("/users/{$other->id}", [
            'name' => 'Renamed', 'role' => 'campus_director', 'campus_id' => $campus->id,
        ])->assertRedirect('/users');
        $this->assertSame('Renamed', $other->fresh()->name);

        $this->patch("/users/{$other->id}/active", ['is_active' => 0])->assertRedirect('/users');
        $this->assertFalse($other->fresh()->is_active);
        $this->patch("/users/{$other->id}/active", ['is_active' => 1])->assertRedirect('/users');

        foreach (['user_updated', 'user_deactivated', 'user_reactivated'] as $a) {
            $this->assertTrue(ActivityLog::where('action', $a)->exists(), $a);
        }
    }
}
