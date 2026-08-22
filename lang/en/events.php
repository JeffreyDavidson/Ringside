<?php

declare(strict_types=1);

return [
    'name' => 'Event Name',
    'date' => 'Date',
    'venue' => 'Venue',
    'preview' => 'Preview',

    'actions' => [
        'deleted' => 'Event successfully deleted.',
        'restored' => 'Event successfully restored.',
    ],

    'validation' => [
        'has_past_date' => 'This event has past and its date cannot be changed.',
    ],
];
