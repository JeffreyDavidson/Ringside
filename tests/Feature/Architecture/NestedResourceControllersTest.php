<?php

declare(strict_types=1);

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * @return array{parent: non-empty-string, nested: non-empty-string}|null
 */
function nestedResourceSegments(IlluminateRoute $route): ?array
{
    $matches = [];

    if (preg_match('#(?:^|/)([^/{]+)/{[^}]+}/([^/{]+)(?:/{[^}]+})?$#', $route->uri(), $matches) !== 1) {
        return null;
    }

    return ['parent' => $matches[1], 'nested' => $matches[2]];
}

/**
 * @return array{controller: class-string, method: non-empty-string}
 */
function controllerAction(IlluminateRoute $route): array
{
    $action = $route->getActionName();

    if (! str_contains($action, '@')) {
        throw new RuntimeException("Nested resource route [{$route->uri()}] must use a controller method.");
    }

    [$controller, $method] = explode('@', $action, 2);

    if (! class_exists($controller) || $method === '') {
        throw new RuntimeException("Nested resource route [{$route->uri()}] has an invalid controller action [{$action}].");
    }

    return ['controller' => $controller, 'method' => $method];
}

test('nested resources use resourceful controllers', function () {
    $resourceMethods = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
    $nestedRouteCount = 0;

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $segments = nestedResourceSegments($route);

        if ($segments === null) {
            continue;
        }

        $nestedRouteCount++;
        ['controller' => $controller, 'method' => $method] = controllerAction($route);
        $parentResource = Str::studly(Str::singular($segments['parent']));
        $nestedResource = Str::studly($segments['nested']);
        $expectedController = "{$parentResource}{$nestedResource}Controller";
        $expectedRouteName = Str::of($segments['parent'])
            ->append('.', $segments['nested'], ".{$method}")
            ->toString();

        expect(class_basename($controller))
            ->toBe($expectedController)
            ->and($method)
            ->toBeIn($resourceMethods)
            ->and($route->getName())
            ->toBe($expectedRouteName);
    }

    expect($nestedRouteCount)->toBeGreaterThan(0);
});
