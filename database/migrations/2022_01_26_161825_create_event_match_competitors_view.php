<?php

use App\Models\EventMatch;
use Illuminate\Database\Migrations\Migration;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

class CreateEventMatchCompetitorsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createOrReplaceMergeView(
            'all_match_competitors',
            [(new EventMatch)->wrestlers(), (new EventMatch)->tagTeams()]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('all_match_competitors');
    }
}
