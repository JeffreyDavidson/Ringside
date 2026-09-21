<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $this->createFilteredUniqueIndex(DB::connection()),
            'mysql', 'mariadb' => $this->createGeneratedColumnUniqueIndex(DB::connection()),
            default => throw new LogicException('The database driver does not support the stable activity-period invariant.'),
        };
    }

    private function createFilteredUniqueIndex(Connection $connection): void
    {
        $connection->statement(
            'CREATE UNIQUE INDEX stables_activations_one_open_period_unique '
            .'ON stables_activations (stable_id) WHERE ended_at IS NULL'
        );
    }

    private function createGeneratedColumnUniqueIndex(Connection $connection): void
    {
        $connection->statement(
            'ALTER TABLE stables_activations '
            .'ADD COLUMN open_stable_id BIGINT UNSIGNED '
            .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN stable_id ELSE NULL END) STORED, '
            .'ADD UNIQUE INDEX stables_activations_one_open_period_unique (open_stable_id)'
        );
    }
};
