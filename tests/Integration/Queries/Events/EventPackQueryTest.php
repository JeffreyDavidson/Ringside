<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\EventPack;
use App\Queries\Events\EventPackQuery;

test('reads the saved pack of the event in one statement', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $other = Event::factory()
        ->create();
    $pack = EventPack::factory()
        ->for($event)
        ->create();
    EventPack::factory()
        ->for($other)
        ->create();
    $query = resolve(EventPackQuery::class);

    // Act
    $found = $query->get($event);
    $statements = recordStatements(fn () => $query->get($event));

    // Assert
    expect($found?->is($pack))->toBeTrue()
        ->and($statements)->toHaveCount(1);
});

test('returns nothing while the event has no saved pack', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();

    // Act
    $found = resolve(EventPackQuery::class)->get($event);

    // Assert
    expect($found)->toBeNull();
});
