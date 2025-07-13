<?php

use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events_matches_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(EventMatch::class);
            $table->foreignIdFor(Title::class);
            $table->timestamps();
        });
    }
};
