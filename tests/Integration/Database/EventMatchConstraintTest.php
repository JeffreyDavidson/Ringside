<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('an event cannot repeat a match number', function () {
    // Arrange
    $event = Event::factory()->create();
    EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create();

    // Act
    $repeatedNumber = fn () => DB::transaction(fn () => EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create());

    // Assert
    expect($repeatedNumber)->toThrow(QueryException::class)
        ->and(EventMatch::query()->whereBelongsTo($event)->count())->toBe(1);
});

test('an event cannot reuse the match number of a deleted match', function () {
    // Arrange
    $event = Event::factory()->create();
    EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create()->delete();

    // Act
    $reusedNumber = fn () => DB::transaction(fn () => EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create());

    // Assert
    expect($reusedNumber)->toThrow(QueryException::class)
        ->and(EventMatch::query()->whereBelongsTo($event)->withTrashed()->count())->toBe(1);
});

test('different events may use the same match number', function () {
    // Arrange
    [$first, $second] = Event::factory()->count(2)->create()->all();

    // Act
    EventMatch::factory()->forEvent($first)->withMatchNumber(1)->create();
    EventMatch::factory()->forEvent($second)->withMatchNumber(1)->create();

    // Assert
    expect(EventMatch::query()->whereBelongsTo($first)->count())->toBe(1)
        ->and(EventMatch::query()->whereBelongsTo($second)->count())->toBe(1);
});

test('a match cannot list the same referee twice', function () {
    // Arrange
    $match = EventMatch::factory()->create();
    $referee = Referee::factory()->create();
    $match->referees()->attach($referee);

    // Act
    $listedAgain = fn () => DB::transaction(fn () => $match->referees()->attach($referee));

    // Assert
    expect($listedAgain)->toThrow(QueryException::class)
        ->and(DB::table('events_matches_referees')->count())->toBe(1);
});

test('a match cannot list the same title twice', function () {
    // Arrange
    $match = EventMatch::factory()->create();
    $title = Title::factory()->create();
    $match->titles()->attach($title);

    // Act
    $listedAgain = fn () => DB::transaction(fn () => $match->titles()->attach($title));

    // Assert
    expect($listedAgain)->toThrow(QueryException::class)
        ->and(DB::table('events_matches_titles')->count())->toBe(1);
});

test('different matches may list the same referee and title', function () {
    // Arrange
    [$first, $second] = EventMatch::factory()->count(2)->create()->all();
    $referee = Referee::factory()->create();
    $title = Title::factory()->create();

    // Act
    $first->referees()->attach($referee);
    $second->referees()->attach($referee);
    $first->titles()->attach($title);
    $second->titles()->attach($title);

    // Assert
    expect(DB::table('events_matches_referees')->count())->toBe(2)
        ->and(DB::table('events_matches_titles')->count())->toBe(2);
});
