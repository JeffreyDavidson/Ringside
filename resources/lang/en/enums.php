<?php

use App\Enums\TitleStatus;
use App\Enums\WrestlerStatus;

return [
    TitleStatus::class => [
        TitleStatus::ACTIVE => 'Active',
        TitleStatus::PENDING_INTRODUCED => 'Pending Introduced',
        TitleStatus::RETIRED => 'Retired',
    ],
    WrestlerStatus::class => [
        WrestlerStatus::BOOKABLE => 'Bookable',
        WrestlerStatus::PENDING_INTRODUCED => 'Pending Introduced',
        WrestlerStatus::RETIRED => 'Retired',
        WrestlerStatus::SUSPENDED => 'Suspended',
        WrestlerStatus::INJURED => 'Injured',
    ],
];
