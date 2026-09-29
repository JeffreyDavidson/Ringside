<?php

return [
    'name' => 'Tag Team Name',
    'wrestlerA' => 'Tag Team Partner A',
    'wrestlerB' => 'Tag Team Partner B',
    'signature_move' => 'Signature Move',
    'date_joined' => 'Date Joined',
    'date_left' => 'Date Left',
    'partner' => 'Partner',
    'partners' => 'Partners',
    'index_description' => 'Manage your promotion’s tag teams, partnerships, and records.',
    'index_title' => 'Tag Teams',
    'add' => 'Add Tag Team',
    'all' => 'All tag teams',
    'filter_status' => 'Filter tag teams by status',
    'search' => 'Search tag teams',
    'clear_search' => 'Clear search',
    'clear_filters' => 'Clear filters',
    'empty_title' => 'Your tag teams start here',
    'empty_description' => 'Add a tag team to get started.',
    'empty_read_only_description' => 'Tag teams will appear here when they are added to this promotion.',
    'no_results_title' => 'No matching tag teams',
    'no_results_description' => 'Try another name or clear the filters to see all tag teams.',
    'results' => ':first–:last of :total tag teams',
    'per_page' => 'Rows per page',
    'updating' => 'Updating tag teams…',
    'pagination' => 'Tag team pages',
    'previous_page' => 'Previous page',
    'next_page' => 'Next page',
    'page' => ':current / :last',

    'modal' => [
        'create' => 'Create Tag Team',
    ],

    'actions' => [
        'employed' => 'Tag team has been hired.',
        'released' => 'Tag team contract has been terminated.',
        'retired' => 'Tag team has been retired.',
        'unretired' => 'Tag team has been brought out of retirement.',
        'suspended' => 'Tag team has been suspended.',
        'reinstated' => 'Tag team has been reinstated.',
        'restored' => 'Tag team has been restored.',
        'deleted' => 'Tag team has been deleted.',
    ],

    'errors' => [
        'employ' => [
            'default' => 'Unable to hire this tag team at this time.',
            'already_employed' => 'This tag team is already hired.',
            'retired' => 'Retired tag teams cannot be hired without unretiring first.',
            'suspended' => 'Cannot hire suspended tag teams.',
        ],
        'release' => [
            'default' => 'Unable to release this tag team.',
            'suspended' => 'Tag team must be reinstated before being released.',
            'unemployed' => 'This tag team is not currently employed.',
        ],
        'retire' => [
            'default' => 'Unable to retire this tag team.',
            'already_retired' => 'This tag team is already retired.',
            'suspended' => 'Suspended tag teams must be reinstated before retiring.',
            'unemployed' => 'Only employed tag teams can retire.',
        ],
        'unretire' => [
            'default' => 'Unable to bring this tag team out of retirement.',
            'not_retired' => 'This tag team is not currently retired.',
        ],
        'suspend' => [
            'default' => 'Unable to suspend this tag team.',
            'already_suspended' => 'This tag team is already suspended.',
            'unemployed' => 'Only employed tag teams can be suspended.',
        ],
        'reinstate' => [
            'default' => 'Unable to reinstate this tag team.',
            'not_suspended' => 'This tag team is not currently suspended.',
        ],
        'restore' => [
            'default' => 'Unable to restore this tag team.',
            'not_deleted' => 'This tag team has not been deleted.',
        ],
        'general' => 'An unexpected error occurred with this tag team action. Please try again.',
    ],
];
