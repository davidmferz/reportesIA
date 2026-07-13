<?php

use Illuminate\Console\Scheduling\Schedule;
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
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'license' => \App\Http\Middleware\EnsureLicenseIsValid::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Phone-home diario: revalida/renueva/revoca la licencia local
        // contra el licensing-server. Offline-first: si el server no
        // responde, el estado persistido queda intacto (ver LicenseCheckCommand).
        $schedule->command('license:check')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
