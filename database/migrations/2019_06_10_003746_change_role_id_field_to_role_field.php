<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeRoleIdFieldToRoleField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_id', 'role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 15)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role', 'role_id')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->change();
        });
    }
}
