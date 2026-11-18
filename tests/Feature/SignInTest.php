<?php

namespace Tests\Feature;

use App\Auth\FindUserForSignIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignInTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_signs_in_and_lands_on_dashboard(): void
    {
        $user = User::factory()->president()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_wrong_password_shows_error(): void
    {
        $user = User::factory()->president()->create();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => "These details don't match an active account."]);

        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_sign_in(): void
    {
        $user = User::factory()->president()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_user_is_signed_out_on_next_request(): void
    {
        $user = User::factory()->president()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($user);

        $user->update(['is_active' => false]);
        auth()->forgetGuards(); // the next request builds a fresh guard and reloads the user

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_demo_buttons_only_when_demo_is_on(): void
    {
        $user = User::factory()->president()->create();

        config(['extrack.demo' => true]);
        $this->get('/login')->assertSee('Sign in as');
        $this->post("/login/demo/{$user->id}")->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        config(['extrack.demo' => false]);
        $this->get('/login')->assertDontSee('Sign in as');
        $this->post("/login/demo/{$user->id}")->assertNotFound();
    }

    public function test_find_user_never_creates_accounts(): void
    {
        $this->assertNull(app(FindUserForSignIn::class)->handle('stranger@example.com', 'zitadel', 'abc'));
        $this->assertDatabaseCount('users', 0);

        $user = User::factory()->president()->create(['email' => 'a@example.com']);
        $found = app(FindUserForSignIn::class)->handle('a@example.com', 'zitadel', 'ext-1');
        $this->assertTrue($found->is($user));
        $this->assertSame('ext-1', $user->fresh()->external_id);
        $this->assertSame('zitadel', $user->fresh()->auth_provider);

        $inactive = User::factory()->president()->inactive()->create(['email' => 'b@example.com']);
        $this->assertNull(app(FindUserForSignIn::class)->handle($inactive->email, 'zitadel', 'x'));
    }
}
