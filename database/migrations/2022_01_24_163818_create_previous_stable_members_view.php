<?php

use App\Models\Stable;
use Illuminate\Database\Migrations\Migration;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

class CreatePreviousStableMembersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createMergeView(
            'previous_stable_members',
            [(new Stable)->previousTagTeams(), (new Stable)->previousWrestlers()]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('previous_stable_members');
    }
}
