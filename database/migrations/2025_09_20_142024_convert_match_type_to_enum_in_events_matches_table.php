<?php

declare(strict_types=1);

use App\Enums\MatchType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add the new match_type column
        Schema::table('events_matches', function (Blueprint $table) {
            $table->string('match_type')->nullable()->after('match_type_id');
        });

        // Set default value for any existing records (uses Singles as default)
        DB::table('events_matches')
            ->whereNull('match_type')
            ->update(['match_type' => MatchType::Singles->value]);

        // Remove the old column
        Schema::table('events_matches', function (Blueprint $table) {
            $table->dropColumn('match_type_id');
        });

        // Make the new column non-nullable
        Schema::table('events_matches', function (Blueprint $table) {
            $table->string('match_type')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the match_type_id column
        Schema::table('events_matches', function (Blueprint $table) {
            $table->bigInteger('match_type_id')->nullable()->after('match_number');
        });

        // Remove the enum column
        Schema::table('events_matches', function (Blueprint $table) {
            $table->dropColumn('match_type');
        });

        // Make match_type_id non-nullable
        Schema::table('events_matches', function (Blueprint $table) {
            $table->bigInteger('match_type_id')->nullable(false)->change();
        });
    }
};
