<?php

declare(strict_types=1);

use App\Models\Events\EventPack;

test('mass assignment accepts the pack content and ignores ownership, version and fingerprint', function (): void {
    // Arrange
    $pack = new EventPack;

    // Act
    $pack->fill([
        'content' => ['timezone' => 'UTC'],
        'event_id' => 99,
        'version' => 5,
        'card_fingerprint' => 'forged',
    ]);

    // Assert
    expect(array_keys($pack->getAttributes()))->toBe(['content'])
        ->and($pack->content)->toBe(['timezone' => 'UTC']);
});
