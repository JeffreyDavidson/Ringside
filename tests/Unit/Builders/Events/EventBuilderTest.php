<?php

declare(strict_types=1);

use App\Models\Events\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('scheduled events can be retrieved', function () {
    // Clear any existing events to ensure test isolation
    Event::query()->forceDelete();

    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $scheduledEvents = Event::scheduled()->get();

    expect($scheduledEvents)
        ->toHaveCount(2)
        ->collectionHas($scheduledEvent)
        ->collectionHas($pastEvent);
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
        ->collectionHas($unscheduledEvent);
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
        ->collectionHas($pastEvent);
});
