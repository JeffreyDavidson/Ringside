<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Suspendable;
use Exception;

class CannotBeSuspendedException extends Exception
{
    public static function unemployed(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is unemployed and cannot be suspended.");
    }

    public static function hasFutureEmployment(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` has not been officially employed and cannot be suspended.");
    }

    public static function retired(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is retired and cannot be suspended.");
    }

    public static function released(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is released and cannot be suspended.");
    }

    public static function suspended(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is already suspended.");
    }

    public static function injured(Suspendable $model): self
    {
        return new self("`{$model->getIdentifier()}` is injured and cannot be suspended.");
    }
}
