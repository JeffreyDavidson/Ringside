<?php

declare(strict_types=1);

return [
    'name' => 'Name',
    'street_address' => 'Street Address',
    'city' => 'City',
    'state' => 'State',
    'zipcode' => 'Zip Code',
    'timezone' => 'Time zone',
    'index_title' => 'Venues',
    'add' => 'Add Venue',
    'index_description' => 'Manage shared venues available to every promotion and their event history.',
    'empty_description' => 'Venues added to the shared directory will appear here.',
    'empty_title' => 'No venues yet',

    'actions' => [
        'menu_label' => 'Venue actions',
        'deleted' => 'Venue successfully deleted.',
        'restored' => 'Venue successfully restored.',
    ],

    'validation' => [
        'name_taken' => "A venue named ':name' already exists.",
        'attributes' => [
            'street_address' => 'street address',
            'zip_code' => 'zip code',
        ],
    ],

    'errors' => [
        'deleted' => [
            'has_upcoming_events' => '{1} :context has :count upcoming event and cannot be deleted. Move it to another venue or delete it first.|[0,*] :context has :count upcoming events and cannot be deleted. Move them to another venue or delete them first.',
        ],
        'restored' => [
            'double_booked' => ':context cannot be restored because it hosts more than one event on :date. Move or delete the extra events first.',
            'name_conflict' => ':context cannot be restored because the name conflicts with existing venue \':conflicting_name\'. Resolve the conflict before restoration.',
            'not_deleted' => ':context cannot be restored because it is not deleted.',
        ],
    ],
];
