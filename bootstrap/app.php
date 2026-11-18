<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Services\ApiResponse;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Guests hitting protected pages go to the sign-in page
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');

        // An oversized upload loses its body, CSRF token included. Check the size after the session
        // starts (so "back with an error" can flash the message) and before the CSRF check (which
        // would otherwise answer with a confusing "page expired").
        $middleware->remove(ValidatePostSize::class);
        $middleware->group('web', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidatePostSize::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
        ]);
        $middleware->prependToGroup('api', ValidatePostSize::class);

        // Deactivated users are signed out on their next request
        $middleware->appendToGroup('web', EnsureUserIsActive::class);

        // Behind a sharing tunnel (ngrok, VS Code port forwarding) the page is served over https
        // from another host; trust the tunnel's forwarded headers so links and assets use that address.
        // Fine for sharing the prototype; production should list its real proxies here instead of '*'.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            $message = 'This file is larger than the '.config('extrack.files.max_mb').' MB limit';

            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error($message, ['upload' => [$message]], 413);
            }

            return back()->withErrors(['upload' => $message]);
        });

        // JSON validation errors use the shared shape.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error($e->getMessage(), $e->errors(), $e->status);
            }
        });
    })->create();
