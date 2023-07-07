<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Injurable;
use Exception;

class CannotBeInjuredException extends Exception
{
    public static function unemployed(Injurable $model): self
    {
        return new self("`{$model->getIdentifier()}` is unemployed and cannot be injured.");
    }

    public static function released(Injurable $model): self
    {
        return new self("`{$model->getIdentifier()}` is released and cannot be injured.");
    }

    public static function retired(Injurable $model): self
    {
        return new self("`{$model->getIdentifier()}` is retired and cannot be injured.");
    }

    public static function hasFutureEmployment(Injurable $model): self
    {
        return new self("`{$model->getIdentifier()}` has not been officially employed and cannot be injured.");
    }

    public static function injured(Injurable $model): self
    {
        return new self("`{$model->getIdentifier()}` is already currently injured.");
    }

    public static function suspended(Injurable $model): self
    {
        return new self("`{$model->getIdentifier()}` is suspended and cannot be injured.");
    }
}
