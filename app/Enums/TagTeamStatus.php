<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static TagTeamStatus BOOKABLE()
 * @method static TagTeamStatus PENDING_INTRODUCTION()
 * @method static TagTeamStatus SUSPENDED()
 * @method static TagTeamStatus RETIRED()
 */
final class TagTeamStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const PENDING_INTRODUCTION = 'pending-introduction';
    const SUSPENDED = 'suspended';
    const RETIRED = 'retired';
}
