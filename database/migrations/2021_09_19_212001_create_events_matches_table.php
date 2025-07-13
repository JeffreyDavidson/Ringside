<?php

use App\Models\Events\Event;
use App\Models\Matches\MatchType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Event::class);
            $table->unsignedTinyInteger('match_number');
            $table->foreignIdFor(MatchType::class);
            $table->text('preview')->nullable();
            $table->timestamps();
        });
    }
};
