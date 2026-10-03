<?php

return [
    'match_type' => 'Match Type',
    'referees' => 'Referee',
    'competitors' => 'Competitors',
    'titles' => 'Titles',
    'result' => 'Result',

    'modal' => [
        'edit' => 'Edit Match',
        'record_result' => 'Record Match Result',
        'correct_result' => 'Correct Match Result',
    ],

    'form' => [
        'competitor' => 'Competitor :number',
        'side' => 'Side :number',
        'team' => 'Team :team',
        'wrestlers' => 'Wrestlers',
        'tag_teams' => 'Tag Teams',
    ],

    'validation' => [
        'competitor_required' => 'Choose a wrestler for :attribute.',
        'side_required' => 'Add wrestlers or a tag team to :attribute.',
        'tag_team_side_min' => 'Add at least :min wrestlers or a tag team to :attribute.',
        'entrants_required' => 'Choose the wrestlers competing in this match.',
        'entrants_min' => 'Choose at least :min wrestlers for this match.',
        'entrants_max' => 'Choose no more than :max wrestlers for this match.',
        'wrestler_distinct' => 'A wrestler can only be booked once in a match.',
        'tag_team_distinct' => 'A tag team can only be booked once in a match.',
        'attributes' => [
            'wrestler' => 'wrestler',
            'tag_team' => 'tag team',
            'referee' => 'referee',
            'title' => 'championship title',
        ],
    ],

    'actions' => [
        'edit' => 'Edit Match',
        'remove' => 'Remove',
        'remove_match' => 'Remove Match :number',
        'confirm_remove' => 'Remove match :number?',
        'deleted' => 'Match successfully deleted.',
        'menu' => 'More actions for match :number',
        'menu_label' => 'Match actions',
    ],
];
