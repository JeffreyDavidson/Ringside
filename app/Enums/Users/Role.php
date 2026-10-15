<?php

declare(strict_types=1);

namespace App\Enums\Users;

enum Role: string
{
    case Administrator = 'administrator';
    case Basic = 'basic';

    public function isAdministrator(): bool
    {
        return $this === self::Administrator;
    }

    public function label(): string
    {
        return __("users.roles.{$this->value}");
    }
}
