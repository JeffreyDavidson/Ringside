<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CannotBeEmployedException extends Exception
{
    public static function employed(): self
    {
        return new self('This model is already employed.');
    }
}
