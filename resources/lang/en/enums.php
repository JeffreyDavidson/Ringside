<?php

use App\Enums\TitleStatus;

return [
    TitleStatus::class => [
        TitleStatus::ACTIVE => 'Active',
        TitleStatus::INACTIVE => 'Inactive',
        TitleStatus::RETIRED => 'Retired',
    ],
];
