<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static EventStatus SCHEDULED()
 * @method static EventStatus PAST()
 */
final class EventStatus extends Enum
{
    const SCHEDULED = 'scheduled';
    const PAST = 'past';
}
