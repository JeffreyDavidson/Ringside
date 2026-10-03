<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let a lifecycle transition take effect on any date a user can enter, like the period columns it mirrors.
     *
     * effective_at was created with timestamp(). On MySQL that is a TIMESTAMP, which only holds 1970-01-01 to
     * 2038-01-19, so recording the employment of a wrestler hired before 1970 failed in strict mode. DATETIME has no
     * such limit. PostgreSQL (timestamp without time zone) and SQLite (datetime) already store both builder types the
     * same way, so the column is only changed on MySQL and MariaDB; on SQLite a change() would also rebuild the table.
     */
    public function up(): void
    {
        match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => Schema::table('lifecycle_transitions', function (Blueprint $table): void {
                $table->dateTime('effective_at')->change();
            }),
            'pgsql', 'sqlite' => null,
            default => throw new LogicException('The database driver is not supported.'),
        };
    }
};
