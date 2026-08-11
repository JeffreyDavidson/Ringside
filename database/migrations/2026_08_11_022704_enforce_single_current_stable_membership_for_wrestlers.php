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
                'CREATE UNIQUE INDEX stables_wrestlers_one_current_membership_unique '
                .'ON stables_wrestlers (wrestler_id) WHERE left_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE stables_wrestlers '
                .'ADD COLUMN current_wrestler_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN left_at IS NULL THEN wrestler_id ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX stables_wrestlers_one_current_membership_unique (current_wrestler_id)'
            ),
            default => throw new LogicException('The database driver does not support current stable membership constraints.'),
        };
    }
};
