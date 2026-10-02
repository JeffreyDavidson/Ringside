<?php

declare(strict_types=1);

use function Pest\Laravel\get;

test('the removed tenancy asset route does not exist', function (): void {
    // Act
    $response = get('/tenancy/assets/x');

    // Assert
    $response->assertNotFound();
});
