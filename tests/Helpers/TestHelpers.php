<?php

declare(strict_types=1);

use App\Data\Stables\StableMembershipData;
use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Lifecycle\Roster\Stables\StableFormerMemberEligibility;
use App\Models\Lifecycle\Employment;
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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/**
 * Test helper functions for common testing scenarios.
 *
 * These functions provide convenient methods for creating test data,
 * setting up common test scenarios, and performing repetitive test operations.
 */

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
 * The former members of a stable that can return to it, as the reunite action expects them.
 */
function formerMembersOf(Stable $stable): StableMembershipData
{
    return resolve(StableFormerMemberEligibility::class)->availableMembersFor($stable);
}

/**
 * Simulate a concurrent request: just before the next stable membership insert for the wrestler, another stable
 * claims them, so the partial unique index rejects the insert as it would for the losing request of a race.
 */
function rivalStableClaimsWrestlerOnNextMembershipInsert(Wrestler $wrestler): void
{
    $rivalStable = Stable::factory()->create();
    $claimed = false;

    DB::beforeExecuting(function (string $query) use ($wrestler, $rivalStable, &$claimed): void {
        if ($claimed || ! str_starts_with(mb_strtolower($query), 'insert into') || ! str_contains($query, 'stables_wrestlers')) {
            return;
        }

        $claimed = true;

        DB::table('stables_wrestlers')->insert([
            'stable_id' => $rivalStable->getKey(),
            'wrestler_id' => $wrestler->getKey(),
            'joined_at' => now(),
            'left_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });
}

/**
 * Move a stable's current and former members into the stable's promotion, so a promotion-scoped user can see and move them.
 */
function putStableMembersInPromotion(Stable $stable): Stable
{
    $stable->wrestlers()->withoutGlobalScopes()->update(['wrestlers.promotion_id' => $stable->promotion_id]);
    $stable->tagTeams()->withoutGlobalScopes()->update(['tag_teams.promotion_id' => $stable->promotion_id]);

    return $stable;
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
 * Act as a request inside the promotion: the records created next belong to it and its name checks are scoped to it.
 */
function enforcePromotionContext(Promotion $promotion): void
{
    $context = resolve(PromotionContextService::class);
    $context->set($promotion);
    $context->enforce();
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
 * The reason a test that relies on the database rejecting duplicate unowned stable names is skipped on MySQL.
 */
const MYSQL_UNOWNED_STABLE_NAMES = 'MySQL has no partial index for active stables without a promotion; the split name lock (StableNameLock) with the split eligibility check, and form validation, are the guard there (migration 2026_10_01_190000).';

/**
 * The reason the concurrent split test is skipped away from MySQL: on PostgreSQL and SQLite the partial unique index makes
 * the losing split fail with a QueryException instead, so the test would not tell the name lock apart from the index.
 */
const MYSQL_CONCURRENT_STABLE_SPLITS = 'Only MySQL lacks a unique index over active stables without a promotion, so only there does the name lock decide the race; set DB_CONNECTION=mysql and RUN_CONCURRENCY_TESTS=1.';

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
 * Deadlocks the database resolved since $before was read with resolvedDeadlocks().
 *
 * PostgreSQL must resolve none: lock ordering is meant to rule them out. MySQL (InnoDB) can still pick a victim when
 * two transactions race on a new row of the slot lock table; Laravel retries the victim transparently, so the
 * outcome assertions in each test (and the workers' own deadlock flag) are what prove no deadlock reaches a caller,
 * and the counter is not asserted there.
 */
function deadlocksResolvedSince(int $before): int
{
    return runsOnDriver('mysql') ? 0 : resolvedDeadlocks() - $before;
}

/** What the database says about lock waits right now, for failure messages when blocked workers are not seen. */
function lockWaitDiagnostics(): string
{
    if (! runsOnDriver('mysql')) {
        return 'not mysql';
    }

    return json_encode([
        'user' => DB::scalar('select current_user()'),
        'trx' => DB::select('select trx_id, trx_state, trx_mysql_thread_id, substr(trx_query, 1, 120) as q from information_schema.INNODB_TRX'),
        'data_lock_waits' => DB::scalar('select count(*) from performance_schema.data_lock_waits'),
        'processlist' => DB::select('select id, user, command, state, substr(info, 1, 100) as info from information_schema.PROCESSLIST'),
    ], JSON_THROW_ON_ERROR);
}

/**
 * How many sessions are currently waiting for a lock held by another one.
 *
 * On MySQL the waiting sessions come from performance_schema.data_lock_waits: a worker blocked on its first locking
 * read does not show as LOCK WAIT in information_schema.INNODB_TRX.
 */
function workersBlockedOnLocks(): int
{
    $blocked = match (DB::connection()->getDriverName()) {
        'mysql' => DB::scalar('select count(distinct REQUESTING_ENGINE_TRANSACTION_ID) from performance_schema.data_lock_waits'),
        default => DB::scalar("select count(*) from pg_stat_activity where datname = current_database() and wait_event_type = 'Lock'"),
    };

    return is_numeric($blocked) ? (int) $blocked : throw new RuntimeException('Unable to count the workers blocked on locks.');
}

/**
 * Give a roster record a pinned employment history, for a test whose clock is travelled to 2024-06-01.
 *
 * "released" ended its only employment, "retired" also has an open retirement, and "future" is employed from a later date.
 */
function giveEmploymentHistory(Wrestler|Manager|Referee|TagTeam $entity, string $state): void
{
    if ($state === 'future') {
        $entity->employments()->create(['started_at' => '2024-09-01']);

        return;
    }

    $entity->employments()->create(['started_at' => '2024-01-15', 'ended_at' => '2024-03-01']);

    if ($state === 'retired') {
        $entity->retirements()->create(['started_at' => '2024-03-01']);
    }
}

/**
 * The record's employment rows as plain values, to prove an edit left them untouched.
 *
 * @return array<int, array{id: int, started_at: string, ended_at: ?string}>
 */
function employmentSnapshot(Wrestler|Manager|Referee|TagTeam $entity): array
{
    return $entity->employments()
        ->orderBy('id')
        ->get()
        ->map(fn (Employment $employment): array => [
            'id' => $employment->id,
            'started_at' => $employment->started_at->toDateString(),
            'ended_at' => $employment->ended_at?->toDateString(),
        ])
        ->values()
        ->all();
}

/**
 * Load config/<file>.php with the given environment variables set, or unset when null, and restore them afterwards.
 *
 * @param  array<string, ?string>  $variables
 * @return array<string, mixed>
 */
function configFileWith(string $file, array $variables): array
{
    $previous = [];

    foreach ($variables as $name => $value) {
        $previous[$name] = [$_SERVER[$name] ?? null, $_ENV[$name] ?? null, getenv($name)];
        unset($_SERVER[$name], $_ENV[$name]);
        putenv($name);

        if ($value !== null) {
            $_SERVER[$name] = $value;
            $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }

    try {
        return require config_path("{$file}.php");
    } finally {
        foreach ($previous as $name => [$server, $env, $process]) {
            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);

            if ($server !== null) {
                $_SERVER[$name] = $server;
            }

            if ($env !== null) {
                $_ENV[$name] = $env;
            }

            if ($process !== false) {
                putenv("{$name}={$process}");
            }
        }
    }
}

/**
 * Leave the transaction RefreshDatabase wraps around a test so child processes can see the data,
 * and rebuild the scratch database afterwards.
 */
function withCommittedData(Closure $callback): void
{
    DB::commit();

    try {
        $callback();
    } finally {
        Artisan::call('migrate:fresh');
    }
}

/**
 * @return list<string> The SQL of every query issued by the callback.
 */
function queriesDuring(Closure $callback): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $queries = array_column(DB::getQueryLog(), 'query');
    DB::disableQueryLog();

    return $queries;
}

function promotionHasActiveMember(Promotion $promotion, User $user): bool
{
    return $promotion->memberships()
        ->forUser($user)
        ->active()
        ->exists();
}

function promotionHasMemberWithRole(Promotion $promotion, User $user, MembershipRole ...$roles): bool
{
    return $promotion->memberships()
        ->forUser($user)
        ->active()
        ->withRole(...$roles)
        ->exists();
}
