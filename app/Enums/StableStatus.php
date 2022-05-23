<?php

declare(strict_types=1);

namespace App\Enums;

enum StableStatus: string
{
    case ACTIVE = 'active';
    case FUTURE_ACTIVATION = 'future_activation';
    case INACTIVE = 'inactive';
    case RETIRED = 'retired';
    case UNACTIVATED = 'unactivated';

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::FUTURE_ACTIVATION => 'warning',
            self::INACTIVE => 'dark',
            self::RETIRED => 'secondary',
            self::UNACTIVATED => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::FUTURE_ACTIVATION => 'Awaiting Activation',
            self::INACTIVE => 'Inactive',
            self::RETIRED => 'Retired',
            self::UNACTIVATED => 'Unactivated',
        };
    }
}
