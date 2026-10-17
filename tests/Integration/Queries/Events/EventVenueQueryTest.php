<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Queries\Events\EventVenueQuery;

test('reads the venue of the event in one statement', function (): void {
    // Arrange
    $venue = Venue::factory()
        ->create();
    $event = Event::factory()
        ->create(['venue_id' => $venue->id]);
    $query = resolve(EventVenueQuery::class);

    // Act
    $found = $query->get($event);
    $statements = recordStatements(fn () => $query->get($event));

    // Assert
    expect($found?->is($venue))->toBeTrue()
        ->and($statements)->toHaveCount(1);
});

test('keeps reading a venue that was deleted after it was booked', function (): void {
    // Arrange
    $venue = Venue::factory()
        ->create();
    $event = Event::factory()
        ->create(['venue_id' => $venue->id]);
    $venue->delete();

    // Act
    $found = resolve(EventVenueQuery::class)->get($event->refresh());

    // Assert
    expect($found?->is($venue))->toBeTrue()
        ->and($found?->trashed())->toBeTrue();
});

test('reads nothing for an event without a venue', function (): void {
    // Arrange
    $event = Event::factory()
        ->create(['venue_id' => null]);

    // Act
    $found = resolve(EventVenueQuery::class)->get($event);

    // Assert
    expect($found)->toBeNull();
});
