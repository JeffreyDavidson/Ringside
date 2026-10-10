<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per normalized (kind, promotion, value) triple that a create or update has ever locked, keyed by the
     * sha256 of the triple.
     *
     * RecordNameLock upserts the row and so holds its exclusive row lock until the surrounding transaction ends. The
     * rows carry no domain data; they exist because no engine has a unique index over tag team names, tag team
     * signature moves or title names, so there is otherwise nothing to lock for a value no record uses yet. A new
     * table, so nothing existing is rebuilt on SQLite.
     */
    public function up(): void
    {
        Schema::create('record_name_locks', function (Blueprint $table) {
            $table->string('name_key', 64)->primary();
        });
    }
};
