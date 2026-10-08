<?php

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetLocale;
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
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // Short names used in routes/web.php
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);

        // Guests who open a protected page: go to login with a short note (Laravel remembers the page)
        $middleware->redirectGuestsTo(function (Request $request) {
            $request->session()->flash('login_notice', __('auth.login_required'));

            return route('login');
        });

        // Logged-in users who open /login go to their own dashboard
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->role . '.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
