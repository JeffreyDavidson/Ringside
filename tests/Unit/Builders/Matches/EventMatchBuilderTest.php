<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;

it('retrieves matches for past events and eager loads their events', function () {
    $pastEvent = Event::factory()->past()->create();
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastMatch = EventMatch::factory()->forEvent($pastEvent)->create();
    EventMatch::factory()->forEvent($scheduledEvent)->create();
    EventMatch::factory()->forEvent($unscheduledEvent)->create();

    $matches = EventMatch::query()->forPastEvents()->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->firstOrFail()->is($pastMatch))->toBeTrue()
        ->and($matches->firstOrFail()->relationLoaded('event'))->toBeTrue();
});
