<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static TitleStatus ACTIVE()
 * @method static TitleStatus RETIRED()
 * @method static TitleStatus PENDING_INTRODUCED()
 */
final class TitleStatus extends Enum
{
    const ACTIVE = 'active';
    const RETIRED = 'retired';
    const PENDING_INTRODUCED = 'pending-introduced';
}
