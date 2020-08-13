<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStableMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stable_members', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('stable_id');
            $table->morphs('member');
            $table->datetime('joined_at');
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
        Schema::dropIfExists('stable_members');
    }
}