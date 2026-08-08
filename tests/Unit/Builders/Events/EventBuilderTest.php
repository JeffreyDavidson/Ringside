<?php

declare(strict_types=1);

use App\Models\Events\Event;

test('scheduled events can be retrieved', function () {
    // Clear any existing events to ensure test isolation
    Event::query()->forceDelete();

    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $scheduledEvents = Event::scheduled()->get();

    expect($scheduledEvents)
        ->toHaveCount(2)
        ->and($scheduledEvents->contains($scheduledEvent))->toBeTrue()
        ->and($scheduledEvents->contains($pastEvent))->toBeTrue();
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
