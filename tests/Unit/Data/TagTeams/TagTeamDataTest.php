<?php

declare(strict_types=1);

use App\Data\TagTeams\TagTeamData;

test('carries data only', function () {
    // Arrange
    $class = new ReflectionClass(TagTeamData::class);

    // Act
    $methods = array_map(fn (ReflectionMethod $method): string => $method->getName(), $class->getMethods());

    // Assert
    expect($methods)->toBe(['__construct'])
        ->and($class->isReadOnly())->toBeTrue();
});
