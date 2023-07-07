<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Retirable;
use Exception;

class CannotBeRetiredException extends Exception
{
    public static function unemployed(Retirable $model): self
    {
        return new self("`{$model->getIdentifier()}` is unemployed and cannot be retired.");
    }

    public static function hasFutureEmployment(Retirable $model): self
    {
        return new self("`{$model->getIdentifier()}` has not been officially employed and cannot be retired.");
    }

    public static function retired(Retirable $model): self
    {
        return new self("`{$model->getIdentifier()}` is already retired.");
    }
}
