<?php


namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Enums\ThemeMode;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\app\filament\Pages\Auth\Login::class)
            ->passwordReset()
            ->colors([
                'primary' => Color::hex('#3A3272'),
                'warning' => Color::hex('#D9B44A'),
            ])
            ->brandName('EXTRACK')
            ->font('Monserrat')
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Light)
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => '<link rel="stylesheet" href="'.asset('css/admin-login-glass.css').'">'
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => '<link rel="stylesheet" href="'.asset('css/admin-login-glass.css').'">'
            )
            ->renderHook(PanelsRenderHook::BODY_START, fn () => view('filament.login-art'))
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => view('filament.login-note'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
