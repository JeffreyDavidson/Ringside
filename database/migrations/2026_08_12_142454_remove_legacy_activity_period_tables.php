<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::drop('stables_activations');
        Schema::drop('titles_activations');

        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $connection->statement(
                'CREATE UNIQUE INDEX activity_periods_one_open_period_unique '
                .'ON activity_periods (activeable_type, activeable_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE activity_periods '
                .'ADD COLUMN current_activeable_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN activeable_id ELSE NULL END) STORED, '
                .'ADD COLUMN current_activeable_type VARCHAR(255) '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN activeable_type ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX activity_periods_one_open_period_unique '
                .'(current_activeable_type, current_activeable_id)'
            ),
            default => throw new LogicException('The database driver does not support open activity-period constraints.'),
        };
    }
};
