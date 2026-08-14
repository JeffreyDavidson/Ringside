<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\Venue;

test('relates a venue to its events', function () {
    $venue = Venue::factory()->create();
    $events = Event::factory()->count(2)->atVenue($venue)->create();

    expect($venue->events)->toHaveCount(2)
        ->and($venue->events->modelKeys())->toEqualCanonicalizing($events->modelKeys());
});

test('allows a venue to exist without events', function () {
    $venue = Venue::factory()->create();

    expect($venue->events)->toBeEmpty()
        ->and($venue->previousEvents)->toBeEmpty()
        ->and($venue->futureEvents)->toBeEmpty();
});

test('filters previous events', function () {
    $venue = Venue::factory()->create();
    $pastEvent = Event::factory()->atVenue($venue)->create(['date' => today()->subDay()]);
    Event::factory()->atVenue($venue)->create(['date' => today()->addDay()]);
    Event::factory()->atVenue($venue)->create(['date' => null]);

    expect($venue->previousEvents)->toHaveCount(1)
        ->and($venue->previousEvents->firstOrFail()->is($pastEvent))->toBeTrue();
});

test('filters future events', function () {
    $venue = Venue::factory()->create();
    Event::factory()->atVenue($venue)->create(['date' => today()->subDay()]);
    $futureEvent = Event::factory()->atVenue($venue)->create(['date' => today()->addDay()]);
    Event::factory()->atVenue($venue)->create(['date' => null]);

    expect($venue->futureEvents)->toHaveCount(1)
        ->and($venue->futureEvents->firstOrFail()->is($futureEvent))->toBeTrue();
});

test('updates dated event relationships when an event date changes', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->atVenue($venue)->create(['date' => today()->addDay()]);

    expect($venue->previousEvents)->toBeEmpty();

    $event->update(['date' => today()->subDay()]);
    $venue->unsetRelation('previousEvents');

    expect($venue->previousEvents->firstOrFail()->is($event))->toBeTrue();
});

test('excludes soft-deleted events from venue relationships', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->atVenue($venue)->create();

    $event->delete();

    expect($venue->events()->count())->toBe(0);
});

test('associates an existing venue with a newly created event', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);

    expect($venue->events->firstOrFail()->is($event))->toBeTrue();
});
