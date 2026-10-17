<?php

declare(strict_types=1);

use App\Data\Stables\StableMembershipData;

/*
 * Data objects carry data (.ai/rules/data.md): readonly, a promoted constructor, static named constructors (static methods
 * that return static, self or the class) and the named value-level checks below. No other methods, no now() and no queries.
 */
$dataDirectory = __DIR__.'/../../../app/Data/';

$dataClasses = array_map(
    static fn (string $file): string => 'App\\Data\\'.str_replace(['/', '.php'], ['\\', ''], mb_substr($file, mb_strlen($dataDirectory))),
    glob($dataDirectory.'*/*.php') ?: [],
);

dataset('data classes', array_combine($dataClasses, array_map(static fn (string $class): array => [$class], $dataClasses)));

test('data objects are readonly', function (string $class) {
    /** @var class-string $class */
    // Arrange
    $reflection = new ReflectionClass($class);

    // Act
    $isReadonly = $reflection->isReadOnly();

    // Assert
    expect($isReadonly)->toBeTrue();
})->with('data classes');

test('data objects only have a constructor, static named constructors and allowed checks', function (string $class) {
    /** @var class-string $class */
    // Arrange
    // Value-level checks on a data object's own fields that data.md explicitly allows.
    $allowedChecks = [StableMembershipData::class => ['isEmpty', 'isNotEmpty', 'getTotalMemberCount']];
    $reflection = new ReflectionClass($class);

    // Act
    $unexpectedMethods = collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
        ->reject(fn (ReflectionMethod $method): bool => $method->isConstructor()
            || ($method->isStatic() && in_array((string) $method->getReturnType(), ['static', 'self', $class], true))
            || in_array($method->getName(), $allowedChecks[$class] ?? [], true))
        ->map(fn (ReflectionMethod $method): string => $method->getName())
        ->values()
        ->all();

    // Assert
    expect($unexpectedMethods)->toBeEmpty();
})->with('data classes');

test('data objects do not read the clock', function (string $class) {
    /** @var class-string $class */
    // Arrange
    $source = (string) file_get_contents((string) new ReflectionClass($class)->getFileName());

    // Act
    $readsClock = preg_match('/\bnow\(|Carbon::now|Date::now/', $source) === 1;

    // Assert
    expect($readsClock)->toBeFalse();
})->with('data classes');
