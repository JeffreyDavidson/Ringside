<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Titles\Title;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the match number migration lists the repeated match numbers before changing anything', function () {
    $event = Event::factory()->create();
    Schema::table('events_matches', fn (Blueprint $table) => $table->dropUnique('events_matches_event_id_match_number_unique'));
    $first = EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create();
    $second = EventMatch::factory()->forEvent($event)->withMatchNumber(1)->create();
    $second->delete();
    $migration = require database_path('migrations/2026_10_05_024424_enforce_unique_match_number_per_event.php');
    $up = new ReflectionMethod($migration, 'up');

    expect(fn () => $up->invoke($migration))
        ->toThrow(
            RuntimeException::class,
            "match number 1 of event {$event->id} is used by match ids {$first->id}, {$second->id}",
        );
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_IMPLICIT_COMMIT);

test('the pivot migration lists a referee repeated within a match before changing anything', function () {
    $match = EventMatch::factory()->create();
    $referee = Referee::factory()->create();
    Schema::table('events_matches_referees', fn (Blueprint $table) => $table->dropUnique('events_matches_referees_match_referee_unique'));
    $match->referees()->attach([$referee->id]);
    $match->referees()->attach([$referee->id]);
    $rowIds = DB::table('events_matches_referees')->orderBy('id')->pluck('id')->implode(', ');
    $migration = require database_path('migrations/2026_10_05_024426_enforce_unique_referees_and_titles_per_match.php');
    $up = new ReflectionMethod($migration, 'up');

    expect(fn () => $up->invoke($migration))
        ->toThrow(
            RuntimeException::class,
            "events_matches_referees: match {$match->id} lists referee_id {$referee->id} in row ids {$rowIds}",
        );
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_IMPLICIT_COMMIT);

test('the pivot migration lists a title repeated within a match before changing anything', function () {
    $match = EventMatch::factory()->create();
    $title = Title::factory()->create();
    Schema::table('events_matches_titles', fn (Blueprint $table) => $table->dropUnique('events_matches_titles_match_title_unique'));
    $match->titles()->attach([$title->id]);
    $match->titles()->attach([$title->id]);
    $rowIds = DB::table('events_matches_titles')->orderBy('id')->pluck('id')->implode(', ');
    $migration = require database_path('migrations/2026_10_05_024426_enforce_unique_referees_and_titles_per_match.php');
    $up = new ReflectionMethod($migration, 'up');

    expect(fn () => $up->invoke($migration))
        ->toThrow(
            RuntimeException::class,
            "events_matches_titles: match {$match->id} lists title_id {$title->id} in row ids {$rowIds}",
        )
        ->and(DB::table('events_matches_titles')->count())->toBe(2);
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_IMPLICIT_COMMIT);
