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
                'CREATE UNIQUE INDEX referees_retirements_one_open_period_unique '
                .'ON referees_retirements (referee_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE referees_retirements '
                .'ADD COLUMN currently_retired_referee_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN referee_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX referees_retirements_one_open_period_unique (currently_retired_referee_id)'
            ),
            default => throw new LogicException('The database driver does not support open retirement period constraints.'),
        };
    }
};
