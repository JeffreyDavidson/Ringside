<?php

declare(strict_types=1);

use App\Models\Stable;
use Illuminate\Database\Migrations\Migration;
use Staudenmeir\LaravelMergedRelations\Facades\Schema;

class CreateCurrentStableMembersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::createOrReplaceMergeView(
            'current_stable_members',
            [(new Stable)->currentTagTeams(), (new Stable)->currentWrestlers()]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('current_stable_members');
    }
}
