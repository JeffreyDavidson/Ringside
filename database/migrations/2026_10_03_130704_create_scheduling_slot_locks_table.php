<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per event date slot that a reschedule or restore has ever locked, keyed by the slot's unix timestamp.
     *
     * SchedulingSlotLock upserts the row and so holds its exclusive row lock until the surrounding transaction
     * ends. The rows carry no domain data; they exist only so that every database engine has a real row to lock for a
     * date that has no event yet. A new table, so nothing existing is rebuilt on SQLite.
     */
    public function up(): void
    {
        Schema::create('scheduling_slot_locks', function (Blueprint $table) {
            $table->bigInteger('slot')->primary();
        });
    }
};
