<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static TitleStatus ACTIVE()
 * @method static TitleStatus INACTIVE()
 * @method static TitleStatus FUTURE_ACTIVATION()
 * @method static TitleStatus RETIRED()
 * @method static TitleStatus UNACTIVATED()
 */
final class TitleStatus extends Enum
{
    const __default = self::UNACTIVATED;

    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
    const FUTURE_ACTIVATION = 'future-activation';
    const RETIRED = 'retired';
    const UNACTIVATED = 'unactivated';
}
