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
        // short names in attachments.attachable_type instead of class names
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'extension_request' => \App\Models\ExtensionRequest::class,
            'accomplishment_report' => \App\Models\AccomplishmentReport::class,
        ]);

        // the prototype dashboards start from the data saved on the server
        \Illuminate\Support\Facades\View::composer('prototype.partials.head', function ($view) {
            $store = app(\App\Services\Prototype\DashboardDataStore::class);
            $view->with('prototypeServer', [
                'state' => $store->export(),
                'version' => $store->version(),
                'stateUrl' => route('prototype.state.update'),
                'filesUrl' => route('prototype.files.store'),
            ]);
            // the person picked on the sign-in form, so the dashboard opens as them
            $view->with('prototypeActor', \App\Support\PrototypeRole::current());
        });
    }
}
