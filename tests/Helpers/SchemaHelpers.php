<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Helpers for the database constraint and migration tests.
 */

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
 * Removes the date order triggers (SQLite) or constraints (PostgreSQL, MySQL) so violating rows can be created.
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

const CURRENT_TAG_TEAM_MEMBERSHIP_INDEX = 'tag_teams_wrestlers_one_current_membership_unique';

function currentTagTeamMembershipIndexExists(): bool
{
    return collect(Schema::getIndexes('tag_teams_wrestlers'))->contains('name', CURRENT_TAG_TEAM_MEMBERSHIP_INDEX);
}

function joinTagTeam(TagTeam $tagTeam, Wrestler $wrestler, ?DateTimeInterface $leftAt = null): TagTeamWrestler
{
    return TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => now()->subMonth(),
        'left_at' => $leftAt,
    ]);
}

/**
 * Drops the index (and, on MySQL, the generated column behind it) that enforces a rule, so the migration under
 * test can be shown rows that violate the rule.
 *
 * MySQL has no partial indexes: the migrations enforce these rules with a unique index over a STORED generated
 * column, and DROP INDEX there needs the table name. Pass the generated column when the migration under test
 * adds that column itself.
 */
function dropEnforcingIndex(string $table, string $index, ?string $mysqlGeneratedColumn = null): void
{
    if (! runsOnDriver('mysql')) {
        DB::statement("DROP INDEX {$index}");

        return;
    }

    DB::statement(match ($mysqlGeneratedColumn) {
        null => "ALTER TABLE {$table} DROP INDEX {$index}",
        default => "ALTER TABLE {$table} DROP COLUMN {$mysqlGeneratedColumn}",
    });
}

/**
 * Drops the index that keeps active stable names unique, so the migrations under test can be shown duplicates.
 *
 * On MySQL that unique (promotion_id, active_name) index is the only index on stables.promotion_id, and MySQL
 * refuses to drop an index a foreign key needs, so a plain promotion_id index is added first.
 */
function dropActiveStableNameIndex(): void
{
    if (! runsOnDriver('mysql')) {
        DB::statement('DROP INDEX stables_active_name_unique');

        return;
    }

    DB::statement('ALTER TABLE stables ADD INDEX stables_promotion_id_index (promotion_id)');
    DB::statement('ALTER TABLE stables DROP INDEX stables_active_name_unique');
}

/**
 * The index that keeps active unowned stable names unique exists on SQLite and PostgreSQL only: MySQL cannot index NULL promotions.
 */
function dropUnownedStableNameIndex(): void
{
    if (runsOnDriver('mysql')) {
        return;
    }

    DB::statement('DROP INDEX stables_active_unowned_name_unique');
}

/**
 * Rebuilds every table from the migrations, committing the result.
 *
 * Used by tests under tests/Integration/DatabaseMigrations, which change the schema (DDL) and so cannot run
 * inside the RefreshDatabase transaction: MySQL commits implicitly on DDL.
 */
function rebuildDatabaseSchema(): void
{
    app(Kernel::class)->call('migrate:fresh');
    app(Kernel::class)->setArtisan(null);
}

function databaseIsInMemory(): bool
{
    return DB::connection()->getDatabaseName() === ':memory:';
}
