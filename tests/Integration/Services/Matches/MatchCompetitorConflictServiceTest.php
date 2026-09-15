<?php

declare(strict_types=1);

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Matches\MatchCompetitorConflictService;

test('it rejects a wrestler already booked at another event time', function (): void {
    $eventDate = now()->addWeek();
    $event = Event::factory()->create(['date' => $eventDate]);
    $wrestler = Wrestler::factory()->bookable()->create();

    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$wrestler])
        ->create();

    expect(fn () => resolve(MatchCompetitorConflictService::class)
        ->ensureWrestlersCanBeAssigned(collect([$event->id]), collect([$wrestler])))
        ->toThrow(SchedulingConflictException::class, "Wrestler [{$wrestler->name}] is already booked at this event time.");
});

test('it rejects a tag team already booked at another event time', function (): void {
    $eventDate = now()->addWeek();
    $event = Event::factory()->create(['date' => $eventDate]);
    $tagTeam = TagTeam::factory()->bookable()->create();

    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$tagTeam])
        ->create();

    expect(fn () => resolve(MatchCompetitorConflictService::class)
        ->ensureTagTeamsCanBeAssigned(collect([$event->id]), collect([$tagTeam])))
        ->toThrow(SchedulingConflictException::class, "Tag team [{$tagTeam->name}] is already booked at this event time.");
});
