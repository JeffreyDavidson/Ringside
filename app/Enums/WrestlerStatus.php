<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static WrestlerStatus BOOKABLE()
 * @method static WrestlerStatus PENDING_INTRODUCTION()
 * @method static WrestlerStatus INJURED()
 * @method static WrestlerStatus SUSPENDED()
 * @method static WrestlerStatus RETIRED()
 */
final class WrestlerStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const PENDING_INTRODUCTION = 'pending-introduction';
    const INJURED = 'injured';
    const SUSPENDED = 'suspended';
    const RETIRED = 'retired';
}
