<?php

declare(strict_types=1);

namespace App\Enums\Users;

use App\Support\Enums\ProvidesFilterOptions;

enum UserStatus: string
{
    use ProvidesFilterOptions;

    case Unverified = 'unverified';
    case Active = 'active';
    case Inactive = 'inactive';

    public function color(): string
    {
        return match ($this) {
            self::Unverified => 'warning',
            self::Active => 'success',
            self::Inactive => 'light',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Unverified',
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }
}
