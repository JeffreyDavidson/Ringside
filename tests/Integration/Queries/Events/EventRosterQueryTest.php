<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\EventRosterQuery;

test('reads the matches of a card once with the current members of its tag teams already loaded', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addWeek()]);
    $team = TagTeam::factory()
        ->for($promotion)
        ->create();
    [$lower, $higher, $left] = Wrestler::factory()
        ->count(3)
        ->for($promotion)
        ->create()
        ->all();
    $team->wrestlers()->attach([$higher->id, $lower->id], ['joined_at' => now()->subDays(5), 'left_at' => null]);
    $team->wrestlers()->attach($left->id, ['joined_at' => now()->subDays(9), 'left_at' => now()->subDays(2)]);
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$team])
        ->create();
    $query = resolve(EventRosterQuery::class);

    // Act
    $matches = $query->get($event);
    $statements = recordStatements(function () use ($query, $event): void {
        $competitor = $query->get($event)->firstOrFail()->competitors->firstOrFail()->competitor;
        assert($competitor instanceof TagTeam);
        // Reading the members now must not run another statement.
        $competitor->currentWrestlers->modelKeys();
    });
    $team = $matches->firstOrFail()->competitors->firstOrFail()->competitor;
    assert($team instanceof TagTeam);

    // Assert
    expect($matches)->toHaveCount(1)
        ->and(collect($statements)->filter(fn (array $statement): bool => str_contains($statement['sql'], 'from "events_matches" where')))->toHaveCount(1)
        ->and(collect($statements)->filter(fn (array $statement): bool => str_contains($statement['sql'], 'from "wrestlers"')))->toHaveCount(1)
        ->and($team->currentWrestlers->modelKeys())->toBe([$lower->id, $higher->id]);
});

test('reads the same matches for a restored event that is still deleted, whatever promotion is current', function (): void {
    // Arrange
    $event = Event::factory()
        ->create(['date' => now()->addWeek()]);
    EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([Wrestler::factory()->create()])
        ->create();
    $event->delete();

    // Act
    $matches = resolve(EventRosterQuery::class)->get($event);

    // Assert
    expect($matches)->toHaveCount(1);
});

test('returns nothing without a statement for an event that is not saved yet', function (): void {
    // Arrange
    $query = resolve(EventRosterQuery::class);

    // Act
    $matches = $query->get(new Event);
    $statements = recordStatements(fn () => $query->get(new Event));

    // Assert
    expect($matches)->toBeEmpty()
        ->and($statements)->toBeEmpty();
});

test('leaves out matches with a result once the event date has passed, only when asked', function (): void {
    // Arrange
    $past = Event::factory()
        ->create(['date' => now()->subWeek()]);
    $open = EventMatch::factory()
        ->forEvent($past)
        ->withCompetitors([Wrestler::factory()->create()])
        ->create();
    EventMatch::factory()
        ->forEvent($past)
        ->withCompetitors([Wrestler::factory()->create()])
        ->create(['match_finish' => MatchFinish::NoDecision]);
    $future = Event::factory()
        ->create(['date' => now()->addWeek()]);
    EventMatch::factory()
        ->forEvent($future)
        ->withCompetitors([Wrestler::factory()->create()])
        ->create(['match_finish' => MatchFinish::NoDecision]);
    $query = resolve(EventRosterQuery::class);

    // Act
    $everything = $query->get($past);
    $openOnly = $query->get($past, excludePastResults: true);
    $futureOpenOnly = $query->get($future, excludePastResults: true);

    // Assert
    expect($everything)->toHaveCount(2)
        ->and($openOnly->modelKeys())->toBe([$open->id])
        ->and($futureOpenOnly)->toHaveCount(1);
});

test('loads the referees booked on each match', function (): void {
    // Arrange
    $event = Event::factory()
        ->create(['date' => now()->addWeek()]);
    $referee = Referee::factory()
        ->create();
    $match = EventMatch::factory()
        ->forEvent($event)
        ->create();
    $match->referees()->attach($referee);

    // Act
    $matches = resolve(EventRosterQuery::class)->get($event);

    // Assert
    expect($matches->firstOrFail()->referees->modelKeys())->toBe([$referee->id]);
});
