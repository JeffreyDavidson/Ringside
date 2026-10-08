<?php

return [
    'name' => 'Manager Name',
    'first_name' => 'First Name',
    'last_name' => 'Last Name',
    'date_hired' => 'Date Hired',
    'date_fired' => 'Date Fired',
    'index_description' => 'Manage your promotion’s managers, employment, and representation.',
    'index_title' => 'Managers',
    'add' => 'Add Manager',
    'all' => 'All managers',
    'filter_status' => 'Filter managers by status',
    'search' => 'Search managers',
    'clear_search' => 'Clear search',
    'filters' => 'Filters',
    'employment_date' => 'Employment date range',
    'from' => 'From',
    'to' => 'To',
    'clear_filters' => 'Clear filters',
    'empty_title' => 'Your manager roster starts here',
    'empty_description' => 'Add a manager to build your roster and represent your talent.',
    'empty_read_only_description' => 'Managers in this promotion will appear here.',
    'no_results_title' => 'No matching managers',
    'no_results_description' => 'Try another name or adjust the filters to see more managers.',
    'results' => ':first–:last of :total managers',
    'per_page' => 'Rows per page',
    'updating' => 'Updating managers…',
    'pagination' => 'Manager pages',
    'previous_page' => 'Previous page',
    'next_page' => 'Next page',
    'page' => ':current / :last',

    'actions' => [
        'menu_label' => 'Manager actions',
        'employed' => 'Manager has been hired.',
        'released' => 'Manager contract has been terminated.',
        'retired' => 'Manager has been retired.',
        'unretired' => 'Manager has been brought out of retirement.',
        'suspended' => 'Manager has been suspended.',
        'reinstated' => 'Manager has been reinstated.',
        'injured' => 'Manager injury has been recorded.',
        'cleared_from_injury' => 'Manager has been cleared from injury.',
        'deleted' => 'Manager has been deleted.',
        'restored' => 'Manager has been restored.',
    ],

    'errors' => [
        'employ' => [
            'default' => 'Unable to hire this manager at this time.',
            'already_employed' => 'This manager is already hired.',
            'retired' => 'Retired managers cannot be hired without unretiring first.',
        ],
        'release' => [
            'default' => 'Unable to release this manager.',
            'unemployed' => 'This manager is not currently employed.',
        ],
        'retire' => [
            'default' => 'Unable to retire this manager.',
            'already_retired' => 'This manager is already retired.',
            'unemployed' => 'Only employed managers can retire.',
        ],
        'unretire' => [
            'default' => 'Unable to bring this manager out of retirement.',
            'not_retired' => 'This manager is not currently retired.',
        ],
        'suspend' => [
            'default' => 'Unable to suspend this manager.',
            'already_suspended' => 'This manager is already suspended.',
            'injured' => 'Injured managers cannot be suspended.',
            'unemployed' => 'Only employed managers can be suspended.',
        ],
        'reinstate' => [
            'default' => 'Unable to reinstate this manager.',
            'injured' => 'Injured managers must be cleared from injury instead of reinstated.',
            'not_suspended' => 'This manager is not currently suspended.',
        ],
        'injure' => [
            'default' => 'Unable to record injury for this manager.',
            'already_injured' => 'This manager is already injured.',
            'suspended' => 'Suspended managers cannot be injured.',
            'unemployed' => 'Only employed managers can be injured.',
        ],
        'clear_from_injury' => [
            'default' => 'Unable to clear this manager from injury.',
            'not_injured' => 'This manager is not currently injured.',
        ],
        'restore' => [
            'default' => 'Unable to restore this manager.',
            'not_deleted' => 'This manager has not been deleted.',
        ],
        'general' => 'An unexpected error occurred. Please try again.',
    ],

    'validation' => [
        'attributes' => [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'employment_date' => 'employment date',
        ],
    ],
];
