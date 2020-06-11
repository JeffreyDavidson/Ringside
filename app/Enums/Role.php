<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static Role ADMINISTRATOR()
 * @method static Role BASIC()
 * @method static Role SUPER_ADMINISTRATOR()
 */
final class Role extends Enum
{
    const __default = self::BASIC;

    const BASIC = 'basic';
    const ADMINISTRATOR = 'admin';
    const SUPER_ADMINISTRATOR = 'super_admin';
}
