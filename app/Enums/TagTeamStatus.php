<?php

namespace App\Enums;

use MadWeb\Enum\Enum;

/**
 * @method static TagTeamStatus BOOKABLE()
 * @method static TagTeamStatus PENDING_INTRODUCED()
 * @method static TagTeamStatus RETIRED()
 * @method static TagTeamStatus SUSPENDED()
 */
final class TagTeamStatus extends Enum
{
    const BOOKABLE = 'bookable';
    const RETIRED = 'retired';
    const SUSPENDED = 'suspended';
    const PENDING_INTRODUCED = 'pending-introduced';
}
