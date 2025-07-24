<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events_matches_results', function (Blueprint $table) {
            $table->string('winner_type')->nullable()->change();
            $table->unsignedBigInteger('winner_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events_matches_results', function (Blueprint $table) {
            $table->string('winner_type')->nullable(false)->change();
            $table->unsignedBigInteger('winner_id')->nullable(false)->change();
        });
    }
};
