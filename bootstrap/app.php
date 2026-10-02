<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EstablishPromotionContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [EnsureUserIsActive::class]);
        $middleware->authenticateSessions();
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/dashboard');
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: EstablishPromotionContext::class);
        $middleware->prependToPriorityList(before: EstablishPromotionContext::class, prepend: EnsureUserIsActive::class);
        $middleware->alias([
            'promotion.context' => EstablishPromotionContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
