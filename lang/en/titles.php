<?php

declare(strict_types=1);

return [
    'name' => 'Title Name',
    'type' => 'Title Type',
    'type_locked' => 'The type cannot be changed once the title has championship reigns or is booked in a match.',
    'current_champion' => 'Current Champion',
    'index_description' => 'Manage championships, title status, and current champions for your promotion.',
    'index_title' => 'Titles',
    'add' => 'Add Title',
    'all' => 'All titles',
    'filter_status' => 'Filter titles by status',
    'search' => 'Search titles',
    'clear_search' => 'Clear search',
    'filters' => 'Filters',
    'all_types' => 'All title types',
    'activation_date' => 'Activation date range',
    'from' => 'From',
    'to' => 'To',
    'clear_filters' => 'Clear filters',
    'vacant' => 'Vacant',
    'empty_title' => 'Your championship history starts here',
    'empty_description' => 'Add a title to track champions, defenses, and every reign.',
    'empty_read_only_description' => 'Titles for this promotion will appear here.',
    'no_results_title' => 'No matching titles',
    'no_results_description' => 'Try another name or adjust the filters to find a championship.',
    'results' => ':first–:last of :total titles',
    'per_page' => 'Rows per page',
    'updating' => 'Updating titles…',
    'pagination' => 'Title pages',
    'previous_page' => 'Previous page',
    'next_page' => 'Next page',
    'page' => ':current / :last',

    'errors' => [
        'debuted' => [
            'already_debuted' => ':context has already been debuted and cannot be debuted again.',
        ],
        'pulled' => [
            'not_active' => ':context is not currently active and cannot be pulled from competition.',
        ],
        'reinstated' => [
            'active' => ':context is already active and cannot be reinstated.',
            'retired' => ':context is retired and cannot be reinstated.',
            'never_activated' => ':context has never been activated and cannot be reinstated. Use debut workflow instead.',
            'scheduled_debut' => ':context has a scheduled debut and cannot be reinstated. Change the debut date instead.',
        ],
        'restored' => [
            'name_conflict' => ':context cannot be restored because the name conflicts with existing title \':conflicting_name\'. Resolve the conflict before restoration.',
            'not_deleted' => ':context cannot be restored because it is not deleted.',
        ],
        'retired' => [
            'unactivated' => ':context has never been activated and cannot be retired.',
            'has_future_debut' => ':context has future debut scheduled and cannot be retired before activation.',
            'already_retired' => ':context is already retired and cannot be retired again.',
        ],
        'unretired' => [
            'deleted' => ':context cannot be unretired because it is deleted. Restore it first.',
            'not_retired' => ':context is not currently retired and cannot be unretired.',
        ],
        'change_type' => [
            'has_championships_or_matches' => 'Title [:title] type cannot be changed because it has championship reigns or is booked in a match.',
        ],
    ],

    'actions' => [
        'menu_label' => 'Title actions',
        'deleted' => 'Title successfully deleted.',
        'restored' => 'Title successfully restored.',
        'debuted' => 'Title successfully debuted.',
        'retired' => 'Title successfully retired.',
        'unretired' => 'Title successfully unretired.',
        'pulled' => 'Title successfully pulled.',
        'reinstated' => 'Title successfully reinstated.',
    ],

    'validation' => [
        'attributes' => [
            'title_type' => 'title type',
            'start_date' => 'start date',
        ],
        'champion_must_compete' => 'The current champion must be included in title matches.',
        'competitor_type' => 'The :title may only be contested by :competitors.',
        'inactive' => 'This title is not active and cannot be used in matches.',
        'invalid' => 'The selected title is invalid.',
    ],
];
