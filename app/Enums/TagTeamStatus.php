<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static TagTeamStatus BOOKABLE()
 * @method static TagTeamStatus FUTURE_EMPLOYMENT()
 * @method static TagTeamStatus RELEASED()
 * @method static TagTeamStatus RETIRED()
 * @method static TagTeamStatus SUSPENDED()
 * @method static TagTeamStatus UNEMPLOYED()
 */
final class TagTeamStatus extends Enum
{
    const __default = self::UNEMPLOYED;

    const BOOKABLE = 'bookable';
    const FUTURE_EMPLOYMENT = 'future-employment';
    const RELEASED = 'released';
    const RETIRED = 'retired';
    const SUSPENDED = 'suspended';
    const UNEMPLOYED = 'unemployed';
}
