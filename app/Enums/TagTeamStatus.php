<?php

declare(strict_types=1);

namespace App\Enums;

enum TagTeamStatus: string
{
    case BOOKABLE = 'bookable';
    case UNBOOKABLE = 'unbookable';
    case FUTURE_EMPLOYMENT = 'future_employment';
    case SUSPENDED = 'suspended';
    case RELEASED = 'released';
    case RETIRED = 'retired';
    case UNEMPLOYED = 'unemployed';

    public function color(): string
    {
        return match ($this) {
            self::BOOKABLE => 'success',
            self::UNBOOKABLE => 'light',
            self::FUTURE_EMPLOYMENT => 'warning',
            self::SUSPENDED => 'danger',
            self::RELEASED => 'dark',
            self::RETIRED => 'secondary',
            self::UNEMPLOYED => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BOOKABLE => 'Bookable',
            self::UNBOOKABLE => 'Unbookable',
            self::FUTURE_EMPLOYMENT => 'Awaiting Employment',
            self::SUSPENDED => 'Suspended',
            self::RELEASED => 'Retired',
            self::RETIRED => 'Retired',
            self::UNEMPLOYED => 'Unemployed',
        };
    }
}
