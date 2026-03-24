<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn(Request $request) => $request->expectsJson() ? null : '/login');
        $middleware->redirectUsersTo('/ui/dashboard');

        $middleware->validateCsrfTokens(except: [
            'logout',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\PreventBrowserCache::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'program.enabled' => \App\Http\Middleware\EnsureProgramIsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Sesi login sudah kedaluwarsa. Silakan coba login kembali.',
                ]);
        });
    })->create();
