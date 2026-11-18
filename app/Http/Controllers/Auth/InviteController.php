<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInviteRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class InviteController extends Controller
{
    public function show(User $user): View
    {
        $this->ensureUsable($user);

        return view('auth.accept-invite', ['invitee' => $user]);
    }

    public function store(AcceptInviteRequest $request, User $user): RedirectResponse
    {
        $this->ensureUsable($user);

        $user->forceFill([
            'password' => $request->validated('password'),
            'last_login_at' => now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function ensureUsable(User $user): void
    {
        abort_if($user->password !== null, 410, 'This invite has already been used');
        abort_unless($user->is_active, 403, 'This account is deactivated.');
    }
}
