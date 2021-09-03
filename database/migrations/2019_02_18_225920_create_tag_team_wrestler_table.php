<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->foreignId('tag_team_id')->constrained();
            $table->foreignId('wrestler_id')->constrained();
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
