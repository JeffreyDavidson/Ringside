<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('an event cannot repeat a match number', function () {
    $event = Event::factory()->create();
    EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create();

    expect(fn () => DB::transaction(fn () => EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create()))
        ->toThrow(QueryException::class);
});

test('an event cannot reuse the match number of a deleted match', function () {
    $event = Event::factory()->create();
    EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create()->delete();

    expect(fn () => DB::transaction(fn () => EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create()))
        ->toThrow(QueryException::class);
});

test('different events may use the same match number', function () {
    [$first, $second] = Event::factory()->count(2)->create()->all();

    EventMatch::factory()->forEvent($first)->withMatchNumber(1)->create();
    $match = EventMatch::factory()->forEvent($second)->withMatchNumber(1)->create();

    expect($match->exists)->toBeTrue();
});

test('a match cannot list the same referee twice', function () {
    $match = EventMatch::factory()->create();
    $referee = Referee::factory()->create();
    $match->referees()->attach($referee);

    expect(fn () => DB::transaction(fn () => $match->referees()->attach($referee)))
        ->toThrow(QueryException::class);
});

test('a match cannot list the same title twice', function () {
    $match = EventMatch::factory()->create();
    $title = Title::factory()->create();
    $match->titles()->attach($title);

    expect(fn () => DB::transaction(fn () => $match->titles()->attach($title)))
        ->toThrow(QueryException::class);
});

test('different matches may list the same referee and title', function () {
    [$first, $second] = EventMatch::factory()->count(2)->create()->all();
    $referee = Referee::factory()->create();
    $title = Title::factory()->create();

    $first->referees()->attach($referee);
    $second->referees()->attach($referee);
    $first->titles()->attach($title);
    $second->titles()->attach($title);

    expect(DB::table('events_matches_referees')->count())->toBe(2)
        ->and(DB::table('events_matches_titles')->count())->toBe(2);
});
