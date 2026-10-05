<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Builds the minimum NOT NULL columns of a row in each period table (parents created through factories).
 *
 * @return array<string, mixed>
 */
function periodRowColumns(string $table): array
{
    return match ($table) {
        'employments' => ['employable_type' => 'wrestler', 'employable_id' => Wrestler::factory()->create()->id],
        'injuries' => ['injurable_type' => 'wrestler', 'injurable_id' => Wrestler::factory()->create()->id],
        'suspensions' => ['suspendable_type' => 'wrestler', 'suspendable_id' => Wrestler::factory()->create()->id],
        'retirements' => ['retirable_type' => 'wrestler', 'retirable_id' => Wrestler::factory()->create()->id],
        'activity_periods' => ['activeable_type' => 'stable', 'activeable_id' => Stable::factory()->create()->id],
        'stables_wrestlers' => ['stable_id' => Stable::factory()->create()->id, 'wrestler_id' => Wrestler::factory()->create()->id],
        'stables_tag_teams' => ['stable_id' => Stable::factory()->create()->id, 'tag_team_id' => TagTeam::factory()->create()->id],
        'tag_teams_wrestlers' => ['tag_team_id' => TagTeam::factory()->create()->id, 'wrestler_id' => Wrestler::factory()->create()->id],
        'wrestlers_managers' => ['wrestler_id' => Wrestler::factory()->create()->id, 'manager_id' => Manager::factory()->create()->id],
        'tag_teams_managers' => ['tag_team_id' => TagTeam::factory()->create()->id, 'manager_id' => Manager::factory()->create()->id],
        default => throw new InvalidArgumentException("Unknown period table {$table}"),
    };
}

/**
 * Removes the date order triggers (SQLite) or constraints (PostgreSQL) so violating rows can be created.
 */
function dropDateOrderConstraints(): void
{
    foreach (periodTableDataset() as [$table]) {
        if (runsOnDriver('sqlite')) {
            DB::statement("DROP TRIGGER {$table}_dates_ordered_insert");
            DB::statement("DROP TRIGGER {$table}_dates_ordered_update");

            continue;
        }

        DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$table}_dates_ordered");
    }
}

/**
 * @return array<string, array{0: string, 1: string, 2: string}>
 */
function periodTableDataset(): array
{
    return [
        'employments' => ['employments', 'started_at', 'ended_at'],
        'injuries' => ['injuries', 'started_at', 'ended_at'],
        'suspensions' => ['suspensions', 'started_at', 'ended_at'],
        'retirements' => ['retirements', 'started_at', 'ended_at'],
        'activity_periods' => ['activity_periods', 'started_at', 'ended_at'],
        'stables_wrestlers' => ['stables_wrestlers', 'joined_at', 'left_at'],
        'stables_tag_teams' => ['stables_tag_teams', 'joined_at', 'left_at'],
        'tag_teams_wrestlers' => ['tag_teams_wrestlers', 'joined_at', 'left_at'],
        'wrestlers_managers' => ['wrestlers_managers', 'hired_at', 'fired_at'],
        'tag_teams_managers' => ['tag_teams_managers', 'hired_at', 'fired_at'],
    ];
}

dataset('periodTables', fn (): array => periodTableDataset());

test('a period ending before it starts cannot be inserted', function (string $table, string $start, string $end) {
    $columns = periodRowColumns($table);

    expect(fn () => DB::transaction(fn () => DB::table($table)->insert([
        ...$columns,
        $start => '2026-03-10 12:00:00',
        $end => '2026-03-09 12:00:00',
    ])))->toThrow(QueryException::class);
})->with('periodTables');

test('a period cannot be updated to end before it starts', function (string $table, string $start, string $end) {
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-11 12:00:00']);
    $id = DB::table($table)->value('id');

    expect(fn () => DB::transaction(fn () => DB::table($table)->where('id', $id)->update([$end => '2026-03-09 12:00:00'])))
        ->toThrow(QueryException::class);
})->with('periodTables');

test('a period cannot be updated to start after it ends', function (string $table, string $start, string $end) {
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-11 12:00:00']);
    $id = DB::table($table)->value('id');

    expect(fn () => DB::transaction(fn () => DB::table($table)->where('id', $id)->update([$start => '2026-03-12 12:00:00'])))
        ->toThrow(QueryException::class);
})->with('periodTables');

test('a period may end on the same moment it starts', function (string $table, string $start, string $end) {
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-10 12:00:00']);

    expect(DB::table($table)->count())->toBe(1);
})->with('periodTables');

test('a period may end later than it starts', function (string $table, string $start, string $end) {
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-10 12:00:01']);

    expect(DB::table($table)->count())->toBe(1);
})->with('periodTables');

test('an open period passes', function (string $table, string $start, string $end) {
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => null]);

    expect(DB::table($table)->count())->toBe(1);
})->with('periodTables');

test('a membership without a start date may still have an end date', function () {
    DB::table('tag_teams_wrestlers')->insert([...periodRowColumns('tag_teams_wrestlers'), 'joined_at' => null, 'left_at' => '2026-03-10 12:00:00']);

    expect(DB::table('tag_teams_wrestlers')->count())->toBe(1);
});

test('the migration lists every row that ends before it starts before changing anything', function () {
    dropDateOrderConstraints();
    $firstEmployment = DB::table('employments')->insertGetId([...periodRowColumns('employments'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-09 12:00:00']);
    DB::table('employments')->insert([...periodRowColumns('employments'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-10 12:00:00']);
    $secondEmployment = DB::table('employments')->insertGetId([...periodRowColumns('employments'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-08 12:00:00']);
    $managerPeriod = DB::table('wrestlers_managers')->insertGetId([...periodRowColumns('wrestlers_managers'), 'hired_at' => '2026-03-10 12:00:00', 'fired_at' => '2026-03-01 12:00:00']);
    $migration = require database_path('migrations/2026_10_05_154301_enforce_date_order_for_period_tables.php');
    $up = new ReflectionMethod($migration, 'up');

    expect(fn () => $up->invoke($migration))
        ->toThrow(
            RuntimeException::class,
            "employments ids {$firstEmployment}, {$secondEmployment}; wrestlers_managers ids {$managerPeriod}. No changes were made.",
        );

    $invertedInjury = fn (): bool => DB::table('injuries')->insert([...periodRowColumns('injuries'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-09 12:00:00']);
    expect($invertedInjury())->toBeTrue();
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_IMPLICIT_COMMIT);
