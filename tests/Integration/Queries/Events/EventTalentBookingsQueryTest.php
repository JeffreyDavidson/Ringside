<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use App\Queries\Events\EventTalentBookingsQuery;

test('reads the bookings of one event oldest first in one statement', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();
    $first = EventTalentBooking::factory()
        ->for($event)
        ->create();
    $second = EventTalentBooking::factory()
        ->for($event)
        ->create();
    EventTalentBooking::factory()
        ->create();
    $query = resolve(EventTalentBookingsQuery::class);

    // Act
    $bookings = $query->get($event);
    $statements = recordStatements(fn () => $query->get($event));

    // Assert
    expect($bookings->modelKeys())->toBe([$first->id, $second->id])
        ->and($statements)->toHaveCount(1);
});
