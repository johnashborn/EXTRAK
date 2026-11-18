<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The sign-in seam. LocalAuthenticator handles email and password today; a future
 * ZitadelAuthenticator redirects in showLogin() and handles the Socialite callback in authenticate().
 */
interface Authenticator
{
    public function showLogin(Request $r): Response;

    /**
     * @throws \Illuminate\Validation\ValidationException when the details do not match an active account
     */
    public function authenticate(Request $r): User;
}
