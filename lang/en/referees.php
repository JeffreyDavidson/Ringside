<?php

return [
    'name' => 'Referee Name',
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'index_description' => 'Manage your promotion’s referees, assignments, and officiating records.',
    'index_title' => 'Referees',
    'add' => 'Add Referee',
    'all' => 'All referees',
    'filter_status' => 'Filter referees by status',
    'search' => 'Search referees',
    'clear_search' => 'Clear search',
    'filters' => 'Filters',
    'employment_date' => 'Employment date range',
    'from' => 'From',
    'to' => 'To',
    'clear_filters' => 'Clear filters',
    'empty_title' => 'Your referee roster starts here',
    'empty_description' => 'Add a referee to build your officiating roster.',
    'empty_read_only_description' => 'Referees in this promotion will appear here.',
    'no_results_title' => 'No matching referees',
    'no_results_description' => 'Try another name or adjust the filters to see more referees.',
    'results' => ':first–:last of :total referees',
    'per_page' => 'Rows per page',
    'updating' => 'Updating referees…',
    'pagination' => 'Referee pages',
    'previous_page' => 'Previous page',
    'next_page' => 'Next page',
    'page' => ':current / :last',

    'actions' => [
        'menu_label' => 'Referee actions',
        'employed' => 'Referee has been hired.',
        'released' => 'Contract has been terminated.',
        'retired' => 'Referee has been retired.',
        'unretired' => 'Referee has been brought out of retirement.',
        'suspended' => 'Referee has been suspended.',
        'reinstated' => 'Referee has been reinstated.',
        'injured' => 'Injury has been recorded.',
        'cleared_from_injury' => 'Referee has been cleared from injury.',
        'deleted' => 'Referee has been removed from the roster.',
        'restored' => 'Referee has been restored to the roster.',
    ],

    'errors' => [
        'employ' => [
            'default' => 'Unable to hire this referee at this time.',
            'already_employed' => 'This referee is already hired.',
            'retired' => 'Retired referees cannot be hired without unretiring first.',
        ],
        'release' => [
            'default' => 'Unable to release this referee.',
            'unemployed' => 'This referee is not currently employed.',
        ],
        'retire' => [
            'default' => 'Unable to retire this referee.',
            'already_retired' => 'This referee is already retired.',
            'unemployed' => 'Only employed referees can retire.',
        ],
        'unretire' => [
            'default' => 'Unable to bring this referee out of retirement.',
            'not_retired' => 'This referee is not currently retired.',
        ],
        'suspend' => [
            'default' => 'Unable to suspend this referee.',
            'already_suspended' => 'This referee is already suspended.',
            'injured' => 'Injured referees cannot be suspended.',
            'unemployed' => 'Only employed referees can be suspended.',
        ],
        'reinstate' => [
            'default' => 'Unable to reinstate this referee.',
            'injured' => 'Injured referees must be cleared from injury instead of reinstated.',
            'not_suspended' => 'This referee is not currently suspended.',
        ],
        'injure' => [
            'default' => 'Unable to record injury for this referee.',
            'already_injured' => 'This referee is already injured.',
            'suspended' => 'Suspended referees cannot be injured.',
            'unemployed' => 'Only employed referees can be injured.',
        ],
        'clear_from_injury' => [
            'default' => 'Unable to clear this referee from injury.',
            'not_injured' => 'This referee is not currently injured.',
        ],
        'restore' => [
            'default' => 'Unable to restore this referee.',
            'not_deleted' => 'This referee has not been deleted.',
        ],
        'general' => 'An unexpected error occurred. Please try again.',
    ],

    'validation' => [
        'attributes' => [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'employment_date' => 'employment date',
        ],
        'not_bookable' => 'This referee is not available to officiate matches.',
    ],
];
