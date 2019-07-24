<?php

use App\Enums\TitleStatus;
use App\Enums\ManagerStatus;
use App\Enums\RefereeStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;

return [
    TitleStatus::class => [
        TitleStatus::BOOKABLE => 'Bookable',
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
    TagTeamStatus::class => [
        TagTeamStatus::BOOKABLE => 'Bookable',
        TagTeamStatus::PENDING_INTRODUCED => 'Pending Introduced',
        TagTeamStatus::RETIRED => 'Retired',
        TagTeamStatus::SUSPENDED => 'Suspended',
    ],
    RefereeStatus::class => [
        RefereeStatus::BOOKABLE => 'Bookable',
        RefereeStatus::PENDING_INTRODUCED => 'Pending Introduced',
        RefereeStatus::RETIRED => 'Retired',
        RefereeStatus::SUSPENDED => 'Suspended',
        RefereeStatus::INJURED => 'Injured',
    ],
    ManagerStatus::class => [
        ManagerStatus::BOOKABLE => 'Bookable',
        ManagerStatus::PENDING_INTRODUCTION => 'Pending Introduction',
        ManagerStatus::RETIRED => 'Retired',
        ManagerStatus::SUSPENDED => 'Suspended',
        ManagerStatus::INJURED => 'Injured',
    ],
];
