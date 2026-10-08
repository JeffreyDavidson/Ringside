<?php

declare(strict_types=1);

namespace App\Exceptions\Users;

use App\Exceptions\BaseBusinessException;

final class CannotRemoveLastAdministratorException extends BaseBusinessException
{
    public static function lastActiveAdministrator(): static
    {
        return new self(__('users.last_administrator'));
    }
}
