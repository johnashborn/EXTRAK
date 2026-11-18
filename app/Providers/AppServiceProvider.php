<?php

namespace App\Providers;

use App\Auth\Authenticator;
use App\Auth\LocalAuthenticator;
use App\Services\ReportReading\NullReportReader;
use App\Services\ReportReading\ReportReader;
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

        $this->app->bind(ReportReader::class, function ($app) {
            $reader = config('extrack.report_reader');

            return match ($reader) {
                'null' => $app->make(NullReportReader::class),
                default => throw new RuntimeException(
                    "The report reader [{$reader}] is not implemented yet. Set EXTRACK_REPORT_READER=null."
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
