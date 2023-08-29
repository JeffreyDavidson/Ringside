<?php

declare(strict_types=1);

namespace App\Enums;

enum ManagerStatus: string
{
    case Available = 'available';
    case Injured = 'injured';
    case FutureEmployment = 'future_employment';
    case Released = 'released';
    case Retired = 'retired';
    case Suspended = 'suspended';
    case Unemployed = 'unemployed';

    public function color(): string
    {
        return match ($this) {
            self::Available => 'success',
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
            self::Available => 'Available',
            self::Injured => 'Injured',
            self::FutureEmployment => 'Awaiting Employment',
            self::Released => 'Released',
            self::Retired => 'Retired',
            self::Suspended => 'Suspended',
            self::Unemployed => 'Unemployed',
        };
    }
}
