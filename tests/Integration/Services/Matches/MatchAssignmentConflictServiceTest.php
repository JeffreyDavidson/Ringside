<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Services\Matches\MatchAssignmentConflictService;

test('it locks the event and same-time events for assignment checks', function (): void {
    $eventDate = now()->addWeek();
    $event = Event::factory()->create(['date' => $eventDate]);
    $sameTimeEvent = Event::factory()->create(['date' => $eventDate]);
    $differentTimeEvent = Event::factory()->create(['date' => $eventDate->copy()->addHour()]);
    $match = EventMatch::factory()->forEvent($event)->create();

    $eventIds = resolve(MatchAssignmentConflictService::class)->lockConflictingEventIds($match);

    expect($eventIds->all())
        ->toContain($event->id, $sameTimeEvent->id)
        ->not->toContain($differentTimeEvent->id);
});

test('it returns without checking assignments when an event has no target date', function (): void {
    $event = Event::factory()->unscheduled()->create();

    expect(fn () => resolve(MatchAssignmentConflictService::class)
        ->ensureEventCanBeRescheduled($event, null))
        ->not->toThrow(Throwable::class);
});
