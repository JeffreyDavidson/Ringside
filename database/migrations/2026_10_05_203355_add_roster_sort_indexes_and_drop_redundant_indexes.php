<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index the roster list sort and the match form's roster search, and drop four redundant indexes.
     *
     * The roster tables order by (name, id) or (last_name, id) inside one promotion, and BookableRosterSearch orders
     * referees by (full_name, id) inside one promotion. An index that matches the filter and the ORDER BY lets the
     * database read the first page in order instead of sorting the promotion's whole roster.
     *
     * The dropped single-column indexes are the left prefix of a composite unique or history index created later, so
     * they only slow writes. None of these columns carries a foreign key, so MySQL does not need them. Only CREATE and
     * DROP INDEX statements run, so no SQLite table is rebuilt.
     */
    public function up(): void
    {
        $indexes = [
            'wrestlers' => [['promotion_id', 'name', 'id']],
            'tag_teams' => [['promotion_id', 'name', 'id']],
            'titles' => [['promotion_id', 'name', 'id']],
            'managers' => [['promotion_id', 'last_name', 'id']],
            'referees' => [['promotion_id', 'last_name', 'id'], ['promotion_id', 'full_name', 'id']],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($tableIndexes): void {
                foreach ($tableIndexes as $columns) {
                    $blueprint->index($columns);
                }
            });
        }

        $redundant = [
            'events_matches' => 'events_matches_event_id_index',
            'events_matches_referees' => 'events_matches_referees_match_id_index',
            'events_matches_titles' => 'events_matches_titles_match_id_index',
            'lifecycle_transitions' => 'lifecycle_transitions_subject_type_subject_id_index',
        ];

        foreach ($redundant as $table => $index) {
            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropIndex($index);
            });
        }
    }
};
