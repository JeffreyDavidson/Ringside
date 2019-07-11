<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static TitleStatus BOOKABLE()
 * @method static TitleStatus RETIRED()
 * @method static TitleStatus PENDING_INTRODUCED()
 */
final class TitleStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const RETIRED = 'retired';
    const PENDING_INTRODUCED = 'pending-introduced';
}
