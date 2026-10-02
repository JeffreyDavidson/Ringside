<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

test('foreign key, history and tenant-scoping columns are indexed', function (string $table, array $columns): void {
    // Arrange
    $indexName = sprintf('%s_%s_index', $table, implode('_', $columns));

    // Act
    $hasIndex = Schema::hasIndex($table, $indexName);

    // Assert
    expect($hasIndex)->toBeTrue();
})->with([
    'matches by event' => ['events_matches', ['event_id']],
    'competitors by side' => ['events_matches_competitors', ['match_side_id']],
    'match referees by match' => ['events_matches_referees', ['match_id']],
    'match referees by referee' => ['events_matches_referees', ['referee_id']],
    'match titles by match' => ['events_matches_titles', ['match_id']],
    'match titles by title' => ['events_matches_titles', ['title_id']],
    'championships by title and win date' => ['titles_championships', ['title_id', 'won_at']],
    'wrestler managers by wrestler' => ['wrestlers_managers', ['wrestler_id']],
    'wrestler managers by manager' => ['wrestlers_managers', ['manager_id']],
    'tag team managers by tag team' => ['tag_teams_managers', ['tag_team_id']],
    'tag team managers by manager' => ['tag_teams_managers', ['manager_id']],
    'tag team wrestlers by tag team' => ['tag_teams_wrestlers', ['tag_team_id']],
    'tag team wrestlers by wrestler' => ['tag_teams_wrestlers', ['wrestler_id']],
    'stable wrestlers by stable' => ['stables_wrestlers', ['stable_id']],
    'stable wrestlers by wrestler' => ['stables_wrestlers', ['wrestler_id']],
    'stable tag teams by stable' => ['stables_tag_teams', ['stable_id']],
    'stable tag teams by tag team' => ['stables_tag_teams', ['tag_team_id']],
    'wrestlers by promotion' => ['wrestlers', ['promotion_id']],
    'managers by promotion' => ['managers', ['promotion_id']],
    'referees by promotion' => ['referees', ['promotion_id']],
    'tag teams by promotion' => ['tag_teams', ['promotion_id']],
    'titles by promotion' => ['titles', ['promotion_id']],
    'events by promotion and date' => ['events', ['promotion_id', 'date']],
    'events by venue' => ['events', ['venue_id']],
    'promotion members by user' => ['promotion_user', ['user_id']],
]);
