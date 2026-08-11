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
                'CREATE UNIQUE INDEX wrestlers_suspensions_one_open_period_unique '
                .'ON wrestlers_suspensions (wrestler_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE wrestlers_suspensions '
                .'ADD COLUMN currently_suspended_wrestler_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN wrestler_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX wrestlers_suspensions_one_open_period_unique (currently_suspended_wrestler_id)'
            ),
            default => throw new LogicException('The database driver does not support open suspension period constraints.'),
        };
    }
};
