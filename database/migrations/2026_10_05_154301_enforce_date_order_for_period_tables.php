<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Start and end columns of every period table, keyed by table name.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const PERIODS = [
        'employments' => ['started_at', 'ended_at'],
        'injuries' => ['started_at', 'ended_at'],
        'suspensions' => ['started_at', 'ended_at'],
        'retirements' => ['started_at', 'ended_at'],
        'activity_periods' => ['started_at', 'ended_at'],
        'stables_wrestlers' => ['joined_at', 'left_at'],
        'stables_tag_teams' => ['joined_at', 'left_at'],
        'tag_teams_wrestlers' => ['joined_at', 'left_at'],
        'wrestlers_managers' => ['hired_at', 'fired_at'],
        'tag_teams_managers' => ['hired_at', 'fired_at'],
    ];

    public function up(): void
    {
        $this->ensureNoPeriodEndsBeforeItStarts();

        foreach (self::PERIODS as $table => [$start, $end]) {
            $this->enforceDateOrder($table, $start, $end);
        }
    }

    /**
     * Abort before any schema change when a row already ends before it starts.
     *
     * Existing rows are never rewritten: the operator decides which date is wrong.
     */
    private function ensureNoPeriodEndsBeforeItStarts(): void
    {
        $violations = [];

        foreach (self::PERIODS as $table => [$start, $end]) {
            $ids = DB::table($table)
                ->whereNotNull($end)
                ->whereNotNull($start)
                ->whereColumn($end, '<', $start)
                ->orderBy('id')
                ->get(['id', $start, $end])
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                $violations[] = "{$table} ids {$ids->implode(', ')}";
            }
        }

        if ($violations === []) {
            return;
        }

        throw new RuntimeException(
            'Cannot enforce date order: '.implode('; ', $violations).'. '
            .'No changes were made. Correct or delete these rows (end must not be before start), '
            .'then re-run "php artisan migrate".'
        );
    }

    /**
     * Reject any row whose end is before its start; a NULL on either side and equal dates pass.
     *
     * SQLite cannot add a constraint to an existing table, so it gets triggers instead of a table rebuild.
     */
    private function enforceDateOrder(string $table, string $start, string $end): void
    {
        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite' => $this->createSqliteTriggers($table, $start, $end),
            'pgsql', 'mysql', 'mariadb' => $connection->statement(
                "ALTER TABLE {$table} ADD CONSTRAINT {$table}_dates_ordered "
                ."CHECK ({$end} IS NULL OR {$start} IS NULL OR {$end} >= {$start})"
            ),
            default => throw new LogicException('The database driver does not support date order constraints.'),
        };
    }

    /**
     * SQLite stores dates as text, where a date-only value sorts before the same day with a time,
     * so datetime() normalizes both sides (and drops sub-second precision).
     */
    private function createSqliteTriggers(string $table, string $start, string $end): void
    {
        $events = [
            'insert' => 'INSERT',
            'update' => "UPDATE OF {$start}, {$end}",
        ];

        foreach ($events as $suffix => $event) {
            DB::statement(
                "CREATE TRIGGER {$table}_dates_ordered_{$suffix} BEFORE {$event} ON {$table} "
                ."WHEN NEW.{$end} IS NOT NULL AND NEW.{$start} IS NOT NULL "
                ."AND datetime(NEW.{$end}) < datetime(NEW.{$start}) "
                ."BEGIN SELECT RAISE(ABORT, '{$table}: end before start'); END"
            );
        }
    }
};
