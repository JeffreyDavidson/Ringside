<?php

declare(strict_types=1);

namespace App\Enums;

enum StableStatus: string
{
    case Active = 'active';
    case FutureActivation = 'future_activation';
    case Inactive = 'inactive';
    case Retired = 'retired';
    case Unactivated = 'unactivated';

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::FutureActivation => 'warning',
            self::Inactive => 'dark',
            self::Retired => 'secondary',
            self::Unactivated => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::FutureActivation => 'Awaiting Activation',
            self::Inactive => 'Inactive',
            self::Retired => 'Retired',
            self::Unactivated => 'Unactivated',
        };
    }
}
