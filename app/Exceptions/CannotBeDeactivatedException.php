<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeDeactivatedException extends Exception
{
    public static function unactivated(): self
    {
        return new self('This model is unemployed and cannot be released.');
    }

    public static function deactivated(): self
    {
        return new self('This model is already deactivated.');
    }

    public static function retired(): self
    {
        return new self('This model is retired and cannot be released.');
    }

    public static function hasFutureActivation(): self
    {
        return new self('This model has not been officially activated and cannot be deactivated.');
    }
}
