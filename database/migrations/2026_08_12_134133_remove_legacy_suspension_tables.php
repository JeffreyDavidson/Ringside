<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::drop('wrestlers_suspensions');
        Schema::drop('managers_suspensions');
        Schema::drop('referees_suspensions');
        Schema::drop('tag_teams_suspensions');

        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $connection->statement(
                'CREATE UNIQUE INDEX suspensions_one_open_period_unique '
                .'ON suspensions (suspendable_type, suspendable_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE suspensions '
                .'ADD COLUMN current_suspendable_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN suspendable_id ELSE NULL END) STORED, '
                .'ADD COLUMN current_suspendable_type VARCHAR(255) '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN suspendable_type ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX suspensions_one_open_period_unique '
                .'(current_suspendable_type, current_suspendable_id)'
            ),
            default => throw new LogicException('The database driver does not support open suspension period constraints.'),
        };
    }
};
