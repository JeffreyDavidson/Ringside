<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case ADMINISTRATOR = 'administrator';
    case BASIC = 'basic';

    public function color(): string
    {
        return match ($this) {
            self::ADMINISTRATOR => 'success',
            self::BASIC => 'secondary',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ADMINISTRATOR => 'Administrator',
            self::BASIC => 'Basic',
        };
    }
}
