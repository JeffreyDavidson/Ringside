<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
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
        Schema::dropIfExists('events_matches_winners');
        Schema::dropIfExists('events_matches_losers');
        Schema::dropIfExists('events_matches_results');
        Schema::dropIfExists('events_matches_competitors');

        Schema::create('events_matches_sides', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(EventMatch::class, 'match_id')->constrained('events_matches')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['match_id', 'position']);
            $table->unique(['id', 'match_id']);
        });

        Schema::create('events_matches_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(EventMatch::class, 'match_id')->constrained('events_matches')->cascadeOnDelete();
            $table->foreignIdFor(MatchSide::class, 'match_side_id');
            $table->morphs('competitor');
            $table->timestamps();

            $table->foreign(['match_side_id', 'match_id'])
                ->references(['id', 'match_id'])
                ->on('events_matches_sides')
                ->cascadeOnDelete();
            $table->unique(['match_id', 'competitor_type', 'competitor_id'], 'match_competitor_unique');
        });

        Schema::table('events_matches', function (Blueprint $table) {
            $table->string('match_finish')->nullable();
            $table->foreignIdFor(MatchSide::class, 'winning_side_id')
                ->nullable()
                ->constrained('events_matches_sides')
                ->nullOnDelete();
        });
    }
};
