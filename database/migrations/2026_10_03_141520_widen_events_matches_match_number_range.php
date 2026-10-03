<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let an event number more than 255 matches.
     *
     * match_number was created with unsignedTinyInteger(). On MySQL that is a TINYINT UNSIGNED, which stops at 255,
     * and soft-deleted matches keep their numbers (numbers are never reused), so a busy event could reach the limit
     * and strict mode rejected the next match. SMALLINT UNSIGNED holds up to 65535. PostgreSQL already stores both
     * builder types as smallint and SQLite as integer, so the column is only changed on MySQL and MariaDB, where
     * MODIFY keeps the table's indexes and foreign keys; on SQLite a change() would also rebuild the table.
     */
    public function up(): void
    {
        match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => Schema::table('events_matches', function (Blueprint $table): void {
                $table->unsignedSmallInteger('match_number')->change();
            }),
            'pgsql', 'sqlite' => null,
            default => throw new LogicException('The database driver is not supported.'),
        };
    }
};
