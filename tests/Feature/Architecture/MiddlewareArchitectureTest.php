<?php

declare(strict_types=1);

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;

test('the application uses Laravel security middleware', function () {
    $router = resolve(Router::class);

    expect($router->getMiddlewareGroups()['web'])
        ->toContain(PreventRequestForgery::class)
        ->and($router->getMiddleware()['signed'])
        ->toBe(ValidateSignature::class);
});

/** @return list<string> */
function applicationMiddlewareClasses(string $directory): array
{
    return array_values(collect(File::allFiles($directory))
        ->map(fn (SplFileInfo $file): string => 'App\\Http\\Middleware\\'.str_replace(
            DIRECTORY_SEPARATOR,
            '\\',
            Str::beforeLast($file->getRelativePathname(), '.php'),
        ))
        ->sort()
        ->values()
        ->all());
}

/** A declaration must have a body and not belong to a skipped test or group. */
function declaresExecutableMiddlewareTest(string $source): bool
{
    try {
        $nodes = (new ParserFactory)->createForNewestSupportedVersion()->parse($source) ?? [];
    } catch (Error) {
        return false;
    }

    $nodes = new NodeTraverser(new ParentConnectingVisitor)->traverse($nodes);
    $finder = new NodeFinder;

    foreach ($finder->findInstanceOf($nodes, FuncCall::class) as $call) {
        if (! $call->name instanceof Name || ! in_array($call->name->getLast(), ['test', 'it'], true)) {
            continue;
        }

        $callback = $call->args[1]->value ?? null;
        if (! $callback instanceof ArrowFunction
            && (! $callback instanceof Closure || $callback->stmts === [])) {
            continue;
        }

        $skipped = false;
        for ($parent = $call->getAttribute('parent'); $parent instanceof Node; $parent = $parent->getAttribute('parent')) {
            if ($parent instanceof MethodCall
                && $parent->name instanceof Identifier
                && in_array($parent->name->toString(), ['skip', 'todo'], true)) {
                $skipped = true;
                break;
            }
        }

        if (! $skipped) {
            return true;
        }
    }

    return false;
}

/**
 * @param  list<string>  $middlewareClasses
 * @return list<string>
 */
function middlewareTestProblems(array $middlewareClasses, string $testDirectory): array
{
    $problems = [];

    foreach ($middlewareClasses as $middleware) {
        if (! class_exists($middleware)) {
            $problems[] = "{$middleware}: missing application class";

            continue;
        }

        if (new ReflectionClass($middleware)->isAbstract()) {
            continue;
        }

        $relative = str_replace('\\', '/', Str::after($middleware, 'App\\Http\\Middleware\\')).'Test.php';
        $path = $testDirectory.'/'.$relative;
        if (! is_file($path)) {
            $problems[] = "{$relative}: missing matching file";

            continue;
        }

        if (! declaresExecutableMiddlewareTest(File::get($path))) {
            $problems[] = "{$relative}: missing executable Pest test";
        }
    }

    sort($problems);

    return $problems;
}

test('every concrete application middleware has an executable test at its full mirrored Feature path', function (): void {
    // Arrange
    $middleware = applicationMiddlewareClasses(app_path('Http/Middleware'));

    // Act
    $problems = middlewareTestProblems($middleware, base_path('tests/Feature/Http/Middleware'));

    // Assert
    expect($middleware)->not->toBeEmpty()
        ->and($problems)->toBeEmpty();
});

test('middleware discovery preserves nested paths and reports unloadable classes', function (): void {
    // Arrange
    $directory = sys_get_temp_dir().'/ringside-middleware-'.Str::uuid();
    File::ensureDirectoryExists($directory.'/Nested');
    File::put($directory.'/Nested/UnknownMiddleware.php', '<?php');

    try {
        // Act
        $classes = applicationMiddlewareClasses($directory);
        $problems = middlewareTestProblems($classes, $directory);

        // Assert
        expect($classes)->toBe(['App\\Http\\Middleware\\Nested\\UnknownMiddleware'])
            ->and($problems)->toBe(['App\\Http\\Middleware\\Nested\\UnknownMiddleware: missing application class']);
    } finally {
        File::deleteDirectory($directory);
    }
});

test('middleware matching rejects missing and misplaced files', function (string $sourcePath): void {
    // Arrange
    $directory = sys_get_temp_dir().'/ringside-middleware-'.Str::uuid();
    File::ensureDirectoryExists($directory.'/'.$sourcePath);
    File::put($directory.'/'.$sourcePath.'/EnsureUserIsActiveTest.php', '<?php test("probe", fn () => expect(true)->toBeTrue());');

    try {
        // Act
        $problems = middlewareTestProblems([EnsureUserIsActive::class], $directory);

        // Assert
        expect($problems)->toBe(['EnsureUserIsActiveTest.php: missing matching file']);
    } finally {
        File::deleteDirectory($directory);
    }
})->with(['wrong directory' => 'WrongFolder', 'wrong nested directory' => 'Nested/WrongFolder']);

test('middleware matching exempts a genuine abstract base', function (): void {
    // Act
    $problems = middlewareTestProblems([Controller::class], sys_get_temp_dir());

    // Assert
    expect($problems)->toBeEmpty();
});

test('middleware matching rejects placeholder files', function (string $source): void {
    // Arrange
    $directory = sys_get_temp_dir().'/ringside-middleware-'.Str::uuid();
    File::ensureDirectoryExists($directory);
    File::put($directory.'/EnsureUserIsActiveTest.php', $source);

    try {
        // Act
        $problems = middlewareTestProblems([EnsureUserIsActive::class], $directory);

        // Assert
        expect($problems)->toBe(['EnsureUserIsActiveTest.php: missing executable Pest test']);
    } finally {
        File::deleteDirectory($directory);
    }
})->with([
    'empty' => '',
    'comment only' => '<?php // test("probe", fn () => true);',
    'malformed' => '<?php test(',
    'pending' => '<?php test("probe");',
    'empty body' => '<?php test("probe", function () {});',
    'skipped test' => '<?php test("probe", fn () => true)->skip();',
    'todo test' => '<?php test("probe", fn () => true)->todo();',
    'skipped group' => '<?php describe("probe", function () { test("probe", fn () => true); })->skip();',
]);

test('middleware matching accepts executable Pest declarations', function (string $source): void {
    // Act
    $executable = declaresExecutableMiddlewareTest($source);

    // Assert
    expect($executable)->toBeTrue();
})->with([
    'closure' => '<?php test("probe", function () { expect(true)->toBeTrue(); });',
    'arrow' => '<?php it("probe", fn () => expect(true)->toBeTrue());',
    'group and dataset' => '<?php describe("probe", function () { test("probe", fn ($value) => expect($value)->toBeTrue())->with([true]); });',
    'skipped sibling' => '<?php test("skipped", fn () => true)->skip(); test("probe", fn () => expect(true)->toBeTrue());',
]);

test('middleware matching requires and accepts the full nested test path', function (): void {
    // Arrange
    $middleware = 'App\\Http\\Middleware\\MatchingFixture\\EnsureUserIsActive';
    class_alias(EnsureUserIsActive::class, $middleware);
    $directory = sys_get_temp_dir().'/ringside-middleware-'.Str::uuid();
    File::ensureDirectoryExists($directory.'/MatchingFixture');
    File::put($directory.'/EnsureUserIsActiveTest.php', '<?php test("probe", fn () => expect(true)->toBeTrue());');

    try {
        $misplaced = middlewareTestProblems([$middleware], $directory);
        File::move($directory.'/EnsureUserIsActiveTest.php', $directory.'/MatchingFixture/EnsureUserIsActiveTest.php');

        // Act
        $problems = middlewareTestProblems([$middleware], $directory);

        // Assert
        expect($misplaced)->toBe(['MatchingFixture/EnsureUserIsActiveTest.php: missing matching file'])
            ->and($problems)->toBeEmpty();
    } finally {
        File::deleteDirectory($directory);
    }
});
