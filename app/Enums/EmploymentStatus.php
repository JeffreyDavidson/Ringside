<?php

declare(strict_types=1);

namespace App\Enums;

enum EmploymentStatus: string
{
    case Available = 'available';
    case Bookable = 'bookable';
    case FutureEmployment = 'future_employment';
    case Injured = 'injured';
    case Released = 'released';
    case Retired = 'retired';
    case Suspended = 'suspended';
    case Unbookable = 'unbookable';
    case Unemployed = 'unemployed';

    public function color(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Bookable => 'success',
            self::Injured => 'light',
            self::FutureEmployment => 'warning',
            self::Released => 'dark',
            self::Retired => 'secondary',
            self::Suspended => 'danger',
            self::Unbookable => 'secondary',
            self::Unemployed => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Bookable => 'Bookable',
            self::Injured => 'Injured',
            self::FutureEmployment => 'Awaiting Employment',
            self::Released => 'Released',
            self::Retired => 'Retired',
            self::Suspended => 'Suspended',
            self::Unbookable => 'Unbookable',
            self::Unemployed => 'Unemployed',
        };
    }
}
