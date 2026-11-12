<?php

use App\Http\Middleware\EnsureStaffIsActive;
use App\Http\Middleware\EnsureStaffIsAdmin;
use App\Http\Middleware\SecurityHeaders;
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
        $middleware->append(SecurityHeaders::class);
        $middleware->redirectGuestsTo(fn () => route('staff.login'));
        $middleware->alias([
            'staff.active' => EnsureStaffIsActive::class,
            'staff.admin' => EnsureStaffIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Configure exception reporting and rendering here.
    })->create();
