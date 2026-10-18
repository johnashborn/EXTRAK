<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string|Htmlable
    {
        return 'Sign in to EXTRACK';
    }

    public function getSubHeading(): string|Htmlable|null
    {
        return 'Use your university email and password.';
    }
}
