<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Services\Matches\MatchAssignmentConflictService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

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

test('it locks the same-date events in ascending id order including the event itself', function (): void {
    $eventDate = now()->addWeek();
    $first = Event::factory()->create(['date' => $eventDate]);
    $second = Event::factory()->create(['date' => $eventDate]);
    $third = Event::factory()->create(['date' => $eventDate]);
    Event::factory()->create(['date' => $eventDate->copy()->addHour()]);

    $eventIds = resolve(MatchAssignmentConflictService::class)->lockEventSet($second->id);

    expect($eventIds->all())->toBe([$first->id, $second->id, $third->id]);
});

test('it locks only the event itself when it is unscheduled', function (): void {
    $event = Event::factory()->unscheduled()->create();
    Event::factory()->unscheduled()->create();

    $eventIds = resolve(MatchAssignmentConflictService::class)->lockEventSet($event->id);

    expect($eventIds->all())->toBe([$event->id]);
});

test('it ignores soft deleted events in the locked set', function (): void {
    $eventDate = now()->addWeek();
    $event = Event::factory()->create(['date' => $eventDate]);
    Event::factory()->create(['date' => $eventDate])->delete();

    $eventIds = resolve(MatchAssignmentConflictService::class)->lockEventSet($event->id);

    expect($eventIds->all())->toBe([$event->id]);
});

test('it rejects locking the set of a missing or soft deleted event', function (): void {
    $deletedEvent = Event::factory()->create();
    $deletedEvent->delete();
    $service = resolve(MatchAssignmentConflictService::class);

    expect(fn () => $service->lockEventSet($deletedEvent->id))->toThrow(ModelNotFoundException::class)
        ->and(fn () => $service->lockEventSet(PHP_INT_MAX))->toThrow(ModelNotFoundException::class);
});

test('it locks the set again when the event was rescheduled after its date was read', function (): void {
    $originalDate = now()->addWeek();
    $newDate = now()->addWeeks(2);
    $event = Event::factory()->create(['date' => $originalDate]);
    $eventAtNewDate = Event::factory()->create(['date' => $newDate]);
    $setQueries = 0;
    $rescheduled = false;
    DB::listen(function (QueryExecuted $query) use (&$setQueries, &$rescheduled, $event, $newDate): void {
        if (str_contains($query->sql, 'order by "id"')) {
            $setQueries++;
        }

        if (! $rescheduled && str_contains($query->sql, 'select "id", "date" from "events"')) {
            $rescheduled = true;
            DB::table('events')->where('id', $event->id)->update(['date' => $newDate]);
        }
    });

    $eventIds = resolve(MatchAssignmentConflictService::class)->lockEventSet($event->id);

    expect($eventIds->all())->toBe([$event->id, $eventAtNewDate->id])
        ->and($setQueries)->toBe(2);
});

test('it locks the match after the event set and returns the locked match', function (): void {
    $event = Event::factory()->create(['date' => now()->addWeek()]);
    $match = EventMatch::factory()->forEvent($event)->create();

    $lockedMatch = resolve(MatchAssignmentConflictService::class)->lockMatchWithEventSet($match);

    expect($lockedMatch->is($match))->toBeTrue();
});

test('it locks a soft deleted match after its event set', function (): void {
    $match = EventMatch::factory()->create();
    $match->delete();

    $lockedMatch = resolve(MatchAssignmentConflictService::class)->lockMatchWithEventSet($match);

    expect($lockedMatch->is($match))->toBeTrue();
});

test('it locks the new event set when the match moved events after its event was read', function (): void {
    $originalEvent = Event::factory()->create(['date' => now()->addWeek()]);
    $newEvent = Event::factory()->create(['date' => now()->addWeeks(2)]);
    $match = EventMatch::factory()->forEvent($originalEvent)->create();
    $setQueries = 0;
    $moved = false;
    DB::listen(function (QueryExecuted $query) use (&$setQueries, &$moved, $match, $newEvent): void {
        if (str_contains($query->sql, 'order by "id"')) {
            $setQueries++;
        }

        if (! $moved && str_contains($query->sql, 'select "id", "event_id" from "events_matches"')) {
            $moved = true;
            DB::table('events_matches')->where('id', $match->id)->update(['event_id' => $newEvent->id]);
        }
    });

    $lockedMatch = resolve(MatchAssignmentConflictService::class)->lockMatchWithEventSet($match);

    expect($lockedMatch->event_id)->toBe($newEvent->id)
        ->and($setQueries)->toBe(2);
});

test('it rejects locking a match that does not exist', function (): void {
    $match = EventMatch::factory()->create();
    $match->forceDelete();

    expect(fn () => resolve(MatchAssignmentConflictService::class)->lockMatchWithEventSet($match))
        ->toThrow(ModelNotFoundException::class);
});
