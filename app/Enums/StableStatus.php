<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static StableStatus BOOKABLE()
 * @method static StableStatus PENDING_INTRODUCTION()
 * @method static StableStatus RETIRED()
 */
final class StableStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const PENDING_INTRODUCTION = 'pending-introduction';
    const RETIRED = 'retired';
}
