<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per normalized stable name that a split of an unowned stable has ever locked, keyed by the sha256 of the name.
     *
     * StableNameLock upserts the row and so holds its exclusive row lock until the surrounding transaction ends. The
     * rows carry no domain data; they exist only because MySQL has no unique index over active stables without a
     * promotion, so there is otherwise nothing to lock for a name no stable uses yet. A new table, so nothing existing
     * is rebuilt on SQLite.
     */
    public function up(): void
    {
        Schema::create('stable_name_locks', function (Blueprint $table) {
            $table->string('name_key', 64)->primary();
        });
    }
};
