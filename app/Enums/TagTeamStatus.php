<?php

declare(strict_types=1);

namespace App\Enums;

enum TagTeamStatus: string
{
    case BOOKABLE = 'bookable';
    case UNBOOKABLE = 'unbookable';
    case FUTURE_EMPLOYMENT = 'future_employment';
    case RELEASED = 'released';
    case RETIRED = 'retired';
    case SUSPENDED = 'suspended';
    case UNEMPLOYED = 'unemployed';

    public function color(): string
    {
        return match ($this) {
            self::BOOKABLE => 'success',
            self::UNBOOKABLE => 'light',
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
            self::BOOKABLE => 'Bookable',
            self::UNBOOKABLE => 'Unbookable',
            self::FUTURE_EMPLOYMENT => 'Awaiting Employment',
            self::RELEASED => 'Retired',
            self::RETIRED => 'Retired',
            self::SUSPENDED => 'Suspended',
            self::UNEMPLOYED => 'Unemployed',
        };
    }
}
