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
                'CREATE UNIQUE INDEX tag_teams_retirements_one_open_period_unique '
                .'ON tag_teams_retirements (tag_team_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE tag_teams_retirements '
                .'ADD COLUMN currently_retired_tag_team_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN tag_team_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX tag_teams_retirements_one_open_period_unique (currently_retired_tag_team_id)'
            ),
            default => throw new LogicException('The database driver does not support open retirement period constraints.'),
        };
    }
};
