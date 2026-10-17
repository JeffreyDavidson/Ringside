<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchStipulation;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Events\EventPackCardMatchesQuery;

test('reads the matches of one event in card order in a single matches statement', function (): void {
    // Arrange
    $event = Event::factory()
        ->create(['date' => now()->addWeek()]);
    $other = Event::factory()
        ->create(['date' => now()->addWeek()]);
    $third = EventMatch::factory()
        ->forEvent($event)
        ->withMatchNumber(3)
        ->create();
    $first = EventMatch::factory()
        ->forEvent($event)
        ->withMatchNumber(1)
        ->create();
    $second = EventMatch::factory()
        ->forEvent($event)
        ->withMatchNumber(2)
        ->create();
    EventMatch::factory()
        ->forEvent($other)
        ->withMatchNumber(1)
        ->create();
    $query = resolve(EventPackCardMatchesQuery::class);

    // Act
    $matches = $query->get($event);
    $statements = recordStatements(fn () => $query->get($event));

    // Assert
    expect($matches->modelKeys())->toBe([$first->id, $second->id, $third->id])
        ->and(collect($statements)->filter(fn (array $statement): bool => str_contains($statement['sql'], 'from "events_matches" where')))->toHaveCount(1);
});

test('keeps deleted referees and deleted tag team members so the card can warn about them', function (): void {
    // Arrange
    $promotion = Promotion::factory()
        ->create();
    $event = Event::factory()
        ->for($promotion)
        ->create(['date' => now()->addWeek()]);
    $team = TagTeam::factory()
        ->for($promotion)
        ->create();
    [$lower, $higher] = Wrestler::factory()
        ->count(2)
        ->for($promotion)
        ->create()
        ->all();
    $team->wrestlers()->attach([$higher->id, $lower->id], ['joined_at' => now()->subDays(5), 'left_at' => null]);
    [$deletedReferee, $referee] = Referee::factory()
        ->count(2)
        ->for($promotion)
        ->create()
        ->all();
    $stipulation = MatchStipulation::factory()
        ->create();
    $match = EventMatch::factory()
        ->forEvent($event)
        ->withCompetitors([$team])
        ->create(['match_stipulation_id' => $stipulation->id]);
    $match->referees()->attach([$referee->id, $deletedReferee->id]);
    $lower->delete();
    $deletedReferee->delete();

    // Act
    $row = resolve(EventPackCardMatchesQuery::class)->get($event)->firstOrFail();
    $competitor = $row->competitors->firstOrFail()->competitor;
    assert($competitor instanceof TagTeam);

    // Assert
    expect($row->referees->modelKeys())->toBe([$deletedReferee->id, $referee->id])
        ->and($competitor->currentWrestlers->modelKeys())->toBe([$lower->id, $higher->id])
        ->and($row->matchStipulation?->is($stipulation))->toBeTrue();
});
