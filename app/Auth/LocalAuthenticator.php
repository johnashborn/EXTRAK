<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LocalAuthenticator implements Authenticator
{
    public function __construct(private readonly FindUserForSignIn $findUser)
    {
    }

    public function showLogin(Request $r): Response
    {
        $demoUsers = config('extrack.demo')
            ? User::query()->where('is_active', true)->orderBy('name')->get()
            : collect();

        return response()->view('auth.login', ['demoUsers' => $demoUsers]);
    }

    public function authenticate(Request $r): User
    {
        $credentials = $r->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = $this->findUser->handle($credentials['email'], 'local');

        if ($user === null
            || $user->password === null
            || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => "These details don't match an active account.",
            ]);
        }

        return $user;
    }
}
