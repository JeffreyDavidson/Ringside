<?php

declare(strict_types=1);

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * @return list<class-string>
 */
function applicationControllers(): array
{
    return array_values(collect(File::allFiles(app_path('Http/Controllers')))
        ->map(function (SplFileInfo $file): string {
            $relativePath = Str::beforeLast($file->getRelativePathname(), '.php');

            return 'App\\Http\\Controllers\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
        })
        ->filter(fn (string $controller): bool => class_exists($controller))
        ->values()
        ->all());
}

/**
 * @param  class-string  $controller
 * @return list<non-empty-string>
 */
function declaredPublicMethods(string $controller): array
{
    $reflection = new ReflectionClass($controller);

    return array_values(collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
        ->filter(fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $controller)
        ->map(fn (ReflectionMethod $method): string => $method->getName())
        ->sort()
        ->values()
        ->all());
}

function isDomainControllerRoute(IlluminateRoute $route): bool
{
    $action = $route->getActionName();

    return str_starts_with($action, 'App\\Http\\Controllers\\')
        && ! str_starts_with($action, 'App\\Http\\Controllers\\Auth\\')
        && str_contains($action, '@');
}

test('controllers are resourceful or invokable', function () {
    $resourceMethods = ['create', 'destroy', 'edit', 'index', 'show', 'store', 'update'];
    $controllerCount = 0;

    foreach (applicationControllers() as $controller) {
        $reflection = new ReflectionClass($controller);

        if ($reflection->isAbstract()) {
            continue;
        }

        $controllerCount++;
        $methods = declaredPublicMethods($controller);

        expect($methods)->not->toBeEmpty();

        if (in_array('__invoke', $methods, true)) {
            expect($methods)->toBe(['__invoke']);

            continue;
        }

        expect(array_diff($methods, $resourceMethods))->toBeEmpty();
    }

    expect($controllerCount)->toBeGreaterThan(0);
});

test('domain resource controllers use plural resource names', function () {
    foreach (applicationControllers() as $controller) {
        if (str_starts_with($controller, 'App\\Http\\Controllers\\Auth\\')) {
            continue;
        }

        $methods = declaredPublicMethods($controller);

        if ($methods === ['__invoke']) {
            continue;
        }

        $resource = Str::beforeLast(class_basename($controller), 'Controller');

        expect($resource)->toBe(Str::pluralStudly(Str::singular($resource)));
    }
});

test('domain resource routes are named and authorized at the route', function () {
    $routeCount = 0;

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (! isDomainControllerRoute($route)) {
            continue;
        }

        $routeCount++;
        [, $method] = explode('@', $route->getActionName(), 2);
        $middleware = $route->middleware();

        expect($route->getName())
            ->not->toBeNull()
            ->toEndWith(".{$method}")
            ->and(collect($middleware)->contains(
                fn (string $value): bool => str_starts_with($value, 'can:'),
            ))
            ->toBeTrue();
    }

    expect($routeCount)->toBeGreaterThan(0);
});

test('authorization coverage is colocated with its endpoint or component', function () {
    $authorizationDirectory = base_path('tests/Feature/Authorization');
    $authorizationTests = is_dir($authorizationDirectory)
        ? File::allFiles($authorizationDirectory)
        : [];

    expect($authorizationTests)->toBeEmpty();
});

test('application does not expose an API route surface', function () {
    $apiRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (IlluminateRoute $route): bool => str_starts_with($route->uri(), 'api/'));

    expect($apiRoutes)->toBeEmpty();
});
