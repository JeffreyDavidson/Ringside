<?php

declare(strict_types=1);

use App\Data\Wrestlers\WrestlerData;

test('carries data only', function () {
    // Arrange
    $class = new ReflectionClass(WrestlerData::class);

    // Act
    $methods = array_map(fn (ReflectionMethod $method): string => $method->getName(), $class->getMethods());

    // Assert
    expect($methods)->toBe(['__construct'])
        ->and($class->isReadOnly())->toBeTrue();
});
