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
                'CREATE UNIQUE INDEX titles_activations_one_open_period_unique '
                .'ON titles_activations (title_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE titles_activations '
                .'ADD COLUMN currently_active_title_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN title_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX titles_activations_one_open_period_unique (currently_active_title_id)'
            ),
            default => throw new LogicException('The database driver does not support open title activity period constraints.'),
        };
    }
};
