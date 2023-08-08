<?php

declare(strict_types=1);

namespace App\Enums;

enum TitleStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case FUTURE_ACTIVATION = 'future_activation';
    case RETIRED = 'retired';
    case UNACTIVATED = 'unactivated';

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'light',
            self::FUTURE_ACTIVATION => 'warning',
            self::RETIRED => 'secondary',
            self::UNACTIVATED => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::FUTURE_ACTIVATION => 'Awaiting Activation',
            self::RETIRED => 'Retired',
            self::UNACTIVATED => 'Unactivated',
        };
    }
}
