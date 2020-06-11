<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static RefereeStatus BOOKABLE()
 * @method static RefereeStatus INJURED()
 * @method static RefereeStatus PENDING_EMPLOYMENT()
 * @method static RefereeStatus RELEASED()
 * @method static RefereeStatus RETIRED()
 * @method static RefereeStatus SUSPENDED()
 */
final class RefereeStatus extends Enum
{
    const __default = self::UNEMPLOYED;

    const BOOKABLE = 'bookable';
    const INJURED = 'injured';
    const PENDING_EMPLOYMENT = 'pending-employment';
    const RELEASED = 'released';
    const RETIRED = 'retired';
    const SUSPENDED = 'suspended';
    const UNEMPLOYED = 'unemployed';
}
