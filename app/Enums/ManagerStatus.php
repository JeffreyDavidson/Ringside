<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static ManagerStatus BOOKABLE()
 * @method static ManagerStatus PENDING_INTRODUCTION()
 * @method static ManagerStatus INJURED()
 * @method static ManagerStatus RETIRED()
 * @method static ManagerStatus SUSPENDED()
 */
final class ManagerStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const RETIRED = 'retired';
    const SUSPENDED = 'suspended';
    const INJURED = 'injured';
    const PENDING_INTRODUCTION = 'pending-introduction';
}
