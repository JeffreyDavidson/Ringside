<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Services\Matches\SchedulingSlotLockService;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;

/**
 * Test helper functions for common testing scenarios.
 *
 * These functions provide convenient methods for creating test data,
 * setting up common test scenarios, and performing repetitive test operations.
 */

/**
 * Create a wrestler with realistic attributes for testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->create($attributes);
}

/**
 * Create an employed wrestler for testing availability scenarios.
 *
 * @param  array<string, mixed>  $attributes
 */
function createEmployedWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->employed()->create($attributes);
}

/**
 * Create a bookable wrestler for match testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createBookableWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->bookable()->create($attributes);
}

/**
 * Create a manager with realistic attributes for testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createManager(array $attributes = []): Manager
{
    return Manager::factory()->create($attributes);
}

/**
 * Create a referee with realistic attributes for testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createReferee(array $attributes = []): Referee
{
    return Referee::factory()->create($attributes);
}

/**
 * Create a tag team with realistic attributes for testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createTagTeam(array $attributes = []): TagTeam
{
    return TagTeam::factory()->create($attributes);
}

/**
 * Create a stable with realistic attributes for testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createStable(array $attributes = []): Stable
{
    return Stable::factory()->create($attributes);
}

/**
 * Create a title with realistic attributes for testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createTitle(array $attributes = []): Title
{
    return Title::factory()->create($attributes);
}

/**
 * Create a venue with realistic attributes for testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createVenue(array $attributes = []): Venue
{
    return Venue::factory()->create($attributes);
}

/**
 * Create an event with realistic attributes for testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createEvent(array $attributes = []): Event
{
    return Event::factory()->create($attributes);
}

/**
 * Create a match with realistic attributes for testing.
 *
 * @param  array<string, mixed>  $attributes
 */
function createMatch(array $attributes = []): EventMatch
{
    return EventMatch::factory()->create($attributes);
}

/**
 * Create a collection of wrestlers for testing bulk operations.
 *
 * @param  array<string, mixed>  $attributes
 * @return Collection<int, Wrestler>
 */
function createWrestlers(int $count = 5, array $attributes = []): Collection
{
    return Wrestler::factory()->count($count)->create($attributes);
}

/**
 * Create a collection of managers for testing bulk operations.
 *
 * @param  array<string, mixed>  $attributes
 * @return Collection<int, Manager>
 */
function createManagers(int $count = 5, array $attributes = []): Collection
{
    return Manager::factory()->count($count)->create($attributes);
}

/**
 * Create a collection of referees for testing bulk operations.
 *
 * @param  array<string, mixed>  $attributes
 * @return Collection<int, Referee>
 */
function createReferees(int $count = 5, array $attributes = []): Collection
{
    return Referee::factory()->count($count)->create($attributes);
}

/**
 * Create a complete roster with wrestlers, managers, referees, tag teams, and stables.
 *
 * @return array<string, mixed>
 */
function createFullRoster(int $size = 10): array
{
    return [
        'wrestlers' => createWrestlers($size),
        'managers' => createManagers($size / 2),
        'referees' => createReferees($size / 5),
        'tag_teams' => TagTeam::factory()->count($size / 5)->create(),
        'stables' => Stable::factory()->count($size / 10)->create(),
    ];
}

/**
 * Create a complete event with matches and participants.
 *
 * @return array<string, mixed>
 */
function createEventWithMatches(int $matchCount = 3): array
{
    $event = createEvent();
    $matches = [];

    for ($i = 0; $i < $matchCount; $i++) {
        $matches[] = createMatch([
            'event_id' => $event->id,
            'match_number' => $i + 1,
        ]);
    }

    return [
        'event' => $event,
        'matches' => collect($matches),
    ];
}

/**
 * Create a tag team with wrestlers.
 *
 * @return array<string, mixed>
 */
function createTagTeamWithWrestlers(int $wrestlerCount = 2): array
{
    $tagTeam = createTagTeam();
    $wrestlers = createWrestlers($wrestlerCount);

    foreach ($wrestlers as $wrestler) {
        $tagTeam->wrestlers()->attach($wrestler->id, [
            'joined_at' => now(),
        ]);
    }

    return [
        'tag_team' => $tagTeam,
        'wrestlers' => $wrestlers,
    ];
}

/**
 * Create a stable with members.
 *
 * @return array<string, mixed>
 */
function createStableWithMembers(int $wrestlerCount = 3, int $tagTeamCount = 1): array
{
    $stable = createStable();
    $wrestlers = createWrestlers($wrestlerCount);
    $tagTeams = TagTeam::factory()->count($tagTeamCount)->create();

    foreach ($wrestlers as $wrestler) {
        $stable->wrestlers()->attach($wrestler->id, [
            'joined_at' => now(),
        ]);
    }

    foreach ($tagTeams as $tagTeam) {
        $stable->tagTeams()->attach($tagTeam->id, [
            'joined_at' => now(),
        ]);
    }

    return [
        'stable' => $stable,
        'wrestlers' => $wrestlers,
        'tag_teams' => $tagTeams,
    ];
}

/**
 * Create a wrestler with a manager relationship.
 *
 * @return array<string, mixed>
 */
function createWrestlerWithManager(): array
{
    $wrestler = createWrestler();
    $manager = createManager();

    $wrestler->managers()->attach($manager->id, [
        'hired_at' => now(),
    ]);

    return [
        'wrestler' => $wrestler,
        'manager' => $manager,
    ];
}

/**
 * Create a championship scenario with title and champion.
 *
 * @return array<string, mixed>
 */
function createChampionshipScenario(string $championType = 'wrestler'): array
{
    $title = createTitle();

    $champion = match ($championType) {
        'wrestler' => createWrestler(),
        'tag_team' => createTagTeam(),
        default => throw new InvalidArgumentException("Invalid champion type: {$championType}"),
    };

    // Create a basic event match for the championship
    $event = createEvent();
    $match = createMatch(['event_id' => $event->id]);

    $championship = $title->championships()->create([
        'champion_id' => $champion->id,
        'champion_type' => $championType,
        'won_at' => now(),
        'won_match_id' => $match->id,
    ]);

    return [
        'title' => $title,
        'champion' => $champion,
        'championship' => $championship,
    ];
}

/**
 * Create an injured wrestler for testing injury scenarios.
 *
 * @param  array<string, mixed>  $attributes
 */
function createInjuredWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->injured()->create($attributes);
}

/**
 * Create a suspended wrestler for testing suspension scenarios.
 *
 * @param  array<string, mixed>  $attributes
 */
function createSuspendedWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->suspended()->create($attributes);
}

/**
 * Create a retired wrestler for testing retirement scenarios.
 *
 * @param  array<string, mixed>  $attributes
 */
function createRetiredWrestler(array $attributes = []): Wrestler
{
    return Wrestler::factory()->retired()->create($attributes);
}

/**
 * Create a wrestler with employment history for testing timeline scenarios.
 */
function createWrestlerWithEmploymentHistory(): Wrestler
{
    $wrestler = createWrestler();

    // Create past employment
    $wrestler->employments()->create([
        'started_at' => now()->subYears(2),
        'ended_at' => now()->subYear(),
    ]);

    // Create current employment
    $wrestler->employments()->create([
        'started_at' => now()->subMonths(6),
        'ended_at' => null,
    ]);

    return $wrestler;
}

/**
 * Seed basic lookup data for testing.
 *
 * Note: MatchType and MatchFinish are PHP enums and do not require seeding.
 */
function seedBasicLookupData(): void
{
    // MatchType and MatchFinish are PHP enums, so no lookup data is required.
}

/**
 * Create a realistic wrestling date (not too far in past or future).
 */
function wrestlingDate(string $period = 'recent'): Carbon\Carbon
{
    return match ($period) {
        'recent' => now()->subDays(random_int(1, 30)),
        'past' => now()->subMonths(random_int(1, 24)),
        'future' => now()->addDays(random_int(1, 90)),
        'historical' => now()->subYears(random_int(1, 10)),
        default => now(),
    };
}

/**
 * Create a realistic wrestling time period (start and end dates).
 *
 * @return array{started_at: Carbon\Carbon, ended_at: Carbon\Carbon|null}
 */
function wrestlingTimePeriod(string $type = 'employment'): array
{
    $start = match ($type) {
        'employment' => now()->subMonths(random_int(1, 24)),
        'injury' => now()->subWeeks(random_int(1, 12)),
        'suspension' => now()->subMonths(random_int(1, 6)),
        'retirement' => now()->subYears(random_int(1, 5)),
        default => now()->subMonths(random_int(1, 12)),
    };

    $end = match ($type) {
        'employment' => random_int(0, 1) ? $start->copy()->addMonths(random_int(1, 12)) : null,
        'injury' => random_int(0, 1) ? $start->copy()->addWeeks(random_int(1, 8)) : null,
        'suspension' => random_int(0, 1) ? $start->copy()->addMonths(random_int(1, 3)) : null,
        'retirement' => random_int(0, 1) ? $start->copy()->addYears(random_int(1, 3)) : null,
        default => random_int(0, 1) ? $start->copy()->addMonths(random_int(1, 6)) : null,
    };

    return [
        'started_at' => $start,
        'ended_at' => $end,
    ];
}

/**
 * Record every SQL statement issued while the callback runs, flagging row-locking statements.
 *
 * SQLite discards row-lock clauses, so its grammar is swapped for one that renders the lock as a
 * visible comment. PostgreSQL already emits the real clause. The original grammar is always restored.
 *
 * @return array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>
 */
function recordStatements(Closure $callback): array
{
    $connection = DB::connection();
    $originalGrammar = $connection->getQueryGrammar();

    if ($connection->getDriverName() === 'sqlite') {
        $connection->setQueryGrammar(new class($connection) extends SQLiteGrammar
        {
            protected function compileLock(QueryBuilder $query, $value): string
            {
                return $value ? ' /* for update */' : ' /* for share */';
            }
        });
    }

    $statements = [];
    DB::listen(function ($query) use (&$statements): void {
        $sql = mb_strtolower($query->sql);
        $statements[] = [
            'sql' => $sql,
            'bindings' => $query->bindings,
            'locked' => str_contains($sql, 'for update'),
        ];
    });

    try {
        $callback();
    } finally {
        $connection->setQueryGrammar($originalGrammar);
    }

    return $statements;
}

/**
 * The primary key a recorded statement was bound to: the first binding of a single-row lock, or the last binding of an
 * update that targets one row of a pivot table.
 *
 * @param  array{sql: string, bindings: array<int, mixed>, locked: bool}  $statement
 */
function boundKey(array $statement, bool $last = false): int
{
    $binding = $last ? array_last($statement['bindings']) : ($statement['bindings'][0] ?? null);

    return is_int($binding) ? $binding : throw new RuntimeException('Expected the statement to be bound to an integer key.');
}

/**
 * The primary keys of the rows a recorded action locked one at a time in a table, in locking order.
 *
 * @param  array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>  $statements
 * @return array<int, int>
 */
function lockedRowIds(array $statements, string $table): array
{
    $ids = [];

    foreach ($statements as $statement) {
        if ($statement['locked'] && str_contains($statement['sql'], "from \"{$table}\"")) {
            $ids[] = boundKey($statement);
        }
    }

    return $ids;
}

/**
 * The related keys of the pivot rows a recorded action updated one at a time, in update order.
 *
 * @param  array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>  $statements
 * @return array<int, int>
 */
function updatedRowIds(array $statements, string $table): array
{
    $ids = [];

    foreach ($statements as $statement) {
        if (str_starts_with($statement['sql'], "update \"{$table}\"")) {
            $ids[] = boundKey($statement, last: true);
        }
    }

    return $ids;
}

/**
 * A connection double that reports the given database driver, so the SQLite test database can exercise the
 * driver specific branches of collaborators that would otherwise only run on a server such as PostgreSQL.
 */
function driverConnection(string $driver): Connection&DoubleInterface
{
    $connection = Double::for(Connection::class);
    $connection->expects('getDriverName')->returns($driver)->times(minimum: 0);

    return $connection;
}

/**
 * A scheduling slot lock that runs its PostgreSQL branch. The advisory lock statement cannot run on SQLite,
 * so it is handed to the observer instead of being sent to the database.
 *
 * @param  Closure(string, array<mixed>): mixed  $onStatement  Receives the SQL and its bindings
 */
function postgresSlotLock(Closure $onStatement): SchedulingSlotLockService
{
    $connection = driverConnection('pgsql');
    $connection->expects('select')
        ->resolves(function (mixed ...$arguments) use ($onStatement): array {
            [$sql, $bindings] = $arguments;

            is_string($sql) && is_array($bindings) || throw new LogicException('Expected the SQL and its bindings.');

            $onStatement($sql, $bindings);

            return [];
        })
        ->times(minimum: 0);

    return new SchedulingSlotLockService($connection);
}
