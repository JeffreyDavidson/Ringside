<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static WrestlerStatus ACTIVE()
 * @method static WrestlerStatus INACTIVE()
 * @method static WrestlerStatus RETIRED()
 * @method static WrestlerStatus SUSPENDED()
 * @method static WrestlerStatus INJURED()
 */
final class WrestlerStatus extends Enum
{
    const BOOKABLE  = 'bookable';
    const INACTIVE  = 'inactive';
    const RETIRED   = 'retired';
    const SUSPENDED = 'suspended';
    const INJURED   = 'injured';
}
