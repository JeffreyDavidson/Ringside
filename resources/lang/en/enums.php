<?php

use App\Enums\Role;
use App\Enums\EventStatus;
use App\Enums\TitleStatus;
use App\Enums\StableStatus;
use App\Enums\ManagerStatus;
use App\Enums\RefereeStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;

return [
    Role::class => [
        Role::ADMINISTRATOR => 'Administrator',
        Role::SUPER_ADMINISTRATOR => 'Super Administrator',
        Role::BASIC => 'Basic User',
    ],
    TitleStatus::class => [
        TitleStatus::ACTIVE => 'Active',
        TitleStatus::INACTIVE => 'Inactive',
        TitleStatus::PENDING_ACTIVATION => 'Pending Activation',
        TitleStatus::RETIRED => 'Retired',
    ],
    WrestlerStatus::class => [
        WrestlerStatus::BOOKABLE => 'Bookable',
        WrestlerStatus::FUTURE_EMPLOYMENT => 'Future Employment',
        WrestlerStatus::RETIRED => 'Retired',
        WrestlerStatus::SUSPENDED => 'Suspended',
        WrestlerStatus::INJURED => 'Injured',
        WrestlerStatus::RELEASED => 'Released',
    ],
    TagTeamStatus::class => [
        TagTeamStatus::BOOKABLE => 'Bookable',
        TagTeamStatus::FUTURE_EMPLOYMENT => 'Future Employment',
        TagTeamStatus::RETIRED => 'Retired',
        TagTeamStatus::SUSPENDED => 'Suspended',
    ],
    RefereeStatus::class => [
        RefereeStatus::BOOKABLE => 'Bookable',
        RefereeStatus::FUTURE_EMPLOYMENT => 'Future Employment',
        RefereeStatus::RETIRED => 'Retired',
        RefereeStatus::SUSPENDED => 'Suspended',
        RefereeStatus::INJURED => 'Injured',
    ],
    ManagerStatus::class => [
        ManagerStatus::AVAILABLE => 'Available',
        ManagerStatus::FUTURE_EMPLOYMENT => 'Future Employment',
        ManagerStatus::RETIRED => 'Retired',
        ManagerStatus::SUSPENDED => 'Suspended',
        ManagerStatus::INJURED => 'Injured',
    ],
    StableStatus::class => [
        StableStatus::ACTIVE => 'Active',
        StableStatus::INACTIVE => 'Inactive',
        StableStatus::PENDING_ACTIVATION => 'Pending Activation',
        StableStatus::RETIRED => 'Retired',
    ],
    EventStatus::class => [
        EventStatus::SCHEDULED => 'Scheduled',
        EventStatus::PAST => 'Past',
    ],
];
