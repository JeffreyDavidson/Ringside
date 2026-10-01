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

test('it rejects a wrestler booked individually when their tag team is entered at the same event time', function (bool $sameCard) {
    $date = now()->addWeek();
    $bookedEvent = Event::factory()->create(['date' => $date]);
    $checkedEvent = $sameCard ? $bookedEvent : Event::factory()->create(['date' => $date]);
    $member = Wrestler::factory()->bookable()->create();
    $partner = Wrestler::factory()->bookable()->create();
    $tagTeam = TagTeam::factory()->bookable()->withCurrentWrestlers([$member, $partner])->create();

    EventMatch::factory()->forEvent($bookedEvent)->withCompetitors([$tagTeam])->create();

    expect(fn () => resolve(MatchCompetitorConflictService::class)
        ->ensureWrestlersCanBeAssigned(collect([$bookedEvent->id, $checkedEvent->id]), collect([$member])))
        ->toThrow(SchedulingConflictException::class, "Wrestler [{$member->name}] is already booked at this event time.");
})->with([
    'same card' => [true],
    'another event at the same instant' => [false],
]);

test('it rejects a tag team when one of its members is booked individually at the same event time', function (bool $sameCard) {
    $date = now()->addWeek();
    $bookedEvent = Event::factory()->create(['date' => $date]);
    $checkedEvent = $sameCard ? $bookedEvent : Event::factory()->create(['date' => $date]);
    $member = Wrestler::factory()->bookable()->create();
    $partner = Wrestler::factory()->bookable()->create();
    $tagTeam = TagTeam::factory()->bookable()->withCurrentWrestlers([$member, $partner])->create();

    EventMatch::factory()->forEvent($bookedEvent)->withCompetitors([$partner])->create();

    expect(fn () => resolve(MatchCompetitorConflictService::class)
        ->ensureTagTeamsCanBeAssigned(collect([$bookedEvent->id, $checkedEvent->id]), collect([$tagTeam])))
        ->toThrow(SchedulingConflictException::class, "Wrestler [{$partner->name}] is already booked at this event time.");
})->with([
    'same card' => [true],
    'another event at the same instant' => [false],
]);

test('it ignores former tag team members when checking individual bookings', function () {
    $event = Event::factory()->create(['date' => now()->addWeek()]);
    $former = Wrestler::factory()->bookable()->create();
    $current = Wrestler::factory()->bookable()->create();
    $tagTeam = TagTeam::factory()->bookable()->withCurrentWrestlers([$current])->create();
    $tagTeam->wrestlers()->attach($former, ['joined_at' => now()->subMonth(), 'left_at' => now()->subWeek()]);

    EventMatch::factory()->forEvent($event)->withCompetitors([$former])->create();
    EventMatch::factory()->forEvent($event)->withCompetitors([$tagTeam])->create();

    expect(fn () => resolve(MatchCompetitorConflictService::class)
        ->ensureTagTeamsCanBeAssigned(collect([$event->id]), collect([$tagTeam])))
        ->not->toThrow(SchedulingConflictException::class, "Wrestler [{$former->name}]")
        ->and(fn () => resolve(MatchCompetitorConflictService::class)
            ->ensureWrestlersCanBeAssigned(collect([$event->id]), collect([$former])))
        ->not->toThrow(SchedulingConflictException::class, 'Tag team');
});
