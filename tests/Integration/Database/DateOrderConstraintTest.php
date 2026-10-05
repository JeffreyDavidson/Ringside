<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
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
        'titles_championships' => ['title_id' => Title::factory()->create()->id, 'champion_type' => 'wrestler', 'champion_id' => Wrestler::factory()->create()->id],
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
        'titles_championships' => ['titles_championships', 'won_at', 'lost_at'],
    ];
}

dataset('periodTables', fn (): array => periodTableDataset());

test('a period ending before it starts cannot be inserted', function (string $table, string $start, string $end) {
    // Arrange
    $columns = periodRowColumns($table);

    // Act
    $insert = fn () => DB::transaction(fn () => DB::table($table)->insert([
        ...$columns,
        $start => '2026-03-10 12:00:00',
        $end => '2026-03-09 12:00:00',
    ]));

    // Assert
    expect($insert)->toThrow(QueryException::class);
})->with('periodTables');

test('a period cannot be updated to end before it starts', function (string $table, string $start, string $end) {
    // Arrange
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-11 12:00:00']);
    $id = DB::table($table)->value('id');

    // Act
    $update = fn () => DB::transaction(fn () => DB::table($table)->where('id', $id)->update([$end => '2026-03-09 12:00:00']));

    // Assert
    expect($update)->toThrow(QueryException::class);
})->with('periodTables');

test('a period cannot be updated to start after it ends', function (string $table, string $start, string $end) {
    // Arrange
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-11 12:00:00']);
    $id = DB::table($table)->value('id');

    // Act
    $update = fn () => DB::transaction(fn () => DB::table($table)->where('id', $id)->update([$start => '2026-03-12 12:00:00']));

    // Assert
    expect($update)->toThrow(QueryException::class);
})->with('periodTables');

test('a period may end on the same moment it starts', function (string $table, string $start, string $end) {
    // Act
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-10 12:00:00']);

    // Assert
    expect(DB::table($table)->count())->toBe(1);
})->with('periodTables');

test('a period may end later than it starts', function (string $table, string $start, string $end) {
    // Act
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => '2026-03-10 12:00:01']);

    // Assert
    expect(DB::table($table)->count())->toBe(1);
})->with('periodTables');

test('an open period passes', function (string $table, string $start, string $end) {
    // Act
    DB::table($table)->insert([...periodRowColumns($table), $start => '2026-03-10 12:00:00', $end => null]);

    // Assert
    expect(DB::table($table)->count())->toBe(1);
})->with('periodTables');

test('a membership without a start date may still have an end date', function () {
    // Act
    DB::table('tag_teams_wrestlers')->insert([...periodRowColumns('tag_teams_wrestlers'), 'joined_at' => null, 'left_at' => '2026-03-10 12:00:00']);

    // Assert
    expect(DB::table('tag_teams_wrestlers')->count())->toBe(1);
});

test('a soft-deleted reign ending before it was won is rejected too', function () {
    // Arrange
    $columns = periodRowColumns('titles_championships');

    // Act
    $insert = fn () => DB::transaction(fn () => DB::table('titles_championships')->insert([
        ...$columns,
        'won_at' => '2026-03-10 12:00:00',
        'lost_at' => '2026-03-09 12:00:00',
        'deleted_at' => '2026-03-11 12:00:00',
    ]));

    // Assert
    expect($insert)->toThrow(QueryException::class);
});

test('the reign migration lists the titles with inverted reigns, deleted ones included, before changing anything', function () {
    // Arrange
    dropDateOrderConstraints();
    $firstTitle = Title::factory()->create();
    $secondTitle = Title::factory()->create();
    $champion = ['champion_type' => 'wrestler', 'champion_id' => Wrestler::factory()->create()->id];
    $firstColumns = ['title_id' => $firstTitle->id, ...$champion];
    $secondColumns = ['title_id' => $secondTitle->id, ...$champion];
    $first = DB::table('titles_championships')->insertGetId([...$firstColumns, 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-09 12:00:00']);
    DB::table('titles_championships')->insert([...$firstColumns, 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-10 12:00:00']);
    $second = DB::table('titles_championships')->insertGetId([...$firstColumns, 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-08 12:00:00', 'deleted_at' => '2026-03-11 12:00:00']);
    $third = DB::table('titles_championships')->insertGetId([...$secondColumns, 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-01 12:00:00']);
    $migration = require database_path('migrations/2026_10_05_160946_enforce_date_order_for_title_reigns.php');
    $up = new ReflectionMethod($migration, 'up');

    // Act
    $runMigration = fn () => $up->invoke($migration);

    // Assert
    expect($runMigration)
        ->toThrow(
            RuntimeException::class,
            "title {$firstTitle->id} has reign ids {$first}, {$second}; title {$secondTitle->id} has reign ids {$third}. No changes were made.",
        );

    $invertedReign = fn (): bool => DB::table('titles_championships')->insert([...periodRowColumns('titles_championships'), 'won_at' => '2026-03-10 12:00:00', 'lost_at' => '2026-03-09 12:00:00']);
    expect($invertedReign())->toBeTrue();
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_IMPLICIT_COMMIT);

test('the migration lists every row that ends before it starts before changing anything', function () {
    // Arrange
    dropDateOrderConstraints();
    $firstEmployment = DB::table('employments')->insertGetId([...periodRowColumns('employments'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-09 12:00:00']);
    DB::table('employments')->insert([...periodRowColumns('employments'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-10 12:00:00']);
    $secondEmployment = DB::table('employments')->insertGetId([...periodRowColumns('employments'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-08 12:00:00']);
    $managerPeriod = DB::table('wrestlers_managers')->insertGetId([...periodRowColumns('wrestlers_managers'), 'hired_at' => '2026-03-10 12:00:00', 'fired_at' => '2026-03-01 12:00:00']);
    $migration = require database_path('migrations/2026_10_05_154301_enforce_date_order_for_period_tables.php');
    $up = new ReflectionMethod($migration, 'up');

    // Act
    $runMigration = fn () => $up->invoke($migration);

    // Assert
    expect($runMigration)
        ->toThrow(
            RuntimeException::class,
            "employments ids {$firstEmployment}, {$secondEmployment}; wrestlers_managers ids {$managerPeriod}. No changes were made.",
        );

    $invertedInjury = fn (): bool => DB::table('injuries')->insert([...periodRowColumns('injuries'), 'started_at' => '2026-03-10 12:00:00', 'ended_at' => '2026-03-09 12:00:00']);
    expect($invertedInjury())->toBeTrue();
})->skip(fn (): bool => runsOnDriver('mysql'), MYSQL_IMPLICIT_COMMIT);
