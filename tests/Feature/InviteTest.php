<?php

namespace Tests\Feature;

use App\Actions\Users\InviteUser;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{user: User, url: string} */
    private function invite(): array
    {
        $president = User::factory()->president()->create();
        $campus = Campus::factory()->create();

        return app(InviteUser::class)->handle($president, [
            'name' => 'New Person', 'email' => 'new@example.com',
            'role' => 'campus_uploader', 'campus_id' => $campus->id,
        ]);
    }

    public function test_invite_link_sets_password_once(): void
    {
        ['user' => $user, 'url' => $url] = $this->invite();
        $this->assertNull($user->password);

        $this->get($url)->assertOk();
        $this->post($url, ['password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $this->get($url)->assertStatus(410)->assertSee('This invite has already been used');
        $this->post($url, ['password' => 'other-pass-22', 'password_confirmation' => 'other-pass-22'])->assertStatus(410);
    }

    public function test_invite_link_expires_after_configured_days(): void
    {
        ['url' => $url] = $this->invite();

        $this->travel(8)->days();

        $this->get($url)->assertForbidden();
    }

    public function test_campus_roles_require_a_campus(): void
    {
        $president = User::factory()->president()->create();

        foreach (['campus_uploader', 'campus_director'] as $role) {
            $this->actingAs($president)
                ->post('/users', ['name' => 'X', 'email' => "$role@example.com", 'role' => $role])
                ->assertSessionHasErrors('campus_id');
        }

        $this->post('/users', [
            'name' => 'P', 'email' => 'p@example.com', 'role' => 'president',
            'campus_id' => Campus::factory()->create()->id,
        ])->assertSessionHasErrors('campus_id');

        $this->post('/users', ['name' => 'P', 'email' => 'p@example.com', 'role' => 'president'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'p@example.com', 'role' => 'president']);
    }
}
