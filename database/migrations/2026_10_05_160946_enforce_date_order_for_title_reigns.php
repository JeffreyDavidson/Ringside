<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureNoReignEndsBeforeItStarts();

        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite' => $this->createSqliteTriggers(),
            'pgsql', 'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE titles_championships ADD CONSTRAINT titles_championships_dates_ordered '
                .'CHECK (lost_at IS NULL OR won_at IS NULL OR lost_at >= won_at)'
            ),
            default => throw new LogicException('The database driver does not support date order constraints.'),
        };
    }

    /**
     * Abort before any schema change when a reign (soft-deleted ones included) already ends before it was won.
     *
     * Existing reigns are never rewritten: the operator decides which date is wrong.
     */
    private function ensureNoReignEndsBeforeItStarts(): void
    {
        $conflicts = DB::table('titles_championships')
            ->whereNotNull('lost_at')
            ->whereNotNull('won_at')
            ->whereColumn('lost_at', '<', 'won_at')
            ->orderBy('id')
            ->get(['id', 'title_id'])
            ->groupBy('title_id')
            ->map(fn ($reigns, int $titleId): string => sprintf(
                'title %d has reign ids %s',
                $titleId,
                $reigns->pluck('id')->implode(', '),
            ))
            ->implode('; ');

        if ($conflicts === '') {
            return;
        }

        throw new RuntimeException(
            "Cannot enforce date order: {$conflicts}. "
            .'No changes were made. Correct or delete these reigns (lost_at must not be before won_at), '
            .'then re-run "php artisan migrate".'
        );
    }

    /**
     * SQLite cannot add a constraint to an existing table, so it gets triggers instead of a table rebuild.
     * It stores dates as text, where a date-only value sorts before the same day with a time,
     * so datetime() normalizes both sides (and drops sub-second precision).
     */
    private function createSqliteTriggers(): void
    {
        $events = [
            'insert' => 'INSERT',
            'update' => 'UPDATE OF won_at, lost_at',
        ];

        foreach ($events as $suffix => $event) {
            DB::statement(
                "CREATE TRIGGER titles_championships_dates_ordered_{$suffix} BEFORE {$event} ON titles_championships "
                .'WHEN NEW.lost_at IS NOT NULL AND NEW.won_at IS NOT NULL '
                .'AND datetime(NEW.lost_at) < datetime(NEW.won_at) '
                ."BEGIN SELECT RAISE(ABORT, 'titles_championships: end before start'); END"
            );
        }
    }
};
