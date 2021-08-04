<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrentTagTeamIdToWrestlersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wrestlers', function (Blueprint $table) {
            $table->unsignedInteger('current_tag_team_id')->nullable()->after('user_id');

            $table->foreign('current_tag_team_id')->references('id')->on('tag_teams');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wrestlers', function (Blueprint $table) {
            $table->dropForeign('wrestlers_current_tag_team_id_foreign');
            $table->dropColumn(['current_tag_team_id']);
        });
    }
}
