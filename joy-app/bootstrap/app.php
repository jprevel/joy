<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'validate.magic.link' => \App\Http\Middleware\ValidateMagicLink::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'auth.api' => \App\Http\Middleware\EnsureAuthenticated::class,
            'client.access' => \App\Http\Middleware\ResolveClientAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $exception, $request) {
            // Handle authentication failures
            if ($exception instanceof \Illuminate\Auth\AuthenticationException && $request->expectsHtml()) {
                session()->flash('status', 'You\'ve been logged out. Please log in.');
            }

            // Handle CSRF token expiration (419 Page Expired error)
            if ($exception instanceof \Illuminate\Session\TokenMismatchException && $request->expectsHtml()) {
                // Clear old session and redirect to login with friendly message
                session()->invalidate();
                session()->regenerateToken();
                return redirect()->route('login')->with('status', 'Your session has expired. Please log in again.');
            }

            return $response;
        });
    })->create();
