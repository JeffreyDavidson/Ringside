<?php

declare(strict_types=1);

use App\Models\MatchDecision;
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
        Schema::create('event_match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_match_id')->constrained();
            $table->morphs('winner');
            $table->foreignIdFor(MatchDecision::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_match_results');
    }
};
