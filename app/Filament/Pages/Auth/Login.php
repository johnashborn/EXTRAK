<?php

namespace App\Filament\Pages\Auth;

use App\Support\PrototypeRole;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\ToggleButtons;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
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

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getRoleFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    /** Prototype only: which dashboard to open. In the full build the role comes from the account. */
    protected function getRoleFormComponent(): Component
    {
        // a row of tabs across the top of the card
        return ToggleButtons::make('role')
            ->label('Sign in as')
            ->options(PrototypeRole::TAB_LABELS)
            ->grouped()
            ->extraAttributes(['class' => 'x-roles'])
            ->default(PrototypeRole::EXTENSION_DIRECTOR)
            ->required()
            ->markAsRequired(false) // one tab is always chosen, so no asterisk
            ->in(array_keys(PrototypeRole::LABELS));
    }

    public function authenticate(): ?LoginResponse
    {
        $role = $this->data['role'] ?? null;
        $response = parent::authenticate();

        // only reached when the email and password were accepted
        if ($response !== null) {
            session([PrototypeRole::SESSION_KEY => PrototypeRole::valid($role)]);
        }

        return $response;
    }
}
