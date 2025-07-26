<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove user_id columns from organizational tables as they should not have
     * direct user ownership. Users own wrestlers, and organizational entities
     * (tag teams, stables, managers) are formed from those wrestler relationships.
     */
    public function up(): void
    {
        // Remove user_id from tag_teams table
        Schema::table('tag_teams', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // Remove user_id from stables table
        Schema::table('stables', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // Remove user_id from managers table
        Schema::table('managers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore user_id to managers table
        Schema::table('managers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained();
        });

        // Restore user_id to stables table
        Schema::table('stables', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained();
        });

        // Restore user_id to tag_teams table
        Schema::table('tag_teams', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained();
        });
    }
};
