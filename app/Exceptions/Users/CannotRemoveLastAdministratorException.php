<?php

declare(strict_types=1);

namespace App\Exceptions\Users;

use App\Exceptions\BaseBusinessException;

final class CannotRemoveLastAdministratorException extends BaseBusinessException
{
    public static function lastActiveAdministrator(): static
    {
        return new self('The platform must keep at least one active administrator. Make another user an active administrator first.');
    }
}
