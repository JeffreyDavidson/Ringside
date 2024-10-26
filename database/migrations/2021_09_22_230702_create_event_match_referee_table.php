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
        Schema::create('event_match_referee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_match_id')->constrained('event_matches');
            $table->foreignId('referee_id')->constrained();
            $table->timestamps();
        });
    }
};
