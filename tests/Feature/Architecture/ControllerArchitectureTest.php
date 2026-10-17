<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Events\EventController;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use PhpParser\Error;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

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

test('domain resource controllers use singular resource names', function () {
    foreach (applicationControllers() as $controller) {
        if (str_starts_with($controller, 'App\\Http\\Controllers\\Auth\\')) {
            continue;
        }

        $methods = declaredPublicMethods($controller);

        if ($methods === ['__invoke']) {
            continue;
        }

        $resource = Str::beforeLast(class_basename($controller), 'Controller');

        expect($resource)->toBe(Str::studly(Str::singular($resource)));
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

test('controller endpoint tests visit named routes', function () {
    foreach (File::allFiles(base_path('tests/Feature/Http/Controllers')) as $file) {
        $source = File::get($file->getPathname());

        expect($source)->not->toContain('action([');
    }
});

test('application does not expose an API route surface', function () {
    $apiRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (IlluminateRoute $route): bool => str_starts_with($route->uri(), 'api/'));

    expect($apiRoutes)->toBeEmpty();
});

/**
 * A matching file must declare a test with a body, not just a filename or pending test.
 */
function declaresExecutableControllerTest(string $source): bool
{
    try {
        $nodes = (new ParserFactory)->createForNewestSupportedVersion()->parse($source) ?? [];
    } catch (Error) {
        return false;
    }

    $finder = new NodeFinder;

    foreach ($finder->findInstanceOf($nodes, FuncCall::class) as $call) {
        if (! $call->name instanceof Name || ! in_array($call->name->getLast(), ['test', 'it'], true)) {
            continue;
        }

        $callback = $call->args[1]->value ?? null;
        if ($callback instanceof ArrowFunction) {
            return true;
        }
        if ($callback instanceof Closure && $callback->stmts !== []) {
            return true;
        }
    }

    return false;
}

/**
 * @param  list<class-string>  $controllers
 * @return list<string>
 */
function controllerTestProblems(array $controllers, string $testDirectory): array
{
    $problems = [];

    foreach ($controllers as $controller) {
        if (new ReflectionClass($controller)->isAbstract()) {
            continue;
        }

        $relative = str_replace('\\', '/', Str::after($controller, 'App\\Http\\Controllers\\')).'Test.php';
        $path = $testDirectory.'/'.$relative;
        if (! is_file($path)) {
            $problems[] = "{$relative}: missing matching file";

            continue;
        }

        if (! declaresExecutableControllerTest(File::get($path))) {
            $problems[] = "{$relative}: missing executable Pest test";
        }
    }

    sort($problems);

    return $problems;
}

test('every concrete controller has an executable test at its full mirrored Feature path', function (): void {
    // Act
    $problems = controllerTestProblems(applicationControllers(), base_path('tests/Feature/Http/Controllers'));

    // Assert
    expect($problems)->toBeEmpty();
});

test('controller matching rejects missing or misplaced files and exempts abstract controllers', function (string $case, array $expected): void {
    // Arrange
    $directory = sys_get_temp_dir().'/ringside-controller-tests-'.Str::uuid();
    File::ensureDirectoryExists($directory.'/WrongFolder');
    $controller = $case === 'abstract' ? Controller::class : DashboardController::class;
    if ($case === 'wrong path') {
        File::put($directory.'/WrongFolder/DashboardControllerTest.php', '<?php test("dashboard", fn () => expect(true)->toBeTrue());');
    }

    try {
        // Act
        $problems = controllerTestProblems([$controller], $directory);

        // Assert
        expect($problems)->toBe($expected);
    } finally {
        File::deleteDirectory($directory);
    }
})->with([
    'missing' => ['missing', ['DashboardControllerTest.php: missing matching file']],
    'same basename in another directory' => ['wrong path', ['DashboardControllerTest.php: missing matching file']],
    'abstract base' => ['abstract', []],
]);

test('controller matching accepts only a matching file with an executable test body', function (string $source, bool $valid): void {
    // Arrange
    $directory = sys_get_temp_dir().'/ringside-controller-tests-'.Str::uuid();
    File::ensureDirectoryExists($directory.'/Events');
    File::put($directory.'/Events/EventControllerTest.php', $source);

    try {
        // Act
        $problems = controllerTestProblems([EventController::class], $directory);

        // Assert
        expect($problems)->toBe($valid ? [] : ['Events/EventControllerTest.php: missing executable Pest test']);
    } finally {
        File::deleteDirectory($directory);
    }
})->with([
    'empty' => ['', false],
    'comment only' => ['<?php // test("looks real", fn () => true);', false],
    'pending test' => ['<?php test("pending");', false],
    'empty body' => ['<?php test("empty", function () {});', false],
    'test closure' => ['<?php test("works", function () { expect(true)->toBeTrue(); });', true],
    'it arrow' => ['<?php it("works", fn () => expect(true)->toBeTrue());', true],
    'describe and dataset' => ['<?php describe("index", function () { test("works", fn ($value) => expect($value)->toBeTrue())->with([true]); });', true],
]);
