<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Enums\Titles\TitleType;
use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Exceptions\Roster\TagTeams\CannotBeEstablishedException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/*
 * Opt-in PostgreSQL and MySQL checks that cascades lock the rows they iterate in ascending id order. SQLite ignores row
 * locks, so the order can only be proven against a real server. The two tests that force PostgreSQL planner plans are
 * PostgreSQL-only. Run with:
 *
 *   DB_CONNECTION=pgsql|mysql DB_HOST=127.0.0.1 DB_DATABASE=<scratch database> DB_USERNAME=... DB_PASSWORD=... \
 *   RUN_CONCURRENCY_TESTS=1 vendor/bin/pest --group=concurrency --no-coverage
 *
 * The scratch database is rebuilt with migrate:fresh afterwards; never point this at real data.
 *
 * The interleaving is forced instead of left to chance: a separate session holds one row lock (the gate), the workers
 * are started one at a time and each is allowed to block behind it, and releasing the gate lets them proceed in
 * arrival order. With unordered iteration the first worker then holds the gated row and waits for a row the second
 * already holds, which the server resolves as a deadlock.
 */

/**
 * Run the workers behind a row lock held by another session and return their results, in spec order.
 *
 * @param  array<int, array<string, mixed>>  $workers  Specs for cascade-worker.php, in the order they should queue
 * @param  array<int, mixed>  $gateBindings
 * @return array<int, array<mixed>>
 */
function runBehindGate(string $gateSql, array $gateBindings, array $workers): array
{
    $connection = config()->string('database.default');
    $gate = new PDO(
        sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            config()->string("database.connections.{$connection}.driver"),
            config()->string("database.connections.{$connection}.host"),
            config()->string("database.connections.{$connection}.port"),
            config()->string("database.connections.{$connection}.database"),
        ),
        config()->string("database.connections.{$connection}.username"),
        config()->string("database.connections.{$connection}.password"),
    );

    // The workers run at READ COMMITTED on MySQL (config/database.php); the gate only takes a row lock, but match it.
    if (runsOnDriver('mysql')) {
        $gate->query('set session transaction isolation level read committed');
    }

    $gate->beginTransaction();
    $gate->prepare($gateSql)->execute($gateBindings);

    $processes = [];

    try {
        foreach ($workers as $key => $worker) {
            $processes[$key] = new Process([PHP_BINARY, __DIR__.'/cascade-worker.php', json_encode($worker, JSON_THROW_ON_ERROR)]);
            $processes[$key]->setTimeout(120);
            $processes[$key]->start();

            $deadline = microtime(true) + 30;

            while (workersBlockedOnLocks() < count($processes) && $processes[$key]->isRunning() && microtime(true) < $deadline) {
                usleep(20_000);
            }
        }
    } finally {
        $gate->commit();
    }

    $results = [];

    foreach ($processes as $key => $process) {
        $process->wait();

        preg_match('/RESULT:(.*)/', $process->getOutput(), $matches);
        $result = json_decode($matches[1] ?? '', true);

        if (! is_array($result)) {
            throw new RuntimeException("Worker failed: {$process->getErrorOutput()}{$process->getOutput()}");
        }

        $results[$key] = $result;
    }

    return $results;
}

/** Leave the transaction RefreshDatabase wraps around a test so child processes can see the data, and rebuild afterwards. */
function committedScratchData(Closure $callback): void
{
    DB::commit();

    try {
        $callback();
    } finally {
        Artisan::call('migrate:fresh');
    }
}

test('two tag teams retiring together that share managers attached in opposite orders never deadlock', function () {
    committedScratchData(function (): void {
        // With few managers the planner scans them in id order whatever the pivot order is; a large managers table
        // makes it read the pivot rows first, as it does in production, so the members come back in pivot order.
        DB::statement("insert into managers (first_name, last_name, created_at, updated_at) select 'Padding', 'Manager '||number, now(), now() from generate_series(1, 20000) as number");
        DB::statement('analyze managers');

        foreach (range(1, 3) as $run) {
            // Arrange
            $firstManager = Manager::factory()->employed()->create();
            $secondManager = Manager::factory()->employed()->create();
            $firstTagTeam = TagTeam::factory()->employed()->create();
            $secondTagTeam = TagTeam::factory()->employed()->create();

            $firstTagTeam->managers()->attach($firstManager, ['hired_at' => now()->subMonth()]);
            $firstTagTeam->managers()->attach($secondManager, ['hired_at' => now()->subMonth()]);
            $secondTagTeam->managers()->attach($secondManager, ['hired_at' => now()->subMonth()]);
            $secondTagTeam->managers()->attach($firstManager, ['hired_at' => now()->subMonth()]);

            $deadlocksBefore = resolvedDeadlocks();

            // Act
            $results = runBehindGate(
                'select id from managers where id = ? for update',
                [$firstManager->id],
                [
                    ['action' => 'retire_tag_team', 'id' => $firstTagTeam->id, 'nested_loop_joins' => true],
                    ['action' => 'retire_tag_team', 'id' => $secondTagTeam->id, 'nested_loop_joins' => true],
                ],
            );

            // Assert
            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
                ->and($results[0]['ok'])->toBeTrue()
                ->and($results[1]['exception'])->toBeIn([null, CannotBeRetiredException::class])
                ->and($firstTagTeam->currentRetirement()->exists())->toBeTrue();
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->skip(fn (): bool => ! runsOnDriver('pgsql'), POSTGRES_PLANNER_ORDER)
    ->group('concurrency', 'postgres-concurrency');

test('retiring a champion of two titles while a multi-title result is recorded never deadlocks', function () {
    committedScratchData(function (): void {
        foreach (range(1, 3) as $run) {
            // Arrange
            $champion = Wrestler::factory()->bookable()->create();
            $challenger = Wrestler::factory()->bookable()->create();
            $titles = Title::factory()->active()->count(2)->create(['type' => TitleType::Singles]);
            $reigns = $titles->map(fn (Title $title): TitleChampionship => TitleChampionship::factory()
                ->for($title)
                ->forWrestler($champion)
                ->current()
                ->create(['won_at' => now()->subMonths(2)]));

            // Rewriting the first reign moves its live tuple behind the second, so a sequential scan returns them out of id order.
            $firstReign = $reigns->firstOrFail();
            DB::update('update titles_championships set updated_at = now() where id = ?', [$firstReign->id]);

            $match = EventMatch::factory()
                ->forEvent(Event::factory()->create(['date' => now()->subWeeks($run)]))
                ->create(['match_type' => MatchType::Singles]);
            $championSide = MatchSide::factory()->create(['match_id' => $match->id, 'position' => 1]);
            $challengerSide = MatchSide::factory()->create(['match_id' => $match->id, 'position' => 2]);
            $match->competitors()->create(['competitor_id' => $champion->id, 'competitor_type' => 'wrestler', 'match_side_id' => $championSide->id]);
            $match->competitors()->create(['competitor_id' => $challenger->id, 'competitor_type' => 'wrestler', 'match_side_id' => $challengerSide->id]);
            $match->referees()->attach(Referee::factory()->bookable()->create());
            $match->titles()->attach($titles->modelKeys());

            $deadlocksBefore = resolvedDeadlocks();

            // Act
            $results = runBehindGate(
                'select id from titles_championships where id = ? for update',
                [$firstReign->id],
                [
                    ['action' => 'record_result', 'id' => $match->id, 'winning_position' => 2],
                    ['action' => 'retire_wrestler', 'id' => $champion->id, 'sequential_scans' => true],
                ],
            );

            // Assert
            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
                ->and(collect($results)->where('ok', true))->toHaveCount(2)
                ->and(TitleChampionship::query()->forChampion($champion)->current()->exists())->toBeFalse();
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->skip(fn (): bool => ! runsOnDriver('pgsql'), POSTGRES_PLANNER_ORDER)
    ->group('concurrency', 'postgres-concurrency');

/*
 * Membership writers race for the same wrestler. The gate holds the wrestler's row lock, so both writers queue behind it
 * (on the old code, which takes no wrestler lock, they never block and the helper simply stops waiting). Whoever is
 * released first attaches the wrestler; the other must then see that membership and fail with the domain exception, not a
 * unique-index violation or a deadlock.
 */
dataset('tag team membership races', [
    'two creations sharing a wrestler' => [
        fn (Wrestler $shared, array $freeIds, TagTeam $teamOne, TagTeam $teamTwo): array => [
            ['action' => 'create_tag_team', 'id' => 0, 'name' => 'Race One', 'wrestler_ids' => [$shared->id, $freeIds[0]]],
            ['action' => 'create_tag_team', 'id' => 0, 'name' => 'Race Two', 'wrestler_ids' => [$shared->id, $freeIds[1]]],
        ],
    ],
    'an update racing a creation' => [
        fn (Wrestler $shared, array $freeIds, TagTeam $teamOne, TagTeam $teamTwo): array => [
            ['action' => 'update_tag_team', 'id' => $teamOne->id, 'name' => $teamOne->name, 'wrestler_ids' => [$teamOne->currentWrestlers()->firstOrFail()->id, $shared->id]],
            ['action' => 'create_tag_team', 'id' => 0, 'name' => 'Race Two', 'wrestler_ids' => [$shared->id, $freeIds[1]]],
        ],
    ],
    'two updates adding the same wrestler to different tag teams' => [
        fn (Wrestler $shared, array $freeIds, TagTeam $teamOne, TagTeam $teamTwo): array => [
            ['action' => 'update_tag_team', 'id' => $teamOne->id, 'name' => $teamOne->name, 'wrestler_ids' => [$teamOne->currentWrestlers()->firstOrFail()->id, $shared->id]],
            ['action' => 'update_tag_team', 'id' => $teamTwo->id, 'name' => $teamTwo->name, 'wrestler_ids' => [$teamTwo->currentWrestlers()->firstOrFail()->id, $shared->id]],
        ],
    ],
]);

test('concurrent tag team membership writes leave a wrestler on exactly one current tag team', function (Closure $workers) {
    committedScratchData(function () use ($workers): void {
        // Arrange
        $shared = Wrestler::factory()->create();
        $free = Wrestler::factory()->count(2)->create();
        $teamOne = TagTeam::factory()->employed()->create();
        $teamTwo = TagTeam::factory()->employed()->create();
        $deadlocksBefore = resolvedDeadlocks();

        // Act
        $results = runBehindGate('select id from wrestlers where id = ? for update', [$shared->id], $workers($shared, $free->modelKeys(), $teamOne, $teamTwo));

        // Assert
        expect(collect($results)->pluck('message')->filter()->values()->all())->toBeEmpty()
            ->and(collect($results)->pluck('exception')->sort()->values()->all())->toBe([null, CannotBeEstablishedException::class])
            ->and(TagTeamWrestler::query()->current()->where('wrestler_id', $shared->id)->count())->toBe(1);
    });
})->with('tag team membership races')
    ->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->group('concurrency', 'postgres-concurrency');
