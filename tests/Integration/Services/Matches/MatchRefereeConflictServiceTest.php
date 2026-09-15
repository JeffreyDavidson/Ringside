<?php

declare(strict_types=1);

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Services\Matches\MatchRefereeConflictService;

test('it rejects a referee assigned to another event at the same time', function (): void {
    $eventDate = now()->addWeek();
    $event = Event::factory()->create(['date' => $eventDate]);
    $referee = Referee::factory()->bookable()->create();
    $match = EventMatch::factory()->forEvent($event)->create();
    $match->referees()->attach($referee);
    $referee->refresh();

    expect(fn () => resolve(MatchRefereeConflictService::class)
        ->ensureCanBeAssigned(0, collect([$event->id]), collect([$referee])))
        ->toThrow(SchedulingConflictException::class, "Referee [{$referee->full_name}] is already assigned to another event at this time.");
});

test('it permits a referee to officiate another match on the same event card', function (): void {
    $event = Event::factory()->scheduled()->create();
    $referee = Referee::factory()->bookable()->create();

    expect(fn () => resolve(MatchRefereeConflictService::class)
        ->ensureCanBeAssigned($event->id, collect([$event->id]), collect([$referee])))
        ->not->toThrow(Throwable::class);
});
