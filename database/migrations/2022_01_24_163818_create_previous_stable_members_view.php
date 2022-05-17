<?php

declare(strict_types=1);

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
        Schema::createOrReplaceMergeView(
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
