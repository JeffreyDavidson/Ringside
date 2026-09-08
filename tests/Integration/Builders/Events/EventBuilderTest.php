<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\Venue;

test('dated events can be ordered newest first with unscheduled events last', function () {
    // Arrange
    $oldestEvent = Event::factory()->create(['date' => '2025-01-01 19:00:00']);
    $newestEvent = Event::factory()->create(['date' => '2025-03-01 19:00:00']);
    $middleEvent = Event::factory()->create(['date' => '2025-02-01 19:00:00']);
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    Event::factory()->trashed()->create(['date' => '2025-04-01 19:00:00']);
    Event::factory()->unscheduled()->trashed()->create();

    // Act
    $query = Event::query();
    $query->latestDatedFirst();
    $events = $query->get();

    // Assert
    expect($events->modelKeys())->toBe([
        $newestEvent->id,
        $middleEvent->id,
        $oldestEvent->id,
        $unscheduledEvent->id,
    ]);
});

test('events can be queried by venue', function () {
    // Arrange
    $venue = Venue::factory()->create();
    $otherVenue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);
    Event::factory()->create(['venue_id' => $otherVenue->id]);
    Event::factory()->create(['venue_id' => null]);
    Event::factory()->trashed()->create(['venue_id' => $venue->id]);

    // Act
    $query = Event::query();
    $query->forVenueId($venue->id);
    $events = $query->get();

    // Assert
    expect($events->modelKeys())->toBe([$event->id]);
});

test('scheduled events can be retrieved', function () {
    // Arrange
    $scheduledEvent = Event::factory()->scheduled()->create();
    Event::factory()->unscheduled()->create();
    Event::factory()->past()->create();
    Event::factory()->scheduled()->trashed()->create();

    // Act
    $query = Event::query();
    $query->scheduled();
    $scheduledEvents = $query->get();

    // Assert
    expect($scheduledEvents->modelKeys())->toBe([$scheduledEvent->id]);
});

test('unscheduled events can be retrieved', function () {
    // Arrange
    Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    Event::factory()->past()->create();
    Event::factory()->unscheduled()->trashed()->create();

    // Act
    $query = Event::query();
    $query->unscheduled();
    $unscheduledEvents = $query->get();

    // Assert
    expect($unscheduledEvents->modelKeys())->toBe([$unscheduledEvent->id]);
});

test('past events can be retrieved', function () {
    // Arrange
    Event::factory()->scheduled()->create();
    Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();
    Event::factory()->past()->trashed()->create();

    // Act
    $query = Event::query();
    $query->past();
    $pastEvents = $query->get();

    // Assert
    expect($pastEvents->modelKeys())->toBe([$pastEvent->id]);
});
