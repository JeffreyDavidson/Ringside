<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Stable names are unique per promotion instead of globally.
     *
     * Only indexes are replaced (DROP INDEX / CREATE UNIQUE INDEX); the stables table is not rebuilt, so no
     * CHECK constraint or other index is touched on SQLite.
     *
     * NULL promotion_id: unique indexes treat NULLs as distinct, so the composite index alone would stop
     * protecting unowned (legacy) stables. On SQLite, PostgreSQL and SQL Server a second filtered index keeps
     * active unowned stable names unique among themselves. On MySQL and MariaDB the composite index cannot cover
     * unowned stables; the form validation (which scopes to promotion_id IS NULL) remains the guard there.
     */
    public function up(): void
    {
        $this->ensureNoDuplicateActiveNamesPerPromotion();

        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $this->replaceFilteredIndex($connection),
            'mysql', 'mariadb' => $this->replaceGeneratedColumnIndex($connection),
            default => throw new LogicException('The active stable name invariant is not supported by this database driver.'),
        };
    }

    private function replaceFilteredIndex(Connection $connection): void
    {
        $connection->statement('DROP INDEX stables_active_name_unique');
        $connection->statement(
            'CREATE UNIQUE INDEX stables_active_name_unique ON stables (promotion_id, name) WHERE deleted_at IS NULL'
        );
        $connection->statement(
            'CREATE UNIQUE INDEX stables_active_unowned_name_unique ON stables (name) '
            .'WHERE deleted_at IS NULL AND promotion_id IS NULL'
        );
    }

    private function replaceGeneratedColumnIndex(Connection $connection): void
    {
        $connection->statement(
            'ALTER TABLE stables DROP INDEX stables_active_name_unique, '
            .'ADD UNIQUE INDEX stables_active_name_unique (promotion_id, active_name)'
        );
    }

    /**
     * Abort before any schema change when existing data would violate the new indexes.
     *
     * Stables are never modified: the operator decides which duplicate to rename or delete.
     */
    private function ensureNoDuplicateActiveNamesPerPromotion(): void
    {
        $duplicates = DB::table('stables')
            ->whereNull('deleted_at')
            ->groupBy('promotion_id', 'name')
            ->havingRaw('count(*) > 1')
            ->get(['promotion_id', 'name']);

        if ($duplicates->isEmpty()) {
            return;
        }

        $conflicts = $duplicates
            ->map(function (object $duplicate): string {
                $ids = DB::table('stables')
                    ->whereNull('deleted_at')
                    ->where('promotion_id', $duplicate->promotion_id)
                    ->where('name', $duplicate->name)
                    ->orderBy('id')
                    ->pluck('id')
                    ->implode(', ');

                return sprintf('"%s" in promotion %s (stable ids %s)', $duplicate->name, $duplicate->promotion_id ?? 'none', $ids);
            })
            ->implode('; ');

        throw new RuntimeException(
            "Cannot enforce unique active stable names per promotion: {$conflicts}. "
            .'No changes were made. Rename or delete the duplicate stables, then re-run "php artisan migrate".'
        );
    }
};
