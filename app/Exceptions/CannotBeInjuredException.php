<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeInjuredException extends Exception
{
    public static function unemployed(): self
    {
        return new self('This model is unemployed and cannot be injured.');
    }

    public static function released(): self
    {
        return new self('This model is released and cannot be injured.');
    }

    public static function retired(): self
    {
        return new self('This model is retired and cannot be injured.');
    }

    public static function hasFutureEmployment(): self
    {
        return new self('This model has not been officially employed and cannot be injured.');
    }

    public static function injured(): self
    {
        return new self('This model is already currently injured.');
    }

    public static function suspended(): self
    {
        return new self('This model is suspended and cannot be injured.');
    }
}
