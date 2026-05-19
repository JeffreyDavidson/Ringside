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
        Schema::create('events_matches_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(EventMatch::class, 'match_id');
            $table->morphs('competitor');
            $table->tinyInteger('side_number');
            $table->timestamps();
        });
    }
};
