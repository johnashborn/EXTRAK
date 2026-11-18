<?php

namespace App\Providers;

use App\Auth\Authenticator;
use App\Auth\LocalAuthenticator;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(Authenticator::class, function ($app) {
            $provider = config('extrack.auth_provider');

            return match ($provider) {
                'local' => $app->make(LocalAuthenticator::class),
                default => throw new RuntimeException(
                    "The auth provider [{$provider}] is not implemented yet. Set AUTH_PROVIDER=local."
                ),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
