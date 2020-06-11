<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static EventStatus PAST()
 * @method static EventStatus PENDING()
 * @method static EventStatus SCHEDULED()
 */
final class EventStatus extends Enum
{
    const __default = self::PENDING;

    const PAST = 'past';
    const PENDING = 'pending';
    const SCHEDULED = 'scheduled';
}
