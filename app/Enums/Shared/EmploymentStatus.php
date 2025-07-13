<?php

declare(strict_types=1);

namespace App\Enums\Shared;

/**
 * Employment status enum for entities that can be employed.
 *
 * Represents the true employment relationship state between entities and organizations.
 * This enum focuses solely on employment contracts and hiring/firing relationships.
 * Other concerns like availability, health, or disciplinary status are handled separately.
 */
enum EmploymentStatus: string
{
    case Employed = 'employed';
    case FutureEmployment = 'future_employment';
    case Released = 'released';
    case Retired = 'retired';
    case Unemployed = 'unemployed';

    public function color(): string
    {
        return match ($this) {
            self::Employed => 'success',
            self::FutureEmployment => 'warning',
            self::Released => 'dark',
            self::Retired => 'secondary',
            self::Unemployed => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Employed => 'Employed',
            self::FutureEmployment => 'Awaiting Employment',
            self::Released => 'Released',
            self::Retired => 'Retired',
            self::Unemployed => 'Unemployed',
        };
    }
}
