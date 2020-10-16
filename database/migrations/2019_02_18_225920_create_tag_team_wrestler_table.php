<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTagTeamWrestlerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tag_team_wrestler', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tag_team_id');
            $table->unsignedInteger('wrestler_id');
            $table->datetime('joined_at')->nullable();
            $table->datetime('left_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tag_team_wrestler');
    }
}