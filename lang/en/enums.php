<?php

use App\Enums\EventStatus;
use App\Enums\ManagerStatus;
use App\Enums\RefereeStatus;
use App\Enums\Role;
use App\Enums\StableStatus;
use App\Enums\TagTeamStatus;
use App\Enums\TitleStatus;
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
        TitleStatus::FUTURE_ACTIVATION => 'Future Activation',
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
        StableStatus::FUTURE_ACTIVATION => 'Future Activation',
        StableStatus::RETIRED => 'Retired',
    ],
    EventStatus::class => [
        EventStatus::PAST => 'Past',
        EventStatus::SCHEDULED => 'Scheduled',
        EventStatus::UNSCHEDULED => 'Unscheduled',
    ],
];
