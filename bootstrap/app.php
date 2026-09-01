<?php

use App\Http\Middleware\EnsureActiveBusiness;
use App\Http\Middleware\EnsureActiveEmployeeBusiness;
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
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->is('employee*')
                ? route('employee.login')
                : route('login'),
        );
        $middleware->redirectUsersTo(
            fn (Request $request): string => $request->is('employee*')
                ? route('employee.dashboard')
                : route('overview'),
        );
        $middleware->alias([
            'active.business' => EnsureActiveBusiness::class,
            'active.employee.business' => EnsureActiveEmployeeBusiness::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
