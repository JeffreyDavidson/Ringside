<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Models\Matches\MatchType as MatchTypeModel;
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

        // Migrate existing data from match_type_id to match_type enum values
        $matches = DB::table('events_matches')->get();
        foreach ($matches as $match) {
            $matchTypeModel = MatchTypeModel::find($match->match_type_id);
            if ($matchTypeModel) {
                // Convert the slug to enum value by finding matching enum case
                $enumValue = $this->getEnumValueFromSlug($matchTypeModel->slug);
                if ($enumValue) {
                    DB::table('events_matches')
                        ->where('id', $match->id)
                        ->update(['match_type' => $enumValue]);
                }
            }
        }

        // Remove the old foreign key constraint and column
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

        // Migrate data back from enum to foreign key
        $matches = DB::table('events_matches')->get();
        foreach ($matches as $match) {
            $matchTypeModel = MatchTypeModel::where('slug', $match->match_type)->first();
            if ($matchTypeModel) {
                DB::table('events_matches')
                    ->where('id', $match->id)
                    ->update(['match_type_id' => $matchTypeModel->id]);
            }
        }

        // Remove the enum column
        Schema::table('events_matches', function (Blueprint $table) {
            $table->dropColumn('match_type');
        });

        // Make match_type_id non-nullable
        Schema::table('events_matches', function (Blueprint $table) {
            $table->bigInteger('match_type_id')->nullable(false)->change();
        });
    }

    /**
     * Get the enum value from a slug.
     */
    private function getEnumValueFromSlug(string $slug): ?string
    {
        foreach (MatchType::cases() as $case) {
            if ($case->value === $slug) {
                return $case->value;
            }
        }

        return null;
    }
};
