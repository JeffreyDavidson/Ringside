<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeReinstatedException extends Exception
{
    public static function unemployed(): self
    {
        return new self('This model is unemployed and cannot be reinstated.');
    }

    public static function released(): self
    {
        return new self('This model is released and cannot be reinstated.');
    }

    public static function retired(): self
    {
        return new self('This model is retired and cannot be reinstated.');
    }

    public static function hasFutureEmployment(): self
    {
        return new self('This model has not been officially employed and cannot be reinstated.');
    }

    public static function bookable(): self
    {
        return new self('This model is bookable and cannot be reinstated.');
    }

    public static function injured(): self
    {
        return new self('This model is injured and cannot be reinstated.');
    }

    public static function available(): self
    {
        return new self('This model is available and cannot be reinstated.');
    }
}
