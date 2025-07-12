<?php

use App\Models\EventMatch;
use App\Models\Title;
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
        Schema::create('titles_championships', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Title::class);
            $table->morphs('champion');
            $table->foreignIdFor(EventMatch::class, 'won_event_match_id');
            $table->foreignIdFor(EventMatch::class, 'lost_event_match_id');
            $table->dateTime('won_at');
            $table->dateTime('lost_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('titles_championships');
    }
};
