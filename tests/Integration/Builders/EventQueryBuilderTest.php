<?php

declare(strict_types=1);

use App\Models\Events\Event;

test('scheduled events can be retrieved', function () {
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $scheduledEvents = Event::scheduled()->get();

    expect($scheduledEvents->pluck('id'))->toContain($scheduledEvent->id);
});

test('unscheduled events can be retrieved', function () {
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $unscheduledEvents = Event::unscheduled()->get();

    expect($unscheduledEvents->pluck('id'))->toContain($unscheduledEvent->id);
});

test('past events can be retrieved', function () {
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $pastEvents = Event::past()->get();

    expect($pastEvents->pluck('id'))->toContain($pastEvent->id);
});
