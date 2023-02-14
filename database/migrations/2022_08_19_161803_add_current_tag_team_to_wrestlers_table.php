<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wrestlers', function (Blueprint $table) {
            $table->foreignId('current_tag_team_id')->after('status')->nullable()->constrained('tag_teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wrestlers', function (Blueprint $table) {
            //
        });
    }
};
