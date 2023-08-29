<?php

declare(strict_types=1);

namespace App\Enums;

enum TagTeamStatus: string
{
    case Bookable = 'bookable';
    case Unbookable = 'unbookable';
    case FutureEmployment = 'future_employment';
    case Suspended = 'suspended';
    case Released = 'released';
    case Retired = 'retired';
    case Unemployed = 'unemployed';

    public function color(): string
    {
        return match ($this) {
            self::Bookable => 'success',
            self::Unbookable => 'light',
            self::FutureEmployment => 'warning',
            self::Suspended => 'danger',
            self::Released => 'dark',
            self::Retired => 'secondary',
            self::Unemployed => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Bookable => 'Bookable',
            self::Unbookable => 'Unbookable',
            self::FutureEmployment => 'Awaiting Employment',
            self::Suspended => 'Suspended',
            self::Released => 'Released',
            self::Retired => 'Retired',
            self::Unemployed => 'Unemployed',
        };
    }
}
