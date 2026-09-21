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
                'CREATE UNIQUE INDEX tag_teams_employments_one_open_period_unique '
                .'ON tag_teams_employments (tag_team_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE tag_teams_employments '
                .'ADD COLUMN current_tag_team_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN tag_team_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX tag_teams_employments_one_open_period_unique (current_tag_team_id)'
            ),
            default => throw new LogicException('The database driver does not support open employment period constraints.'),
        };
    }
};
