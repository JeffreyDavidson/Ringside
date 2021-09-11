<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->id();
            $table->foreignId('stable_id')->constrained();
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
