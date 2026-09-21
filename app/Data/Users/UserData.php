<?php

declare(strict_types=1);

namespace App\Data\Users;

use App\Enums\Users\Role;

readonly class UserData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public Role $role,
        public ?string $password,
    ) {}
}
