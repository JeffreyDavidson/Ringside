<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static StableStatus ACTIVE()
 * @method static StableStatus FUTURE_ACTIVATION()
 * @method static StableStatus INACTIVE()
 * @method static StableStatus RETIRED()
 * @method static StableStatus UNACTIVATED()
 */
final class StableStatus extends Enum
{
    const __default = self::UNACTIVATED;

    const ACTIVE = 'active';
    const FUTURE_ACTIVATION = 'future-activation';
    const INACTIVE = 'inactive';
    const RETIRED = 'retired';
    const UNACTIVATED = 'unactivated';
}
