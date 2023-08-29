<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Administrator = 'administrator';
    case Basic = 'basic';

    public function color(): string
    {
        return match ($this) {
            self::Administrator => 'success',
            self::Basic => 'secondary',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Basic => 'Basic',
        };
    }
}
