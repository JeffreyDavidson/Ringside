<?php

declare(strict_types=1);

namespace App\Enums\Promotions;

enum MembershipStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return __("promotions.status.{$this->value}");
    }
}
