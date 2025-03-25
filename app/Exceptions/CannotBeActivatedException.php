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
}
