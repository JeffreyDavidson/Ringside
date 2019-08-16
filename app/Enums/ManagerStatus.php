<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static ManagerStatus BOOKABLE()
 * @method static ManagerStatus PENDING_INTRODUCTION()
 * @method static ManagerStatus INJURED()
 * @method static ManagerStatus SUSPENDED()
 * @method static ManagerStatus RETIRED()
 */
final class ManagerStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const PENDING_INTRODUCTION = 'pending-introduction';
    const INJURED = 'injured';
    const SUSPENDED = 'suspended';
    const RETIRED = 'retired';
}
