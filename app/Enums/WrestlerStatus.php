<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static WrestlerStatus BOOKABLE()
 * @method static WrestlerStatus INJURED()
 * @method static WrestlerStatus PENDING_EMPLOYMENT()
 * @method static WrestlerStatus RELEASED()
 * @method static WrestlerStatus RETIRED()
 * @method static WrestlerStatus SUSPENDED()
 * @method static WrestlerStatus UNEMPLOYED()
 */
final class WrestlerStatus extends Enum
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
