<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static RefereeStatus BOOKABLE()
 * @method static RefereeStatus PENDING_INTRODUCTION()
 * @method static RefereeStatus RETIRED()
 * @method static RefereeStatus SUSPENDED()
 * @method static RefereeStatus INJURED()
 */
final class RefereeStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const RETIRED = 'retired';
    const SUSPENDED = 'suspended';
    const INJURED = 'injured';
    const PENDING_INTRODUCTION = 'pending-introduction';
}
