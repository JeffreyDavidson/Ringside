<?php

declare(strict_types=1);

use App\Livewire\Concerns\ShowTableTrait;

test('show table behavior has one focused Livewire mount hook', function () {
    $reflection = new ReflectionClass(ShowTableTrait::class);
    $methods = collect($reflection->getMethods())
        ->filter(fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === ShowTableTrait::class)
        ->map(fn (ReflectionMethod $method): string => $method->getName())
        ->values()
        ->all();

    expect($reflection->isTrait())->toBeTrue()
        ->and($methods)->toBe(['mountShowTableTrait']);
});
