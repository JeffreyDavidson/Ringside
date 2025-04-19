<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeReleasedException extends Exception
{
    public static function unemployed(): self
    {
        return new self('This model is unemployed and cannot be released.');
    }

    public static function released(): self
    {
        return new self('This model is already released.');
    }

    public static function retired(): self
    {
        return new self('This model is retired and cannot be released.');
    }

    public static function hasFutureEmployment(): self
    {
        return new self('This model has not been officially employed and cannot be released.');
    }
}
