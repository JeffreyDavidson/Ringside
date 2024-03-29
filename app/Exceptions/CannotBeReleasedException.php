<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Employable;
use Exception;

class CannotBeReleasedException extends Exception
{
    public static function unemployed(Employable $model): self
    {
        return new self("`{$model->getIdentifier()}` is unemployed and cannot be released.");
    }

    public static function released(Employable $model): self
    {
        return new self("`{$model->getIdentifier()}` is already released.");
    }

    public static function retired(Employable $model): self
    {
        return new self("`{$model->getIdentifier()}` is retired and cannot be released.");
    }

    public static function hasFutureEmployment(Employable $model): self
    {
        return new self("`{$model->getIdentifier()}` has not been officially employed and cannot be released.");
    }
}
