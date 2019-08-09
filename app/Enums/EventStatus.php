<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static EVENTStatus SCHEDULED()
 * @method static EVENTStatus PAST()
 */
final class EventStatus extends Enum
{
    const SCHEDULED = 'scheduled';
    const PAST = 'past';
}
