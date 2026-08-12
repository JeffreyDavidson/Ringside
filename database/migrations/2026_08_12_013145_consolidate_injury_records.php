<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::drop('wrestlers_injuries');
        Schema::drop('managers_injuries');
        Schema::drop('referees_injuries');

        Schema::create('injuries', function (Blueprint $table) {
            $table->id();
            $table->morphs('injurable');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();
        });

        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $connection->statement(
                'CREATE UNIQUE INDEX injuries_one_open_period_unique '
                .'ON injuries (injurable_type, injurable_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE injuries '
                .'ADD COLUMN current_injurable_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN injurable_id ELSE NULL END) STORED, '
                .'ADD COLUMN current_injurable_type VARCHAR(255) '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN injurable_type ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX injuries_one_open_period_unique '
                .'(current_injurable_type, current_injurable_id)'
            ),
            default => throw new LogicException('The database driver does not support open injury period constraints.'),
        };
    }
};
