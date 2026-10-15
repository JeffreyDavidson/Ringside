<?php

// Messages for the lifecycle exceptions shared by wrestlers, managers and referees.
// :context names the person, for example "Wrestler 'Ric'". :details is empty or a
// parenthesised note such as " (torn ACL)".
return [
    'errors' => [
        'employ' => [
            'already_employed' => ':context is already employed.',
            'retired' => ':context is retired and cannot be employed. Unretire them first.',
            'future_employment' => ':context already has employment scheduled to start and cannot be employed again.',
        ],
        'injure' => [
            'unemployed' => ':context is unemployed and cannot be injured.',
            'future_employment' => ':context is not employed yet because their employment starts in the future, so they cannot be injured.',
            'already_injured' => ':context is already injured:details.',
            'suspended' => ':context is suspended:details and cannot be injured.',
        ],
        'clear_from_injury' => [
            'not_injured' => ':context is not injured and cannot be cleared from injury.',
        ],
        'reinstate' => [
            'injured' => ':context is injured:details and must be cleared from injury before being reinstated.',
            'not_suspended' => ':context is not suspended and does not need to be reinstated.',
        ],
        'release' => [
            'unemployed' => ':context is unemployed and cannot be released.',
            'future_employment' => ':context is not employed yet because their employment starts in the future, so they cannot be released.',
        ],
        'restore' => [
            'not_deleted' => ':context is not deleted and cannot be restored.',
        ],
        'retire' => [
            'unemployed' => ':context is unemployed and cannot be retired.',
            'already_retired' => ':context is already retired.',
            'future_employment' => ':context has employment scheduled to start and cannot be retired.',
        ],
        'suspend' => [
            'unemployed' => ':context is unemployed and cannot be suspended.',
            'future_employment' => ':context is not employed yet because their employment starts in the future, so they cannot be suspended.',
            'retired' => ':context is retired and cannot be suspended.',
            'released' => ':context is released and cannot be suspended.',
            'already_suspended' => ':context is already suspended:details.',
            'injured' => ':context is injured:details and cannot be suspended.',
        ],
        'unretire' => [
            'deleted' => ':context is deleted and cannot be unretired. Restore them first.',
            'not_retired' => ':context is not retired and cannot be unretired.',
        ],
    ],
];
