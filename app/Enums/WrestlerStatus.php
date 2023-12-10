<?php

declare(strict_types=1);

namespace App\Enums;

enum WrestlerStatus: string
{
    case Bookable = 'bookable';
    case Injured = 'injured';
    case FutureEmployment = 'future_employment';
    case Released = 'released';
    case Retired = 'retired';
    case Suspended = 'suspended';
    case Unemployed = 'unemployed';

    public function color(): string
    {
        return match ($this) {
            self::Bookable => 'success',
            self::Injured => 'light',
            self::FutureEmployment => 'warning',
            self::Released => 'dark',
            self::Retired => 'secondary',
            self::Suspended => 'danger',
            self::Unemployed => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Bookable => 'Bookable',
            self::Injured => 'Injured',
            self::FutureEmployment => 'Awaiting Employment',
            self::Released => 'Released',
            self::Retired => 'Retired',
            self::Suspended => 'Suspended',
            self::Unemployed => 'Unemployed',
        };
    }
}
