<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static RefereeStatus BOOKABLE()
 * @method static RefereeStatus PENDING_INTRODUCTION()
 * @method static RefereeStatus INJURED()
 * @method static RefereeStatus SUSPENDED()
 * @method static RefereeStatus RETIRED()
 */
final class RefereeStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const PENDING_INTRODUCTION = 'pending-introduction';
    const INJURED = 'injured';
    const SUSPENDED = 'suspended';
    const RETIRED = 'retired';
}
