<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Router;

test('the application uses Laravel security middleware', function () {
    $router = resolve(Router::class);

    expect($router->getMiddlewareGroups()['web'])
        ->toContain(PreventRequestForgery::class)
        ->and($router->getMiddleware()['signed'])
        ->toBe(ValidateSignature::class);
});
