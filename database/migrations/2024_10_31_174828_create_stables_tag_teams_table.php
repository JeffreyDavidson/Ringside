<?php

use App\Models\Stable;
use App\Models\TagTeam;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stables_tag_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Stable::class);
            $table->foreignIdFor(TagTeam::class);
            $table->datetime('joined_at');
            $table->datetime('left_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stables_tag_teams');
    }
};
