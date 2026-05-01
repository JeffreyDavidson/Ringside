<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
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
        Schema::create('events_matches_results', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(EventMatch::class, 'match_id');
            $table->morphs('winner');
            // MatchDecision was converted to an enum, so we use unsignedBigInteger
            $table->unsignedBigInteger('match_decision_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events_matches_results');
    }
};
