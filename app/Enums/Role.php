<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static Role SUPER_ADMINISTRATOR()
 * @method static Role ADMINISTRATOR()
 * @method static Role BASIC()
 */
final class Role extends Enum
{
    const __default = self::BASIC;

    const SUPER_ADMINISTRATOR = 'super-admin';
    const ADMINISTRATOR = 'admin';
    const BASIC = 'basic';
}
