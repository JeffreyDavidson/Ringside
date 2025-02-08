<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivationStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case FutureActivation = 'future_activation';
    case Retired = 'retired';
    case Unactivated = 'unactivated';

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'light',
            self::FutureActivation => 'warning',
            self::Retired => 'secondary',
            self::Unactivated => 'info',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::FutureActivation => 'Awaiting Activation',
            self::Retired => 'Retired',
            self::Unactivated => 'Unactivated',
        };
    }
}
