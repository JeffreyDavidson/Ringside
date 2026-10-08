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
    ],

    'validation' => [
        'attributes' => [
            'street_address' => 'street address',
            'zip_code' => 'zip code',
        ],
    ],

    'errors' => [
        'restored' => [
            'name_conflict' => ':context cannot be restored because the name conflicts with existing venue \':conflicting_name\'. Resolve the conflict before restoration.',
            'not_deleted' => ':context cannot be restored because it is not deleted.',
        ],
    ],
];
