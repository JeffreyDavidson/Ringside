<?php

declare(strict_types=1);

return [
    'name' => 'Event Name',
    'date' => 'Date',
    'date_time' => 'Date and time',
    'date_does_not_exist' => 'That time does not exist in :timezone because the clocks move forward then. Choose a different time.',
    'venue' => 'Venue',
    'preview' => 'Preview',
    'index_description' => 'Manage event schedules, venues, and promotion cards in one place.',
    'index_title' => 'Events',
    'add' => 'Add Event',
    'all' => 'All events',
    'filter_status' => 'Filter events by status',
    'search' => 'Search events',
    'clear_search' => 'Clear search',
    'filters' => 'Filters',
    'all_venues' => 'All venues',
    'date_range' => 'Event date range',
    'from' => 'From',
    'to' => 'To',
    'clear_filters' => 'Clear filters',
    'no_date' => 'No date set',
    'no_venue' => 'No venue',
    'empty_title' => 'Your event schedule starts here',
    'empty_description' => 'Add an event to plan your first show.',
    'empty_read_only_description' => 'Events added to this promotion will appear here.',
    'no_results_title' => 'No matching events',
    'no_results_description' => 'Try another search or adjust the filters to see more events.',
    'results' => ':first–:last of :total events',
    'per_page' => 'Rows per page',
    'updating' => 'Updating events…',
    'pagination' => 'Event pages',
    'previous_page' => 'Previous page',
    'next_page' => 'Next page',
    'page' => ':current / :last',

    'errors' => [
        'reschedule' => [
            'already_occurred' => 'Event [:name] cannot be rescheduled because it has already occurred.',
            'has_title_reigns' => 'Event [:name] cannot be rescheduled because its matches have created or ended title reigns.',
        ],
    ],

    'actions' => [
        'menu_label' => 'Event actions',
        'deleted' => 'Event successfully deleted.',
    ],

    'validation' => [
        'attributes' => [
            'event_name' => 'event name',
            'event_date' => 'event date',
            'venue' => 'venue',
            'event_preview' => 'event preview',
        ],
    ],
];
