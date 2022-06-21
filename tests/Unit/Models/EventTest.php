<?php

use App\Models\Event;
use App\Models\Venue;

test('an event has a name', function () {
    $event = Event::factory()->create(['name' => 'Example Event Name']);

    expect($event)->name->toBe('Example Event Name');
});

test('an event has a date', function () {
    $event = Event::factory()->create(['date' => '2022-10-11 07:00:00']);

    expect($event)->date->toBe('2022-10-11 07:00:00');
});

test('an event date can be formatted', function () {
    $event = Event::factory()->create(['date' => '2020-03-05 00:00:00']);

    expect($event)->present()->date->toEqual('March 5, 2020');
});

test('an event takes place at a venue', function () {
    $event = Event::factory()->create(['venue_id' => $venue = Venue::factory()->create()->id]);

    expect($event)->venue_id->toBe($venue->id);
});

test('an event with a date in the future is scheduled', function () {
    $event = Event::factory()->create(['date' => Carbon::now()->addDay()->toDateTimeString()]);

    expect($event->isScheduled())->toBeTrue();
});

test('an event without a date is unscheduled', function () {
    $event = Event::factory()->create(['date' => null]);

    expect($event->isUnscheduled())->toBeTrue();
});

test('an event with a date in the past has past', function () {
    $event = Event::factory()->create(['date' => Carbon::now()->subDay()->toDateTimeString()]);

    expect($event->isPast())->toBeTrue();
});

test('scheduled events can be retrieved', function () {
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $scheduledEvents = Event::scheduled()->get();

    expect($scheduledEvents)
        ->toHaveCount(1)
        ->assertCollectionHas($scheduledEvent);
});

test('unscheduled events can be retrieved', function () {
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $unscheduledEvents = Event::unscheduled()->get();

    expect($unscheduledEvents)
        ->toHaveCount(1)
        ->assertCollectionHas($unscheduledEvent);
});

test('past events can be retrieved', function () {
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastEvent = Event::factory()->past()->create();

    $pastEvents = Event::past()->get();

    expect($pastEvents)
        ->toHaveCount(1)
        ->assertCollectionHas($pastEvent);
});
