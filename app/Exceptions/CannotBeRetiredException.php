<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeRetiredException extends Exception
{
    public static function unemployed(): self
    {
        return new self('This model is unemployed and cannot be retired.');
    }

    public static function unactivated(): self
    {
        return new self('This model is unactivated and cannot be retired.');
    }

    public static function hasFutureEmployment(): self
    {
        return new self('This model has not been officially employed and cannot be retired.');
    }

    public static function retired(): self
    {
        return new self('This model is already retired.');
    }

    public static function hasFutureActivation(): self
    {
        return new self('This model has not been officially activated and cannot be retired.');
    }
}
