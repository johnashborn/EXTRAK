<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrototypeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        foreach (['/prototype', '/prototype/extension-director', '/prototype/campus-director', '/prototype/president'] as $url) {
            $this->get($url)->assertRedirect(route('filament.admin.auth.login'));
        }
    }

    public function test_signed_in_users_see_the_dashboard_chooser(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/prototype')
            ->assertOk()
            ->assertSee(route('prototype.extension-director'), false)
            ->assertSee(route('prototype.campus-director'), false)
            ->assertSee(route('prototype.president'), false);
    }

    public function test_signed_in_users_see_each_dashboard_with_their_name(): void
    {
        $user = User::factory()->create(['name' => 'Ma. Elena Racines']);

        foreach (['extension-director' => '"ed"', 'campus-director' => '"cd"', 'president' => '"pres"'] as $page => $role) {
            $this->actingAs($user)
                ->get('/prototype/'.$page)
                ->assertOk()
                ->assertSee('"name":"Ma. Elena Racines"', false)
                ->assertSee('window.EXTRACK_ROLE = '.$role, false)
                ->assertSee(route('filament.admin.auth.logout'), false);
        }
    }

    public function test_signing_in_redirects_to_the_dashboard_chooser(): void
    {
        $response = app(LoginResponse::class)->toResponse(request());

        $this->assertSame(route('prototype.index'), $response->getTargetUrl());
    }
}
