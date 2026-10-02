<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index the foreign key, history and tenant-scoping columns that queries filter on but no index leads with.
     *
     * Only CREATE INDEX statements run, so no table is rebuilt and no CHECK constraint or existing index is touched
     * on SQLite. Existing indexes were checked first: the one-current-membership partial unique indexes only serve
     * current membership (history queries filter on the ended side), the stables (promotion_id, name) partial unique
     * index already leads with stables.promotion_id, and events_matches_competitors already has composite and
     * (competitor_type, competitor_id) indexes that do not lead with match_side_id.
     */
    public function up(): void
    {
        $indexes = [
            'events_matches' => [['event_id']],
            'events_matches_competitors' => [['match_side_id']],
            'events_matches_referees' => [['match_id'], ['referee_id']],
            'events_matches_titles' => [['match_id'], ['title_id']],
            'titles_championships' => [['title_id', 'won_at']],
            'wrestlers_managers' => [['wrestler_id'], ['manager_id']],
            'tag_teams_managers' => [['tag_team_id'], ['manager_id']],
            'tag_teams_wrestlers' => [['tag_team_id'], ['wrestler_id']],
            'stables_wrestlers' => [['stable_id'], ['wrestler_id']],
            'stables_tag_teams' => [['stable_id'], ['tag_team_id']],
            'wrestlers' => [['promotion_id']],
            'managers' => [['promotion_id']],
            'referees' => [['promotion_id']],
            'tag_teams' => [['promotion_id']],
            'titles' => [['promotion_id']],
            'events' => [['promotion_id', 'date'], ['venue_id']],
            'promotion_user' => [['user_id']],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($tableIndexes): void {
                foreach ($tableIndexes as $columns) {
                    $blueprint->index($columns);
                }
            });
        }
    }
};
