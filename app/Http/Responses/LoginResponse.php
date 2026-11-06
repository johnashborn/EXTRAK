<?php

namespace App\Http\Responses;

use App\Support\PrototypeRole;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Sends users to the dashboard of the role they signed in as,
 * instead of the empty Filament dashboard.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->route(PrototypeRole::route(PrototypeRole::current()));
    }
}
