<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Guests hitting protected pages go to the Filament login
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));

        // Behind a sharing tunnel (ngrok, VS Code port forwarding) the page is served over https
        // from another host; trust the tunnel's forwarded headers so links and assets use that address.
        // Fine for sharing the prototype; production should list its real proxies here instead of '*'.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
