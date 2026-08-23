<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\Venue;

test('dated events can be ordered newest first with unscheduled events last', function () {
    $oldestEvent = Event::factory()->create(['date' => '2025-01-01 19:00:00']);
    $newestEvent = Event::factory()->create(['date' => '2025-03-01 19:00:00']);
    $middleEvent = Event::factory()->create(['date' => '2025-02-01 19:00:00']);
    $unscheduledEvent = Event::factory()->unscheduled()->create();

    $events = Event::query()
        ->latestDatedFirst()
        ->get();

    expect($events->modelKeys())->toBe([
        $newestEvent->id,
        $middleEvent->id,
        $oldestEvent->id,
        $unscheduledEvent->id,
    ]);
});

test('events can be queried by venue', function () {
    $venue = Venue::factory()->create();
    $otherVenue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);
    Event::factory()->create(['venue_id' => $otherVenue->id]);

    expect(Event::query()->forVenueId($venue->id)->pluck('id')->all())->toBe([$event->id]);
});

test('scheduled events can be retrieved', function () {
    // Clear any existing events to ensure test isolation
    Event::query()->forceDelete();

    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $scheduledEvents = Event::scheduled()->get();

    expect($scheduledEvents)
        ->toHaveCount(1)
        ->and($scheduledEvents->contains($scheduledEvent))->toBeTrue()
        ->and($scheduledEvents->contains($unscheduledEvent))->toBeFalse()
        ->and($scheduledEvents->contains($pastEvent))->toBeFalse();
});

test('unscheduled events can be retrieved', function () {
    // Clear any existing events to ensure test isolation
    Event::query()->forceDelete();

    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $unscheduledEvents = Event::unscheduled()->get();

    expect($unscheduledEvents)
        ->toHaveCount(1)
        ->and($unscheduledEvents->contains($unscheduledEvent))->toBeTrue();
});

test('past events can be retrieved', function () {
    // Clear any existing events to ensure test isolation
    Event::query()->forceDelete();

    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $pastEvents = Event::past()->get();

    expect($pastEvents)
        ->toHaveCount(1)
        ->and($pastEvents->contains($pastEvent))->toBeTrue();
});
