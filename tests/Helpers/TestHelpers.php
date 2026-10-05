<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Users\User;
use App\Services\Promotions\PromotionContextService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

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
 * Sign in a new active user who holds an active membership of the promotion with the given role, and enforce that
 * promotion as the context, the way EstablishPromotionContext does at the start of a request.
 */
function actingAsPromotionMember(Promotion $promotion, MembershipRole $role): User
{
    $user = User::factory()->basicUser()->create(['status' => UserStatus::Active]);
    $promotion->users()->attach($user, [
        'role' => $role,
        'status' => MembershipStatus::Active,
    ]);
    actingAs($user);

    $context = resolve(PromotionContextService::class);
    $context->clear();
    $context->set($promotion);
    $context->enforce();

    return $user;
}

/**
 * Change a signed-in member's role or status mid-session, as another request would, and drop the memoised membership
 * so the next authorization check reads the new row.
 */
function changePromotionMembership(Promotion $promotion, User $user, MembershipRole $role, MembershipStatus $status = MembershipStatus::Active): void
{
    $promotion->users()->updateExistingPivot($user->id, [
        'role' => $role,
        'status' => $status,
    ]);
    resolve(PromotionContextService::class)->forgetMemberships();
}

/**
 * Record every SQL statement issued while the callback runs, flagging row-locking statements.
 *
 * SQLite discards row-lock clauses, so its grammar is swapped for one that renders the lock as a
 * visible comment. PostgreSQL and MySQL already emit the real clause. The original grammar is always restored.
 * The SQL is normalized with normalizedSql(), so the same assertions hold on every engine.
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
        $sql = normalizedSql($query->sql);
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
 * The position of the first recorded statement the callback matches, failing the test when none does.
 *
 * @param  array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>  $statements
 * @param  Closure(array{sql: string, bindings: array<int, mixed>, locked: bool}): bool  $matches
 */
function statementPosition(array $statements, Closure $matches): int
{
    return array_find_key($statements, $matches) ?? throw new RuntimeException('Expected a matching statement to be recorded.');
}

/**
 * The primary key a recorded statement was bound to: the first binding of a single-row lock, or the binding counted from the
 * end of an update that targets one row of a pivot table.
 *
 * @param  array{sql: string, bindings: array<int, mixed>, locked: bool}  $statement
 */
function boundKey(array $statement, int $fromEnd = 0): int
{
    $binding = $fromEnd > 0 ? array_slice($statement['bindings'], -$fromEnd)[0] ?? null : ($statement['bindings'][0] ?? null);

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
 * The related keys of the pivot rows a recorded action updated one at a time, in update order. The key is the binding
 * that many positions from the end of the statement (a pivot update ends with its start column comparison).
 *
 * @param  array<int, array{sql: string, bindings: array<int, mixed>, locked: bool}>  $statements
 * @return array<int, int>
 */
function updatedRowIds(array $statements, string $table, int $bindingFromEnd = 1): array
{
    $ids = [];

    foreach ($statements as $statement) {
        if (str_starts_with($statement['sql'], "update \"{$table}\"")) {
            $ids[] = boundKey($statement, fromEnd: $bindingFromEnd);
        }
    }

    return $ids;
}

/**
 * SQL in one spelling for every engine: lower case, with MySQL's backtick identifier quotes turned into the double
 * quotes SQLite and PostgreSQL use, so assertions about generated statements hold on all three.
 */
function normalizedSql(string $sql): string
{
    return str_replace('`', '"', mb_strtolower($sql));
}

/**
 * Whether the suite runs on the given database driver, for skipping a test that only applies to other engines with an
 * explicit reason.
 */
function runsOnDriver(string $driver): bool
{
    return DB::connection()->getDriverName() === $driver;
}

/**
 * The reason a test that drops or creates schema objects inside the test transaction is skipped on MySQL.
 */
const MYSQL_IMPLICIT_COMMIT = 'MySQL commits the test transaction on DDL, so the schema change and the test data would leak into later tests.';

/**
 * The reason a test that relies on the database rejecting duplicate unowned stable names is skipped on MySQL.
 */
const MYSQL_UNOWNED_STABLE_NAMES = 'MySQL has no partial index for active stables without a promotion; form validation and the split eligibility check are the guard there (migration 2026_10_01_190000).';

/**
 * The reason the real-process concurrency tests are skipped.
 */
const CONCURRENCY_TESTS_SKIPPED = 'Set DB_CONNECTION=pgsql or mysql and RUN_CONCURRENCY_TESTS=1 to run the concurrency tests.';

/**
 * The reason a concurrency test about PostgreSQL's physical row order is skipped on other engines.
 */
const POSTGRES_PLANNER_ORDER = 'The test forces PostgreSQL planner plans (hash joins off, sequential scans) and padding that InnoDB does not share.';

/**
 * Whether the real-process concurrency tests are switched on. SQLite ignores row locks, so they need a server engine,
 * and they commit their data and rebuild the schema, so they only run when asked for in the real environment.
 */
function concurrencyTestsEnabled(): bool
{
    return in_array(getenv('DB_CONNECTION'), ['pgsql', 'mysql'], true) && getenv('RUN_CONCURRENCY_TESTS') === '1';
}

/**
 * The server's own cumulative count of deadlocks it has resolved. Laravel retries a deadlocked transaction, so an
 * outcome alone cannot show that a deadlock never happened; compare the count before and after.
 */
function resolvedDeadlocks(): int
{
    $deadlocks = match (DB::connection()->getDriverName()) {
        'mysql' => DB::scalar("select `count` from information_schema.INNODB_METRICS where name = 'lock_deadlocks' and status = 'enabled'"),
        default => DB::scalar('select deadlocks from pg_stat_database where datname = current_database()'),
    };

    return is_numeric($deadlocks) ? (int) $deadlocks : throw new RuntimeException('Unable to read the deadlock counter (on MySQL the lock_deadlocks InnoDB metric must be enabled and the user needs the PROCESS privilege).');
}

/**
 * How many sessions are currently waiting for a lock held by another one.
 */
function workersBlockedOnLocks(): int
{
    $blocked = match (DB::connection()->getDriverName()) {
        'mysql' => DB::scalar("select count(*) from information_schema.INNODB_TRX where trx_state = 'LOCK WAIT'"),
        default => DB::scalar("select count(*) from pg_stat_activity where datname = current_database() and wait_event_type = 'Lock'"),
    };

    return is_numeric($blocked) ? (int) $blocked : throw new RuntimeException('Unable to count the workers blocked on locks.');
}
