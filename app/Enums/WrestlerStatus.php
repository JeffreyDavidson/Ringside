<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static WrestlerStatus BOOKABLE()
 * @method static WrestlerStatus PENDING_INTRODUCTION()
 * @method static WrestlerStatus RETIRED()
 * @method static WrestlerStatus SUSPENDED()
 * @method static WrestlerStatus INJURED()
 */
final class WrestlerStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const RETIRED = 'retired';
    const SUSPENDED = 'suspended';
    const INJURED = 'injured';
    const PENDING_INTRODUCTION = 'pending-introduction';
}
