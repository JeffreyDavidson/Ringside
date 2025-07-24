<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeActivatedException extends Exception
{
    public static function activated(): self
    {
        return new self('This model is already activated.');
    }

    public static function retired(): self
    {
        return new self('This entity is retired and cannot be reactivated.');
    }
}
