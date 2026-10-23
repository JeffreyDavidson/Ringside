<?php

declare(strict_types=1);

use App\Actions\Events\PersistEventPackAction;
use App\Models\Events\Event;
use App\Models\Events\EventPack;

test('persisting without a pack creates version one with the fingerprint and content', function (): void {
    // Arrange
    $event = Event::factory()->create();
    $content = ['timezone' => 'UTC', 'doors_at' => null, 'revision' => null, 'master' => null, 'notes' => 'First', 'follow_up' => null, 'matches' => [], 'segments' => []];

    // Act
    $pack = resolve(PersistEventPackAction::class)->handle($event, null, 0, 'card-a', $content);

    // Assert
    $stored = $event->pack()->sole();
    expect($pack->is($stored))->toBeTrue()
        ->and($stored->version)->toBe(1)
        ->and($stored->card_fingerprint)->toBe('card-a')
        ->and($stored->content)->toEqual($content);
});

test('persisting over a pack writes the next version in place', function (): void {
    // Arrange
    $existing = EventPack::factory()->create(['version' => 4, 'card_fingerprint' => 'card-a']);
    $content = [...$existing->content, 'notes' => 'Replaced'];

    // Act
    $pack = resolve(PersistEventPackAction::class)->handle($existing->event, $existing, 4, 'card-b', $content);

    // Assert
    $stored = EventPack::query()->sole();
    expect($pack->is($existing))->toBeTrue()
        ->and($stored->version)->toBe(5)
        ->and($stored->card_fingerprint)->toBe('card-b')
        ->and($stored->content)->toEqual($content);
});
