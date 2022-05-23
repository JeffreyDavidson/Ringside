<?php

declare(strict_types=1);

namespace App\Enums;

enum ManagerStatus: string
{
    case AVAILABLE = 'available';
    case INJURED = 'injured';
    case FUTURE_EMPLOYMENT = 'future_employment';
    case RELEASED = 'released';
    case RETIRED = 'retired';
    case SUSPENDED = 'suspended';
    case UNEMPLOYED = 'unemployed';

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::INJURED => 'light',
            self::FUTURE_EMPLOYMENT => 'warning',
            self::RELEASED => 'dark',
            self::RETIRED => 'secondary',
            self::SUSPENDED => 'danger',
            self::UNEMPLOYED => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::INJURED => 'Injured',
            self::FUTURE_EMPLOYMENT => 'Awaiting Employment',
            self::RELEASED => 'Released',
            self::RETIRED => 'Retired',
            self::SUSPENDED => 'Suspended',
            self::UNEMPLOYED => 'Unemployed',
        };
    }
}
