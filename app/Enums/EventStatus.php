<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static EventStatus PAST()
 * @method static EventStatus SCHEDULED()
 * @method static EventStatus UNSCHEDULED()
 */
final class EventStatus extends Enum
{
    const __default = self::UNSCHEDULED;

    const PAST = 'past';
    const SCHEDULED = 'scheduled';
    const UNSCHEDULED = 'unscheduled';
}
