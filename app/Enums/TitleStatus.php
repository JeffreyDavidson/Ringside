<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static TitleStatus ACTIVE()
 * @method static TitleStatus INACTIVE()
 * @method static TitleStatus RETIRED()
 */
final class TitleStatus extends Enum
{
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
    const RETIRED = 'retired';
}
