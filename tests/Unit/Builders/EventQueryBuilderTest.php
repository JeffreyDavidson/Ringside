<?php

use App\Models\Event;

test('scheduled events can be retrieved', function () {
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $scheduledEvents = Event::scheduled()->get();

    expect($scheduledEvents)
        ->toHaveCount(1)
        ->collectionHas($scheduledEvent);
});

test('unscheduled events can be retrieved', function () {
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $unscheduledEvents = Event::unscheduled()->get();

    expect($unscheduledEvents)
        ->toHaveCount(1)
        ->collectionHas($unscheduledEvent);
});

test('past events can be retrieved', function () {
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $pastEvents = Event::past()->get();

    expect($pastEvents)
        ->toHaveCount(1)
        ->collectionHas($pastEvent);
});
