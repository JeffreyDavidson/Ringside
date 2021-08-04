<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentStableIdToTagTeamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tag_teams', function (Blueprint $table) {
            $table->unsignedInteger('current_stable_id')->nullable()->after('user_id');

            $table->foreign('current_stable_id')->references('id')->on('stables');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tag_teams', function (Blueprint $table) {
            $table->dropForeign('tag_teams_current_stable_id_foreign');
            $table->dropColumn(['current_stable_id']);
        });
    }
}
