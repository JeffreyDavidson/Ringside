<?php

declare(strict_types=1);

use App\Actions\Roster\Availability\CreateAction;
use App\Data\Roster\UnavailabilityData;
use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\EventRosterAvailabilityService;
use App\Services\Events\EventRosterService;

test('lists the wrestlers, tag team members and referees on the card who are unavailable on the event day', function (): void {
    // Arrange
    $actor = administrator();
    $promotion = Promotion::factory()
        ->create(['timezone' => 'America/New_York']);
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addWeek()]);
    $team = TagTeam::factory()
        ->for($promotion)
        ->create();
    [$blockedWrestler, $blockedMember, $freeMember, $otherDay] = Wrestler::factory()
        ->count(4)
        ->for($promotion)
        ->create()
        ->all();
    $team->wrestlers()->attach([$blockedMember->id, $freeMember->id], ['joined_at' => now()->subDays(5), 'left_at' => null]);
    [$blockedReferee, $freeReferee] = Referee::factory()
        ->count(2)
        ->for($promotion)
        ->create()
        ->all();
    $match = EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$team, $blockedWrestler, $otherDay])
        ->create();
    $match->referees()->attach([$blockedReferee->id, $freeReferee->id]);
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    $create = resolve(CreateAction::class);
    $create->handle($blockedWrestler, new UnavailabilityData($day, $day, null), $actor);
    $create->handle($blockedMember, new UnavailabilityData($day, $day, null), $actor);
    $create->handle($blockedReferee, new UnavailabilityData($day, $day, null), $actor);
    $create->handle($otherDay, new UnavailabilityData('2000-01-01', '2000-01-01', null), $actor);

    // Act
    $unavailable = resolve(EventRosterAvailabilityService::class)->get($event);

    // Assert
    expect($unavailable->map(EventRosterService::key(...))->all())->toEqualCanonicalizing([
        EventRosterService::key($blockedWrestler),
        EventRosterService::key($blockedMember),
        EventRosterService::key($blockedReferee),
    ]);
});

test('does not read the card of an event that has no date', function (): void {
    // Arrange
    $event = Event::factory()
        ->create(['date' => null]);
    $service = resolve(EventRosterAvailabilityService::class);

    // Act
    $unavailable = null;
    $statements = recordStatements(function () use ($service, $event, &$unavailable): void {
        $unavailable = $service->get($event);
    });

    // Assert
    expect($unavailable)->toBeEmpty()
        ->and(collect($statements)->filter(fn (array $statement): bool => str_contains($statement['sql'], 'events_matches')))->toBeEmpty();
});

test('picks the unavailable ones out of people the caller already read', function (): void {
    // Arrange
    $actor = administrator();
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addWeek()]);
    [$blocked, $free] = Wrestler::factory()
        ->count(2)
        ->for($promotion)
        ->create()
        ->all();
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    resolve(CreateAction::class)->handle($blocked, new UnavailabilityData($day, $day, null), $actor);
    $service = resolve(EventRosterAvailabilityService::class);

    // Act
    $unavailable = $service->among($event, collect([$free, $blocked]));
    $undated = $service->among(Event::factory()->create(['date' => null]), collect([$blocked]));

    // Assert
    expect($unavailable->map(EventRosterService::key(...))->all())->toBe([EventRosterService::key($blocked)])
        ->and($undated)->toBeEmpty();
});

test('ignores a result that was already recorded once the event date has passed', function (): void {
    // Arrange
    $actor = administrator();
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->subWeek()]);
    [$finished, $open] = Wrestler::factory()
        ->count(2)
        ->for($promotion)
        ->create()
        ->all();
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$finished])
        ->create(['match_finish' => MatchFinish::NoDecision]);
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$open])
        ->create();
    $day = ($event->local_date ?? throw new LogicException('Expected a scheduled fixture'))->toDateString();
    resolve(CreateAction::class)->handle($finished, new UnavailabilityData($day, $day, null), $actor);
    resolve(CreateAction::class)->handle($open, new UnavailabilityData($day, $day, null), $actor);

    // Act
    $unavailable = resolve(EventRosterAvailabilityService::class)->get($event);

    // Assert
    expect($unavailable->map(EventRosterService::key(...))->all())->toBe([EventRosterService::key($open)]);
});
