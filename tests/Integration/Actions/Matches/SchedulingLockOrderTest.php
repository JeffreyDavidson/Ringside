<?php

declare(strict_types=1);

use App\Actions\Matches\AddCompetitorsToMatchAction;
use App\Actions\Matches\AddMatchForEventAction;
use App\Actions\Matches\AddRefereesToMatchAction;
use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Actions\Matches\AddTitlesToMatchAction;
use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Actions\Matches\RecordResultAction;
use App\Actions\Matches\UpdateMatchAction;
use App\Data\Matches\EventMatchData;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Enums\Titles\TitleType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Each scenario returns [the event the action works on, a closure running the action].
 *
 * @return array<string, array{0: Closure(Event): array{0: Event, 1: Closure(): mixed}}>
 */
$bookingScenarios = [
    'add match for event' => [function (Event $event): array {
        $data = new EventMatchData(
            MatchType::Singles,
            Referee::factory()->bookable()->count(1)->create(),
            Title::query()->whereKey([])->get(),
            collect([
                1 => ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
                2 => ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
            ]),
            null,
        );

        return [$event, fn () => resolve(AddMatchForEventAction::class)->handle($event, $data)];
    }],
    'update match' => [function (Event $event): array {
        $match = EventMatch::factory()->forEvent($event)->create(['match_type' => MatchType::Singles]);
        $data = new EventMatchData(
            MatchType::Singles,
            Referee::factory()->bookable()->count(1)->create(),
            Title::query()->whereKey([])->get(),
            collect([
                1 => ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
                2 => ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
            ]),
            null,
        );

        return [$event, fn () => resolve(UpdateMatchAction::class)->handle($match, $data)];
    }],
    'add wrestlers' => [function (Event $event): array {
        $match = EventMatch::factory()->forEvent($event)->create();
        $wrestlers = Wrestler::factory()->bookable()->count(2)->create();

        return [$event, fn () => resolve(AddWrestlersToMatchAction::class)->handle($match, $wrestlers, 1)];
    }],
    'add tag teams' => [function (Event $event): array {
        $match = EventMatch::factory()->forEvent($event)->create();
        $tagTeams = TagTeam::factory()->bookable()->count(2)->create();

        return [$event, fn () => resolve(AddTagTeamsToMatchAction::class)->handle($match, $tagTeams, 1)];
    }],
    'add referees' => [function (Event $event): array {
        $match = EventMatch::factory()->forEvent($event)->create();
        $referees = Referee::factory()->bookable()->count(2)->create();

        return [$event, fn () => resolve(AddRefereesToMatchAction::class)->handle($match, $referees)];
    }],
    'add titles' => [function (Event $event): array {
        $match = EventMatch::factory()
            ->forEvent($event)
            ->withCompetitors(Wrestler::factory()->bookable()->count(2)->create()->all())
            ->create();
        $titles = Title::factory()->active()->count(2)->create(['type' => TitleType::Singles]);

        return [$event, fn () => resolve(AddTitlesToMatchAction::class)->handle($match, $titles)];
    }],
    'add competitors' => [function (Event $event): array {
        $match = EventMatch::factory()->forEvent($event)->create(['match_type' => MatchType::Singles]);
        $competitors = collect([
            1 => ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
            2 => ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
        ]);

        return [$event, fn () => resolve(AddCompetitorsToMatchAction::class)->handle($match, $competitors)];
    }],
];

dataset('booking scenarios', $bookingScenarios);

dataset('scheduling lock scenarios', [
    ...$bookingScenarios,
    'record result' => [function (Event $event): array {
        $match = EventMatch::factory()->forEvent($event)->create(['match_type' => MatchType::Singles]);
        $result = new MatchResultData(finish: MatchFinish::NoDecision, winningSide: null, eliminations: collect());

        return [$event, fn () => resolve(RecordResultAction::class)->handle($match, $result)];
    }],
]);

test('it locks the complete ordered same-date event set before anything else', function (Closure $scenario) {
    // Arrange
    $date = now()->addWeek();
    $event = Event::factory()->create(['date' => $date]);
    $sameDateEvent = Event::factory()->create(['date' => $date]);
    Event::factory()->create(['date' => $date->copy()->addHour()]);
    [$event, $act] = $scenario($event);

    // Act
    $statements = recordStatements($act);

    // Assert
    $lockedStatements = collect($statements)->filter(fn (array $statement): bool => $statement['locked']);
    $firstLockIndex = array_find_key($statements, fn (array $statement): bool => $statement['locked'])
        ?? throw new RuntimeException('Expected the action to lock at least one row.');
    $firstLock = $statements[$firstLockIndex];
    $writesBeforeFirstLock = collect(array_slice($statements, 0, $firstLockIndex))
        ->filter(fn (array $statement): bool => preg_match('/^(insert|update|delete)\b/', $statement['sql']) === 1);

    expect($firstLock['sql'])
        ->toContain('from "events"')
        ->toContain('order by "id" asc')
        ->and($firstLock['bindings'])->toContain($event->id)
        ->and(collect($firstLock['bindings'])->contains(
            fn (mixed $binding): bool => $binding instanceof DateTimeInterface
                && $binding->getTimestamp() === $event->date->getTimestamp()
        ))->toBeTrue()
        ->and($writesBeforeFirstLock)->toBeEmpty()
        ->and($lockedStatements->filter(
            fn (array $statement): bool => str_contains($statement['sql'], 'from "events"')
                && ! str_contains($statement['sql'], 'order by "id" asc')
        ))->toBeEmpty()
        ->and($sameDateEvent->id)->toBeGreaterThan($event->id);
})->with('scheduling lock scenarios');

final class DeadlockTracker
{
    public int $lockQueries = 0;
}

/**
 * Fail the first N event-set lock queries the way PostgreSQL reports a lost deadlock.
 */
function failEventSetLocksWithDeadlock(int $failures): DeadlockTracker
{
    $tracker = new DeadlockTracker;

    DB::listen(function (QueryExecuted $query) use ($tracker, $failures): void {
        if (! str_contains($query->sql, 'order by "id"') || ! str_contains($query->sql, 'from "events"')) {
            return;
        }

        $tracker->lockQueries++;

        if ($tracker->lockQueries <= $failures) {
            throw new QueryException(
                'pgsql',
                $query->sql,
                $query->bindings,
                new PDOException('SQLSTATE[40P01]: Deadlock detected: 7 ERROR: deadlock detected'),
            );
        }
    });

    return $tracker;
}

/**
 * Laravel only retries a deadlock when the action owns the outermost transaction, so the
 * transaction RefreshDatabase wraps around the test is committed first and the schema is
 * rebuilt afterwards to discard whatever the test persisted.
 */
function runOutsideTestTransaction(Closure $callback): int
{
    DB::commit();

    try {
        $callback();

        return DB::transactionLevel();
    } finally {
        Artisan::call('migrate:fresh');
    }
}

test('it retries the whole booking transaction after a deadlock', function (Closure $scenario) {
    // Arrange
    [, $act] = $scenario(Event::factory()->create(['date' => now()->addWeek()]));

    $tracker = failEventSetLocksWithDeadlock(1);

    // Act
    $transactionLevel = runOutsideTestTransaction($act);

    // Assert
    expect($tracker->lockQueries)->toBeGreaterThan(1)
        ->and($transactionLevel)->toBe(0);
})->with('booking scenarios');

test('it gives up after three deadlocked booking attempts', function (Closure $scenario) {
    // Arrange
    [, $act] = $scenario(Event::factory()->create(['date' => now()->addWeek()]));

    $tracker = failEventSetLocksWithDeadlock(PHP_INT_MAX);

    // Act
    runOutsideTestTransaction(function () use ($act): void {
        expect($act)->toThrow(QueryException::class);
    });

    // Assert
    expect($tracker->lockQueries)->toBe(3);
})->with(['add match for event' => [$bookingScenarios['add match for event'][0]]]);
