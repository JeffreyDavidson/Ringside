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
        Schema::create('events_matches_losers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_result_id')->constrained('events_matches_results')->cascadeOnDelete();
            $table->foreignId('match_competitor_id')->constrained('events_matches_competitors')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events_matches_losers');
    }
};
