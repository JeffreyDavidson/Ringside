<?php

declare(strict_types=1);

namespace App\Enums\Promotions;

enum MembershipStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Suspended = 'suspended';
}
