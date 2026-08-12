<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::drop('wrestlers_employments');
        Schema::drop('managers_employments');
        Schema::drop('referees_employments');
        Schema::drop('tag_teams_employments');

        Schema::create('employments', function (Blueprint $table) {
            $table->id();
            $table->morphs('employable');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();
        });

        $connection = DB::connection();

        match ($connection->getDriverName()) {
            'sqlite', 'pgsql', 'sqlsrv' => $connection->statement(
                'CREATE UNIQUE INDEX employments_one_open_period_unique '
                .'ON employments (employable_type, employable_id) WHERE ended_at IS NULL'
            ),
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE employments '
                .'ADD COLUMN current_employable_id BIGINT UNSIGNED '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN employable_id ELSE NULL END) STORED, '
                .'ADD COLUMN current_employable_type VARCHAR(255) '
                .'GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN employable_type ELSE NULL END) STORED, '
                .'ADD UNIQUE INDEX employments_one_open_period_unique '
                .'(current_employable_type, current_employable_id)'
            ),
            default => throw new LogicException('The database driver does not support open employment period constraints.'),
        };
    }
};
