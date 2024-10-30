<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeUnretiredException extends Exception
{
    public static function notRetired(): self
    {
        return new self('This model is not retired and cannot be unretired.');
    }
}
