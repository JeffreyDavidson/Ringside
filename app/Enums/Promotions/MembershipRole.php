<?php

declare(strict_types=1);

namespace App\Enums\Promotions;

enum MembershipRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Member = 'member';
}
