<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeClearedFromInjuryException extends Exception
{
    public static function notInjured(): self
    {
        return new self('This model is not injured and cannot be cleared from an injury.');
    }
}
