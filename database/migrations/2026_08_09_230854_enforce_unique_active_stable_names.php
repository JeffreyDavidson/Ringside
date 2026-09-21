<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateNames = DB::table('stables')
            ->whereNull('deleted_at')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        if ($duplicateNames->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce unique active stable names. Resolve duplicate active names first: '
                .$duplicateNames->join(', ')
            );
        }

        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $this->createFilteredUniqueIndex($connection),
            'mysql', 'mariadb' => $this->createGeneratedColumnUniqueIndex($connection),
            default => throw new LogicException('The active stable name invariant is not supported by this database driver.'),
        };
    }

    private function createFilteredUniqueIndex(Connection $connection): void
    {
        $connection->statement(
            'CREATE UNIQUE INDEX stables_active_name_unique ON stables (name) WHERE deleted_at IS NULL'
        );
    }

    private function createGeneratedColumnUniqueIndex(Connection $connection): void
    {
        $connection->statement(
            'ALTER TABLE stables '
            .'ADD COLUMN active_name VARCHAR(255) '
            .'GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN name ELSE NULL END) STORED, '
            .'ADD UNIQUE INDEX stables_active_name_unique (active_name)'
        );
    }
};
