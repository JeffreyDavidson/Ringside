<?php

use App\Models\EventMatch;
use Illuminate\Database\Migrations\Migration;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::createOrReplaceMergeView(
            'all_match_competitors',
            [(new EventMatch)->wrestlers(), (new EventMatch)->tagTeams()]
        );
    }
};
