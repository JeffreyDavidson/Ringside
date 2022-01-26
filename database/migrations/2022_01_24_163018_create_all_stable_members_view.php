<?php

use App\Models\Stable;
use Illuminate\Database\Migrations\Migration;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

class CreateAllStableMembersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createOrReplaceMergeView(
            'all_stable_members',
            [(new Stable)->tagTeams(), (new Stable)->wrestlers()]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('all_stable_members');
    }
}
