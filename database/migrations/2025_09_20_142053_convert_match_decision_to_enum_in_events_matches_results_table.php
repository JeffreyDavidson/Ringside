<?php

declare(strict_types=1);

use App\Enums\MatchDecision;
use App\Models\Matches\MatchDecision as MatchDecisionModel;
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
        // First, add the new match_decision column
        Schema::table('events_matches_results', function (Blueprint $table) {
            $table->string('match_decision')->nullable()->after('match_decision_id');
        });

        // Migrate existing data from match_decision_id to match_decision enum values
        $results = DB::table('events_matches_results')->get();
        foreach ($results as $result) {
            $matchDecisionModel = MatchDecisionModel::find($result->match_decision_id);
            if ($matchDecisionModel) {
                // Convert the slug to enum value by finding matching enum case
                $enumValue = $this->getEnumValueFromSlug($matchDecisionModel->slug);
                if ($enumValue) {
                    DB::table('events_matches_results')
                        ->where('id', $result->id)
                        ->update(['match_decision' => $enumValue]);
                }
            }
        }

        // Remove the old foreign key constraint and column
        Schema::table('events_matches_results', function (Blueprint $table) {
            $table->dropColumn('match_decision_id');
        });

        // Make the new column non-nullable
        Schema::table('events_matches_results', function (Blueprint $table) {
            $table->string('match_decision')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the match_decision_id column
        Schema::table('events_matches_results', function (Blueprint $table) {
            $table->bigInteger('match_decision_id')->nullable()->after('match_id');
        });

        // Migrate data back from enum to foreign key
        $results = DB::table('events_matches_results')->get();
        foreach ($results as $result) {
            $matchDecisionModel = MatchDecisionModel::where('slug', $result->match_decision)->first();
            if ($matchDecisionModel) {
                DB::table('events_matches_results')
                    ->where('id', $result->id)
                    ->update(['match_decision_id' => $matchDecisionModel->id]);
            }
        }

        // Remove the enum column
        Schema::table('events_matches_results', function (Blueprint $table) {
            $table->dropColumn('match_decision');
        });

        // Make match_decision_id non-nullable
        Schema::table('events_matches_results', function (Blueprint $table) {
            $table->bigInteger('match_decision_id')->nullable(false)->change();
        });
    }

    /**
     * Get the enum value from a slug.
     */
    private function getEnumValueFromSlug(string $slug): ?string
    {
        foreach (MatchDecision::cases() as $case) {
            if ($case->value === $slug) {
                return $case->value;
            }
        }

        return null;
    }
};
