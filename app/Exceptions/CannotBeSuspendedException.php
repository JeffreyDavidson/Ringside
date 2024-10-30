<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeSuspendedException extends Exception
{
    public static function unemployed(): self
    {
        return new self('This model is unemployed and cannot be suspended.');
    }

    public static function hasFutureEmployment(): self
    {
        return new self('This model has not been officially employed and cannot be suspended.');
    }

    public static function retired(): self
    {
        return new self('This model is retired and cannot be suspended.');
    }

    public static function released(): self
    {
        return new self('This model is released and cannot be suspended.');
    }

    public static function suspended(): self
    {
        return new self('This model is already suspended.');
    }

    public static function injured(): self
    {
        return new self('This model is injured and cannot be suspended.');
    }
}
