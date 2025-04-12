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
            $table->foreignIdFor(EventMatch::class);
            $table->foreignIdFor(Title::class);
            $table->morphs('new_champion', 'new_champion');
            $table->nullableMorphs('former_champion', 'former_champion');
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
