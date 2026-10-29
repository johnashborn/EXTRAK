<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            \Filament\Http\Responses\Auth\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // the prototype dashboards start from the data saved on the server
        \Illuminate\Support\Facades\View::composer('prototype.partials.head', function ($view) {
            $state = \App\Models\PrototypeState::current();
            $view->with('prototypeServer', [
                'state' => $state?->data,
                'version' => $state?->version ?? 0,
                'stateUrl' => route('prototype.state.update'),
                'filesUrl' => route('prototype.files.store'),
            ]);
        });
    }
}
