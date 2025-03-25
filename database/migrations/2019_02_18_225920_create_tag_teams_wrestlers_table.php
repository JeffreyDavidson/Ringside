<?php

use App\Models\TagTeam;
use App\Models\Wrestler;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tag_teams_wrestlers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TagTeam::class);
            $table->foreignIdFor(Wrestler::class);
            $table->datetime('joined_at')->nullable();
            $table->datetime('left_at')->nullable();
            $table->timestamps();
        });
    }
};
