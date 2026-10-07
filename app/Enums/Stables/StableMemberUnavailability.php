<?php

declare(strict_types=1);

namespace App\Enums\Stables;

/**
 * Why a stable member cannot be moved between stables.
 */
enum StableMemberUnavailability: string
{
    case Unemployed = 'unemployed';
    case Injured = 'injured';
    case Suspended = 'suspended';
    case Retired = 'retired';

    public function label(): string
    {
        return __("stables.unavailability.{$this->value}");
    }
}
