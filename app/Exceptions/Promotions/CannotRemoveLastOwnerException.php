<?php

declare(strict_types=1);

namespace App\Exceptions\Promotions;

use App\Exceptions\BaseBusinessException;

final class CannotRemoveLastOwnerException extends BaseBusinessException
{
    public static function lastActiveOwner(): static
    {
        return new self('A promotion must keep at least one active owner. Make another member an active owner first.');
    }
}
