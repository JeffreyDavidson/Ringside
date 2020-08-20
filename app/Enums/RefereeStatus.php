<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static RefereeStatus BOOKABLE()
 * @method static RefereeStatus INJURED()
 * @method static RefereeStatus FUTURE_EMPLOYMENT()
 * @method static RefereeStatus RELEASED()
 * @method static RefereeStatus RETIRED()
 * @method static RefereeStatus SUSPENDED()
 * @method static RefereeStatus UNEMPLOYED()
 */
final class RefereeStatus extends Enum
{
    const __default = self::UNEMPLOYED;

    const BOOKABLE = 'bookable';
    const INJURED = 'injured';
    const FUTURE_EMPLOYMENT = 'future-employment';
    const RELEASED = 'released';
    const RETIRED = 'retired';
    const SUSPENDED = 'suspended';
    const UNEMPLOYED = 'unemployed';
}
