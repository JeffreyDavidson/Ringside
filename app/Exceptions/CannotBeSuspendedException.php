<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contracts\Suspendable;
use Exception;

class CannotBeSuspendedException extends Exception
{
    public static function unemployed(Suspendable $model): self
    {
        return new self("`{$model->name}` is unemployed and cannot be suspended.");
    }

    public static function hasFutureEmployment(Suspendable $model): self
    {
        return new self("`{$model->name}` has not been officially employed and cannot be suspended.");
    }

    public static function retired(Suspendable $model): self
    {
        return new self("`{$model->name}` is retired and cannot be suspended.");
    }

    public static function released(Suspendable $model): self
    {
        return new self("`{$model->name}` is released and cannot be suspended.");
    }

    public static function suspended(Suspendable $model): self
    {
        return new self("`{$model->name}` is already suspended.");
    }

    public static function injured(Suspendable $model): self
    {
        return new self("`{$model->name}` is injured and cannot be suspended.");
    }
}
