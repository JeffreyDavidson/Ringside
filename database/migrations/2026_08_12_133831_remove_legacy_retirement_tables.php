<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::drop('wrestlers_retirements');
        Schema::drop('managers_retirements');
        Schema::drop('referees_retirements');
        Schema::drop('tag_teams_retirements');
        Schema::drop('stables_retirements');
        Schema::drop('titles_retirements');

        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $connection->statement(
                'CREATE UNIQUE INDEX retirements_one_open_period_unique '
                .'ON retirements (retirable_type, retirable_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE retirements '
                .'ADD COLUMN current_retirable_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN retirable_id ELSE NULL END) STORED, '
                .'ADD COLUMN current_retirable_type VARCHAR(255) '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN retirable_type ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX retirements_one_open_period_unique '
                .'(current_retirable_type, current_retirable_id)'
            ),
            default => throw new LogicException('The database driver does not support open retirement period constraints.'),
        };
    }
};
