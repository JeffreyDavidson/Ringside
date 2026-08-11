<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $connection->statement(
                'CREATE UNIQUE INDEX wrestlers_injuries_one_open_period_unique '
                .'ON wrestlers_injuries (wrestler_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE wrestlers_injuries '
                .'ADD COLUMN currently_injured_wrestler_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN wrestler_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX wrestlers_injuries_one_open_period_unique (currently_injured_wrestler_id)'
            ),
            default => throw new LogicException('The database driver does not support open injury period constraints.'),
        };
    }
};
