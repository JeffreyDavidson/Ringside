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
                'CREATE UNIQUE INDEX stables_tag_teams_one_current_membership_unique '
                .'ON stables_tag_teams (tag_team_id) WHERE left_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE stables_tag_teams '
                .'ADD COLUMN current_tag_team_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN left_at IS NULL THEN tag_team_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX stables_tag_teams_one_current_membership_unique (current_tag_team_id)'
            ),
            default => throw new LogicException('The database driver does not support current stable membership constraints.'),
        };
    }
};
