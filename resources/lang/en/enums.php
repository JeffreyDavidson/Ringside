<?php

use App\Enums\EventStatus;
use App\Enums\TitleStatus;
use App\Enums\StableStatus;
use App\Enums\ManagerStatus;
use App\Enums\RefereeStatus;
use App\Enums\TagTeamStatus;
use App\Enums\WrestlerStatus;

return [
    TitleStatus::class => [
        TitleStatus::BOOKABLE => 'Bookable',
        TitleStatus::PENDING_INTRODUCTION => 'Pending Introduced',
        TitleStatus::RETIRED => 'Retired',
    ],
    WrestlerStatus::class => [
        WrestlerStatus::BOOKABLE => 'Bookable',
        WrestlerStatus::PENDING_INTRODUCTION => 'Pending Introduced',
        WrestlerStatus::RETIRED => 'Retired',
        WrestlerStatus::SUSPENDED => 'Suspended',
        WrestlerStatus::INJURED => 'Injured',
    ],
    TagTeamStatus::class => [
        TagTeamStatus::BOOKABLE => 'Bookable',
        TagTeamStatus::PENDING_INTRODUCTION => 'Pending Introduced',
        TagTeamStatus::RETIRED => 'Retired',
        TagTeamStatus::SUSPENDED => 'Suspended',
    ],
    RefereeStatus::class => [
        RefereeStatus::BOOKABLE => 'Bookable',
        RefereeStatus::PENDING_INTRODUCTION => 'Pending Introduced',
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
    StableStatus::class => [
        StableStatus::BOOKABLE => 'Bookable',
        StableStatus::PENDING_INTRODUCTION => 'Pending Introduction',
        StableStatus::RETIRED => 'Retired',
    ],
    EventStatus::class => [
        EventStatus::SCHEDULED => 'Scheduled',
        EventStatus::PAST => 'Past',
    ],
];
