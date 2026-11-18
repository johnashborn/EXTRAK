<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Authenticator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    public function show(Request $request, Authenticator $authenticator): Response
    {
        return $authenticator->showLogin($request);
    }

    public function store(Request $request, Authenticator $authenticator): RedirectResponse
    {
        return $this->signIn($request, $authenticator->authenticate($request));
    }

    /** Demo-only one-click sign-in. The route always exists; the config check makes it 404 when demo is off. */
    public function demo(Request $request, User $user): RedirectResponse
    {
        abort_unless(config('extrack.demo') && $user->is_active, 404);

        return $this->signIn($request, $user);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function signIn(Request $request, User $user): RedirectResponse
    {
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }
}
