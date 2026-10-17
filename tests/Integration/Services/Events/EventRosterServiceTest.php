<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Lifecycle\Roster\RosterDateAvailability;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Services\Events\EventRosterService;

test('lists everyone on the card as booked and as individuals, each once', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addWeek()]);
    $team = TagTeam::factory()
        ->for($promotion)
        ->create();
    [$memberOne, $memberTwo, $deletedMember, $solo] = Wrestler::factory()
        ->count(4)
        ->for($promotion)
        ->create()
        ->all();
    $team->wrestlers()->attach([$memberOne->id, $memberTwo->id, $deletedMember->id], ['joined_at' => now()->subDays(5), 'left_at' => null]);
    [$referee, $deletedReferee] = Referee::factory()
        ->count(2)
        ->for($promotion)
        ->create()
        ->all();
    $foreign = Wrestler::factory()
        ->for(Promotion::factory()->create())
        ->create();
    $first = EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$team, $solo])
        ->create();
    $first->referees()->attach($referee);
    $second = EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$solo, $foreign, $memberOne])
        ->create();
    $second->referees()->attach([$referee->id, $deletedReferee->id]);
    $deletedMember->delete();
    $deletedReferee->delete();

    // Act
    $roster = resolve(EventRosterService::class)->forEvent($event);

    // Assert
    expect($roster['entries']->map(EventRosterService::key(...))->all())->toBe([
        EventRosterService::key($team),
        EventRosterService::key($solo),
        EventRosterService::key($referee),
        EventRosterService::key($memberOne),
    ])
        ->and($roster['people']->map(EventRosterService::key(...))->all())->toBe([
            EventRosterService::key($solo),
            EventRosterService::key($referee),
            EventRosterService::key($memberOne),
            EventRosterService::key($memberTwo),
        ]);
});

test('keeps a deleted competitor on the roster', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addWeek()]);
    $wrestler = Wrestler::factory()
        ->for($promotion)
        ->create();
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$wrestler])
        ->create();
    $wrestler->delete();

    // Act
    $roster = resolve(EventRosterService::class)->forEvent($event);

    // Assert
    expect($roster['people']->map(EventRosterService::key(...))->all())->toBe([EventRosterService::key($wrestler)]);
});

test('can leave out matches that already have a result once the event date has passed', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->subWeek()]);
    [$open, $finished] = Wrestler::factory()
        ->count(2)
        ->for($promotion)
        ->create()
        ->all();
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$open])
        ->create();
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$finished])
        ->create(['match_finish' => MatchFinish::NoDecision]);
    $service = resolve(EventRosterService::class);

    // Act
    $all = $service->forEvent($event);
    $openOnly = $service->forEvent($event, excludePastResults: true);

    // Assert
    expect($all['people'])->toHaveCount(2)
        ->and($openOnly['people']->map(EventRosterService::key(...))->all())->toBe([EventRosterService::key($open)]);
});

test('has nobody on the roster of an event that has no matches', function (): void {
    // Arrange
    $event = Event::factory()
        ->create();

    // Act
    $roster = resolve(EventRosterService::class)->forEvent($event);

    // Assert
    expect($roster['entries'])->toBeEmpty()
        ->and($roster['people'])->toBeEmpty();
});

test('keys people the same way the roster date availability does', function (): void {
    // Arrange
    $people = [Wrestler::factory()->create(), TagTeam::factory()->create(), Referee::factory()->create()];

    // Act
    $keys = array_map(EventRosterService::key(...), $people);

    // Assert
    expect($keys)->toBe(array_map(RosterDateAvailability::key(...), $people));
});
