<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();

        $this->ensureNoTitleHasSeveralOpenReigns();

        // The unique index only covers live open reigns, so a soft-deleted open reign never blocks a new one.
        match ($connection->getDriverName()) {
            'sqlite', 'pgsql' => $connection->statement(
                'CREATE UNIQUE INDEX titles_championships_one_open_reign_unique '
                .'ON titles_championships (title_id) WHERE lost_at IS NULL AND deleted_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE titles_championships '
                .'ADD COLUMN open_reign_title_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN lost_at IS NULL AND deleted_at IS NULL THEN title_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX titles_championships_one_open_reign_unique (open_reign_title_id)'
            ),
            default => throw new LogicException('The database driver does not support open reign constraints.'),
        };
    }

    /**
     * Abort before any schema change when a title already has more than one live open reign.
     *
     * Existing reigns are never rewritten: the operator decides which reign is the real one.
     */
    private function ensureNoTitleHasSeveralOpenReigns(): void
    {
        $conflicts = DB::table('titles_championships')
            ->whereNull('lost_at')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'title_id'])
            ->groupBy('title_id')
            ->filter(fn ($reigns): bool => $reigns->count() > 1)
            ->map(fn ($reigns, int $titleId): string => sprintf(
                'title %d has open reign ids %s',
                $titleId,
                $reigns->pluck('id')->implode(', '),
            ))
            ->implode('; ');

        if ($conflicts === '') {
            return;
        }

        throw new RuntimeException(
            "Cannot enforce one open reign per title: {$conflicts}. "
            .'No changes were made. End or delete all but one open reign for each title, '
            .'then re-run "php artisan migrate".'
        );
    }
};
